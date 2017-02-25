<?php
namespace W3TC;

class Root_Loader {
	/**
	 * Enabled Plugins that has been run
	 *
	 * @var W3_Plugin[]
	 */
	private $_loaded_plugins = array();
	/**
	 * Enabled extensions that has been run
	 *
	 * @var W3_Plugin[]
	 */
	private $_loaded_extensions = array();

	function __construct() {
		$c = Dispatcher::config();

		$plugins = array();
		$plugins[] = new Generic_Plugin();

		if ( $c->get_boolean( 'dbcache.enabled' ) )
			$plugins[] = new DbCache_Plugin();
		if ( $c->get_boolean( 'objectcache.enabled' ) )
			$plugins[] = new ObjectCache_Plugin();
		if ( $c->get_boolean( 'pgcache.enabled' ) )
			$plugins[] = new PgCache_Plugin();
		if ( $c->get_boolean( 'cdn.enabled' ) )
			$plugins[] = new Cdn_Plugin();
		if ( $c->get_boolean( 'browsercache.enabled' ) )
			$plugins[] = new BrowserCache_Plugin();
		if ( $c->get_boolean( 'minify.enabled' ) )
			$plugins[] = new Minify_Plugin();
		if ( $c->get_boolean( 'varnish.enabled' ) )
			$plugins[] = new Varnish_Plugin();
		if ( $c->get_boolean( 'stats.enabled' ) )
			$plugins[] = new UsageStatistics_Plugin();

		if ( is_admin() ) {
			$plugins[] = new Generic_Plugin_PageCachePurge();
			$plugins[] = new Generic_Plugin_Admin();
			$plugins[] = new BrowserCache_Plugin_Admin();
			$plugins[] = new DbCache_Plugin_Admin();
			$plugins[] = new ObjectCache_Plugin_Admin();
			$plugins[] = new PgCache_Plugin_Admin();
			$plugins[] = new Minify_Plugin_Admin();
			$plugins[] = new Generic_WidgetSpreadTheWord_Plugin();
			//$plugins[] = new Generic_Plugin_WidgetNews();
			$plugins[] = new Generic_Plugin_WidgetForum();
			$plugins[] = new SystemOpCache_Plugin_Admin();

			$plugins[] = new Cdn_Plugin_Admin();
			if ( $c->get_string( 'cdn.engine' ) == 'highwinds' ) {
			} else if ( $c->get_string( 'cdn.engine' ) != 'netdna' )
					$plugins[] = new Cdn_Plugin_WidgetMaxCdn();
				else
					$plugins[] = new Cdn_Plugin_WidgetNetDna();

				if ( $c->get_boolean( 'widget.pagespeed.enabled' ) )
					$plugins[] = new PageSpeed_Plugin_Widget();

				$plugins[] = new Generic_Plugin_AdminCompatibility();

			if ( !( defined( 'W3TC_PRO' ) || Util_Environment::is_w3tc_enterprise() ) )
				$plugins[] = new Licensing_Plugin_Admin();

			if ( $c->get_boolean( 'pgcache.enabled' ) ||
				$c->get_boolean( 'varnish.enabled' ) )
				$plugins[] = new Generic_Plugin_AdminRowActions();

			$plugins[] = new Extensions_Plugin_Admin();
			$plugins[] = new Generic_Plugin_AdminNotifications();
			$plugins[] = new UsageStatistics_Plugin_Admin();
		}

		$this->_loaded_plugins = $plugins;

		register_activation_hook( W3TC_FILE, array(
				$this,
				'activate'
			) );

		register_deactivation_hook( W3TC_FILE, array(
				$this,
				'deactivate'
			) );
	}

	/**
	 * Run plugins
	 */
	function run() {
		foreach ( $this->_loaded_plugins as $plugin ) {
			$plugin->run();
		}

		if ( method_exists( $GLOBALS['wpdb'], 'on_w3tc_plugins_loaded' ) ) {
			$o = $GLOBALS['wpdb'];
			$o->on_w3tc_plugins_loaded();
		}

		$this->run_extensions();
	}

	/**
	 * Activation action hook
	 */
	public function activate( $network_wide ) {
		Root_AdminActivation::activate( $network_wide );
	}

	/**
	 * Deactivation action hook
	 */
	public function deactivate() {
		w3tc_wpcli_stop_prime(); // Stop the WP-CLI page cache prime if it's still running
		Root_AdminActivation::deactivate();
	}

	/**
	 * Loads extensions stored in config
	 */
	function run_extensions() {
		$c = Dispatcher::config();
		$extensions = $c->get_array( 'extensions.active' );

		$loaded = array();

		$frontend = $c->get_array( 'extensions.active_frontend' );
		foreach ( $frontend as $extension => $nothing ) {
			if ( isset( $extensions[$extension] ) ) {
				$path = $extensions[$extension];
				$filename = W3TC_EXTENSION_DIR . '/' .
					str_replace( '..', '', trim( $path, '/' ) );

				if ( file_exists( $filename ) && !isset( $loaded[$filename] ) )
					include $filename;

				$loaded[$filename] = '*';
			}
		}

		if ( is_admin() ) {
			$extensions = $c->get_array( 'extensions.active' );
			foreach ( $extensions as $extension => $path ) {
				$filename = W3TC_EXTENSION_DIR . '/' .
					str_replace( '..', '', trim( $path, '/' ) );

				if ( file_exists( $filename ) && !isset( $loaded[$filename] ) )
					include $filename;

				$loaded[$filename] = '*';
			}
		}
	}
}

global $w3tc_root;
if ( is_null( $w3tc_root ) ) {
	$w3tc_root = new \W3TC\Root_Loader();
	$w3tc_root->run();
}
