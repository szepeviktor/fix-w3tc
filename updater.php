<?php

// Prevent loading this file directly and/or if the class is already defined
if ( ! defined( 'ABSPATH' ) || class_exists( 'WPGitHubUpdater' ) || class_exists( 'WP_GitHub_Updater' ) )
	return;

/**
 *
 *
 * @version 1.6
 * @author Joachim Kudish <info@jkudish.com>
 * @link http://jkudish.com
 * @package WP_GitHub_Updater
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright (c) 2011-2013, Joachim Kudish
 *
 * GNU General Public License, Free Software Foundation
 * <http://creativecommons.org/licenses/GPL/2.0/>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
class WP_GitHub_Updater {

	/**
	 * GitHub Updater version
	 */
	const VERSION = 1.6;

	/**
	 * @var $config the config for the updater
	 * @access public
	 */
	var $config;

	/**
	 * @var $missing_config any config that is missing from the initialization of this instance
	 * @access public
	 */
	var $missing_config;

	/**
	 * @var $github_data temporiraly store the data fetched from GitHub, allows us to only load the data once per class instance
	 * @access private
	 */
	private $github_data;


	/**
	 * Class Constructor
	 *
	 * @since 1.0
	 * @param array $config the configuration required for the updater to work
	 * @see has_minimum_config()
	 * @return void
	 */
	public function __construct( $config = array() ) {

		$defaults = array(
			'slug' => plugin_basename( __FILE__ ),
			'proper_folder_name' => dirname( plugin_basename( __FILE__ ) ),
			'sslverify' => true,
		);

		$this->config = wp_parse_args( $config, $defaults );

		// if the minimum config isn't set, issue a warning and bail
		if ( ! $this->has_minimum_config() ) {
			$message = 'The GitHub Updater was initialized without the minimum required configuration, please check the config in your plugin. The following params are missing: ';
			$message .= implode( ',', $this->missing_config );
			_doing_it_wrong( __CLASS__, $message , self::VERSION );
			return;
		}

		// When WP does scheduled update checks we inform it if our plugin has new version
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'api_check' ) );
		// Hook into the plugin details screen
		add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 10, 3 );
		// After download, replace old with new version and reactivate plugin
		add_filter( 'upgrader_post_install', array( $this, 'upgrader_post_install' ), 10, 3 );
	}

	public function has_minimum_config() {

		$this->missing_config = array();

		$required_config_params = array(
			'api_url',
			'raw_url',
			'github_url',
			'zip_url',
			'requires',
			'tested',
		);

		foreach ( $required_config_params as $required_param ) {
			if ( empty( $this->config[$required_param] ) )
				$this->missing_config[] = $required_param;
		}

		return ( empty( $this->missing_config ) );
	}


	/**
	 * Check wether or not the transients need to be overruled and API needs to be called for every single page load
	 *
	 * @return bool overrule or not
	 */
	public function overrule_transients() {
		return ( defined( 'WP_GITHUB_FORCE_UPDATE' ) && WP_GITHUB_FORCE_UPDATE );
	}


	/**
	 * Set defaults
	 *
	 * @since 1.2
	 * @return void
	 */
	public function init($brief=true) {
			
		$plugin_data = $this->get_plugin_data();

		if ( ! isset( $this->config['version'] ) )
			$this->config['version'] = $plugin_data['Version'];

		if ( ! isset( $this->config['new_version'] ) )
			$this->config['new_version'] = $this->get_new_version();

		if ($brief) return;

		if ( ! isset( $this->config['last_updated'] ) )
			$this->config['last_updated'] = $this->get_date();

		if ( ! isset( $this->config['description'] ) )
			$this->config['description'] = $this->get_description();

		if ( ! isset( $this->config['plugin_name'] ) )
			$this->config['plugin_name'] = $plugin_data['Name'];

		if ( ! isset( $this->config['author'] ) )
			$this->config['author'] = $plugin_data['Author'];

		if ( ! isset( $this->config['homepage'] ) )
			$this->config['homepage'] = $plugin_data['PluginURI'];

		if ( !empty( $this->config['changelog'] ) && empty( $this->config['log'] ) )
			$this->config['log'] = $this->get_changelog();
	}


	/**
	 * Callback fn for the http_request_timeout filter
	 *
	 * @since 1.0
	 * @return int timeout value
	 */
	public function http_request_timeout() {
		return 2;
	}

	/**
	 * Callback fn for the http_request_args filter
	 *
	 * @param unknown $args
	 * @param unknown $url
	 *
	 * @return mixed
	 */
	public function http_request_sslverify( $args, $url ) {
		if ( $this->config[ 'zip_url' ] == $url )
			$args[ 'sslverify' ] = $this->config[ 'sslverify' ];

		return $args;
	}


	/**
	 * Get New Version from GitHub
	 *
	 * @since 1.0
	 * @return int $version the version number
	 */
	public function get_new_version() {
		$version = get_site_transient( md5($this->config['slug']).'_new_version' );

		if ( ( $this->overrule_transients() || ( !isset( $version ) || !$version || '' == $version ) ) || (isset($_GET['force-check']) && $_GET['force-check']==1) ) {
			$raw_response = $this->remote_get( trailingslashit( $this->config['raw_url'] ) . basename( $this->config['slug'] ) );

			if ( is_wp_error( $raw_response ) )
				$version = false;

			if (is_array($raw_response)) {
				if (!empty($raw_response['body']))
					preg_match( '/.*Version\:\s*(.*)$/mi', $raw_response['body'], $matches );
			}

			if ( empty( $matches[1] ) )
				$version = false;
			else
				$version = $matches[1];

			// refresh every 6 hours
			if ( false !== $version )
				set_site_transient( md5($this->config['slug']).'_new_version', $version, 60*60*6 );
		}

		return $version;
	}
	
	public function get_changelog()
	{
		$changelog = get_site_transient( md5($this->config['slug']).'_new_changelog' );

		if ( $this->overrule_transients() || ( !isset( $changelog ) || !$changelog || '' == $changelog ) ) {
			$changelog = false;
			$raw_response = $this->remote_get( trailingslashit( $this->config['raw_url'] ) . $this->config['changelog'] );

			if ( !is_wp_error( $raw_response ) && isset($raw_response['body']) ) {
				$raw = $raw_response['body'];
				
				$lines = (array) preg_split('~[\r\n]+~', $raw);
				
				$ul = false;
				$found=false;
				$changelog = "";
				$vercnt = 0;

				foreach ($lines as $index => $line) {				
					if (preg_match("~^\s*\*\s*(.+)\s*~", $line, $match)) {
						$line = "<li>".htmlspecialchars($match[1])."</li>";
						if (!$ul) {
							$line = '<ul style="list-style: disc;">'.$line;
							$ul = true;
							$found = true;
						}						
					} 
					else if (preg_match("~^\s*=\s*([0-9.]+)\s*=~", $line, $match)) {
						if ($vercnt < 6)
						{
							$line = "<h3>".htmlspecialchars($match[1])."</h3>";
							if ($ul) {
								$line = '</ul>'.$line;
								$ul = false;
							}
							
							$vercnt++;
						}
						else
							break;
					}
					else
					{
						$line = trim($line);
						if (!empty($line))
						{
							$line = "<div>$line</div>";
							if ($ul) {
								$changelog = preg_replace('~</li>$~','',$changelog);
								$line .= "</li>";
							}
						}
					}
					
					$changelog .= $line;
				}

				if ($ul) {
					$changelog .= '</ul>';
				}

				if (!$found) $changelog = false;
			}
		
			if ( false !== $changelog ) {			
				set_site_transient( md5($this->config['slug']).'_new_changelog', $changelog, 60*60*6 );
			}
		}
		
		return $changelog;
	}

	/**
	 * Interact with GitHub
	 *
	 * @param string $query
	 *
	 * @since 1.6
	 * @return mixed
	 */
	public function remote_get( $query ) {
		// set timeout
		add_filter( 'http_request_timeout', array( $this, 'http_request_timeout' ) );

		// set sslverify for zip download
		add_filter( 'http_request_args', array( $this, 'http_request_sslverify' ), 10, 2 );

		$raw_response = wp_remote_get( $query, array(
			'sslverify' => $this->config['sslverify']
		) );
		// Remove filters so we don't globally impact http requets.
		remove_filter( 'http_request_timeout', array( $this, 'http_request_timeout' ) );
		remove_filter( 'http_request_args', array( $this, 'http_request_sslverify' ) );
		return $raw_response;
	}


	/**
	 * Get GitHub Data from the specified repository
	 *
	 * @since 1.0
	 * @return array $github_data the data
	 */
	public function get_github_data() {
		if ( isset( $this->github_data ) && ! empty( $this->github_data ) ) {
			$github_data = $this->github_data;
		} else {
			$github_data = get_site_transient( md5($this->config['slug']).'_github_data' );

			if ( $this->overrule_transients() || ( ! isset( $github_data ) || ! $github_data || '' == $github_data ) ) {
				$github_data = $this->remote_get( $this->config['api_url'] );

				if ( is_wp_error( $github_data ) )
					return false;

				$github_data = json_decode( $github_data['body'] );

				// refresh every 6 hours
				set_site_transient( md5($this->config['slug']).'_github_data', $github_data, 60*60*6 );
			}

			// Store the data in this class instance for future calls
			$this->github_data = $github_data;
		}

		return $github_data;
	}


	/**
	 * Get update date
	 *
	 * @since 1.0
	 * @return string $date the date
	 */
	public function get_date() {
		$_date = $this->get_github_data();
		return ( !empty( $_date->updated_at ) ) ? date( 'Y-m-d', strtotime( $_date->updated_at ) ) : false;
	}


	/**
	 * Get plugin description
	 *
	 * @since 1.0
	 * @return string $description the description
	 */
	public function get_description() {
		$_description = $this->get_github_data();
		return ( !empty( $_description->description ) ) ? $_description->description : false;
	}


	/**
	 * Get Plugin data
	 *
	 * @since 1.0
	 * @return object $data the data
	 */
	public function get_plugin_data() {
		include_once ABSPATH.'/wp-admin/includes/plugin.php';
		$data = get_plugin_data( WP_PLUGIN_DIR.'/'.$this->config['slug'] );
		return $data;
	}


	/**
	 * Hook into the plugin update check and connect to GitHub
	 *
	 * @since 1.0
	 * @param object  $transient the plugin data transient
	 * @return object $transient updated plugin data transient
	 */
	public function api_check( $transient ) {

		// Check if the transient contains the 'checked' information
		// If not, just return its value without hacking it
		if ( empty( $transient->checked ) )
			return $transient;

		$this->init();

		// check the version and decide if it's new
		$update = version_compare( $this->config['new_version'], $this->config['version'] );

		if ( 1 === $update ) {
			$response = new stdClass;
			$response->new_version = $this->config['new_version'];
			$response->slug = $this->config['proper_folder_name'];
			$response->url = $this->config['github_url'];
			$response->package = $this->config['zip_url'];
			$response->tested = $this->config['tested'];

			// If response is false, don't alter the transient
			if ( false !== $response )
			{
				$transient->response[ $this->config['slug'] ] = $response;
				delete_site_transient(md5($this->config['slug']).'_new_changelog');
			}
		}

		return $transient;
	}


	/**
	 * Get Plugin info
	 *
	 * @since 1.0
	 * @param bool    $false  always false
	 * @param string  $action the API function being performed
	 * @param object  $args   plugin arguments
	 * @return object $response the plugin info
	 */
	public function get_plugin_info( $false, $action, $response ) {
		// Check if this call API is for the right plugin
		
		if ( $action != "plugin_information" || !isset( $response->slug ) || strpos($this->config['slug'],$response->slug) === false )
			return false;

		$this->init(false);
		
		$response->name  = $this->config['plugin_name'];
		$response->slug = dirname($this->config['slug']);
		$response->version = $this->config['new_version'];
		$response->author = $this->config['author'];
		$response->homepage = $this->config['homepage'];
		$response->requires = $this->config['requires'];
		$response->tested = $this->config['tested'];
		$response->downloaded   = 0;
		$response->last_updated = $this->config['last_updated'];
		$response->sections['description'] = $this->config['description'];
		if (!empty($this->config['log'])) $response->sections['changelog'] = $this->config['log'];
		$response->banners = array("low" => $this->config['banner']);
		$response->download_link = $this->config['zip_url'];

		return $response;
	}


	/**
	 * Upgrader/Updater
	 * Move & activate the plugin, echo the update message
	 *
	 * @since 1.0
	 * @param boolean $true       always true
	 * @param mixed   $hook_extra not used
	 * @param array   $result     the result of the move
	 * @return array $result the result of the move
	 */
	public function upgrader_post_install( $true, $hook_extra, $result ) {

		global $wp_filesystem;

		// Move & Activate
		$proper_destination = WP_PLUGIN_DIR.'/'.$this->config['proper_folder_name'];
		$wp_filesystem->move( $result['destination'], $proper_destination );
		$result['destination'] = $proper_destination;
		$activate = activate_plugin( WP_PLUGIN_DIR.'/'.$this->config['slug'] );

		// Output the update message
		$fail  = __( 'The plugin has been updated, but could not be reactivated. Please reactivate it manually.', 'github_plugin_updater' );
		$success = __( 'Plugin reactivated successfully.', 'github_plugin_updater' );
		echo is_wp_error( $activate ) ? $fail : $success;
		return $result;

	}
}
