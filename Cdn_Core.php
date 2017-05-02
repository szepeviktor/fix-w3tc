<?php
namespace W3TC;

/**
 * W3 Total Cache CDN Plugin
 */



/**
 * class Cdn_Core
 */
class Cdn_Core {
	/**
	 * Config
	 */
	private $_config = null;

	/**
	 * Runs plugin
	 */
	function __construct() {
		$this->_config = Dispatcher::config();
	}

	/**
	 * Adds file to queue
	 *
	 * @param string  $local_path
	 * @param string  $remote_path
	 * @param integer $command
	 * @param string  $last_error
	 * @return integer
	 */
	function queue_add( $local_path, $remote_path, $command, $last_error ) {
		global $wpdb;

		$table = $wpdb->base_prefix . W3TC_CDN_TABLE_QUEUE;
		$rows = $wpdb->get_results( $wpdb->prepare(
				'SELECT id, command '.
				"FROM $table " .
				'WHERE local_path = %s AND remote_path = %s',
				$local_path, $remote_path ) );

		$already_exists = false;
		foreach ( $rows as $row ) {
			if ( $row->command != $command )
				$wpdb->query( $wpdb->prepare(
						"DELETE FROM $table " .
						'WHERE id = %d', $row->id ) );
			else
				$already_exists = true;
		}

		if ( $already_exists )
			return true;

		// insert if not yet there
		return $wpdb->query( $wpdb->prepare(
				"INSERT INTO $table " .
				'(local_path, remote_path, command, last_error, date) ' .
				'VALUES (%s, %s, %d, %s, NOW())',
				$local_path, $remote_path, $command, $last_error ) );
	}

	/**
	 * Returns array of array('local_path' => '', 'remote_path' => '') for specified file
	 *
	 * @param string  $file
	 * @return array
	 */
	function get_files_for_upload( $file ) {
		$files = array();
		$upload_info = Util_Http::upload_info();

		if ( $upload_info ) {
			$file = $this->normalize_attachment_file( $file );

			$local_file = $upload_info['basedir'] . '/' . $file;
			$remote_file = ltrim( $upload_info['baseurlpath'] . $file, '/' );

			$files[] = $this->build_file_descriptor( $local_file, $remote_file );
		}

		return $files;
	}

	/**
	 * Returns array of files from sizes array
	 *
	 * @param string  $attached_file
	 * @param array   $sizes
	 * @return array
	 */
	function _get_sizes_files( $attached_file, $sizes ) {
		$files = array();
		$base_dir = Util_File::dirname( $attached_file );

		foreach ( (array) $sizes as $size ) {
			if ( isset( $size['file'] ) ) {
				if ( $base_dir ) {
					$file = $base_dir . '/' . $size['file'];
				} else {
					$file = $size['file'];
				}

				$files = array_merge( $files, $this->get_files_for_upload( $file ) );
			}
		}

		return $files;
	}

	/**
	 * Returns attachment files by metadata
	 *
	 * @param array   $metadata
	 * @return array
	 */
	function get_metadata_files( $metadata ) {
		$files = array();

		if ( isset( $metadata['file'] ) && isset( $metadata['sizes'] ) ) {
			$files = array_merge( $files, $this->_get_sizes_files( $metadata['file'], $metadata['sizes'] ) );
		}

		return $files;
	}

	/**
	 * Returns attachment files by attachment ID
	 *
	 * @param integer $attachment_id
	 * @return array
	 */
	function get_attachment_files( $attachment_id ) {
		$files = array();

		/**
		 * Get attached file
		 */
		$attached_file = get_post_meta( $attachment_id, '_wp_attached_file', true );

		if ( $attached_file != '' ) {
			$files = array_merge( $files, $this->get_files_for_upload( $attached_file ) );

			/**
			 * Get backup sizes files
			 */
			$attachment_backup_sizes = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );

			if ( is_array( $attachment_backup_sizes ) ) {
				$files = array_merge( $files, $this->_get_sizes_files( $attached_file, $attachment_backup_sizes ) );
			}
		}

		/**
		 * Get files from metadata
		 */
		$attachment_metadata = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );

		if ( is_array( $attachment_metadata ) ) {
			$files = array_merge( $files, $this->get_metadata_files( $attachment_metadata ) );
		}

		return $files;
	}

	/**
	 * Uploads files to CDN
	 *
	 * @param array   $files
	 * @param boolean $queue_failed
	 * @param array   $results
	 * @return boolean
	 */
	function upload( $files, $queue_failed, &$results, $timeout_time = NULL ) {
		$cdn = $this->get_cdn();
		$force_rewrite = $this->_config->get_boolean( 'cdn.force.rewrite' );

		@set_time_limit( $this->_config->get_integer( 'timelimit.cdn_upload' ) );

		$engine = $this->_config->get_string( 'cdn.engine' );
		$return = $cdn->upload( $files, $results, $force_rewrite, $timeout_time );

		if ( !$return && $queue_failed ) {
			foreach ( $results as $result ) {
				if ( $result['result'] != W3TC_CDN_RESULT_OK ) {
					$this->queue_add( $result['local_path'], $result['remote_path'], W3TC_CDN_COMMAND_UPLOAD, $result['error'] );
				}
			}
		}

		return $return;
	}

	/**
	 * Deletes files frrom CDN
	 *
	 * @param array   $files
	 * @param boolean $queue_failed
	 * @param array   $results
	 * @return boolean
	 */
	function delete( $files, $queue_failed, &$results ) {
		$cdn = $this->get_cdn();

		@set_time_limit( $this->_config->get_integer( 'timelimit.cdn_delete' ) );

		$return = $cdn->delete( $files, $results );

		if ( !$return && $queue_failed ) {
			foreach ( $results as $result ) {
				if ( $result['result'] != W3TC_CDN_RESULT_OK ) {
					$this->queue_add( $result['local_path'], $result['remote_path'], W3TC_CDN_COMMAND_DELETE, $result['error'] );
				}
			}
		}

		return $return;
	}

	/**
	 * Purges files from CDN
	 *
	 * @param array   $files        consisting of array('local_path'=>'', 'remote_path'=>'')
	 * @param boolean $queue_failed
	 * @param array   $results
	 * @return boolean
	 */
	function purge( $files, $queue_failed, &$results ) {
		/**
		 * Purge varnish servers before mirror purging
		 */
		if ( Cdn_Util::is_engine_mirror( $this->_config->get_string( 'cdn.engine' ) ) && $this->_config->get_boolean( 'varnish.enabled' ) ) {
			$varnish = Dispatcher::component( 'Varnish_Flush' );

			foreach ( $files as $file ) {
				$remote_path = $file['remote_path'];
				$varnish->flush_url( network_site_url( $remote_path ) );
			}
		}

		/**
		 * Purge CDN
		 */
		$cdn = $this->get_cdn();

		@set_time_limit( $this->_config->get_integer( 'timelimit.cdn_purge' ) );

		$return = $cdn->purge( $files, $results );

		if ( !$return && $queue_failed ) {
			foreach ( $results as $result ) {
				if ( $result['result'] != W3TC_CDN_RESULT_OK ) {
					$this->queue_add( $result['local_path'], $result['remote_path'], W3TC_CDN_COMMAND_PURGE, $result['error'] );
				}
			}
		}

		return $return;
	}

	/**
	 * Purge CDN completely
	 *
	 * @param unknown $results
	 * @return mixed
	 */
	function purge_all( &$results ) {
		/**
		 * Purge CDN
		 */
		$cdn = $this->get_cdn();

		@set_time_limit( $this->_config->get_integer( 'timelimit.cdn_purge' ) );

		$return = $cdn->purge_all( $results );
		return $return;
	}

	/**
	 * Queues file upload.
	 * Links wp_cron call to do that by the end of request processing
	 *
	 * @param string  $url
	 * @return void
	 */
	function queue_upload_url( $url ) {
		$docroot_filename = Util_Environment::url_to_docroot_filename( $url );
		$filename = Util_Environment::document_root() . '/' . $docroot_filename;

		$a = parse_url( $url );
		$uri = $a['path'];

		$remote_file_name = $this->uri_to_cdn_uri( $uri );
		$this->queue_add( $filename, $remote_file_name,
			W3TC_CDN_COMMAND_UPLOAD, 'Pending' );
	}

	/**
	 * Normalizes attachment file
	 *
	 * @param string  $file
	 * @return string
	 */
	function normalize_attachment_file( $file ) {
		$upload_info = Util_Http::upload_info();
		if ( $upload_info ) {
			$file = ltrim( str_replace( $upload_info['basedir'], '', $file ), '/\\' );
			$matches = null;

			if ( preg_match( '~(\d{4}/\d{2}/)?[^/]+$~', $file, $matches ) ) {
				$file = $matches[0];
			}
		}

		return $file;
	}

	/**
	 * Returns CDN object
	 */
	function get_cdn() {
		static $cdn = array();

		if ( !isset( $cdn[0] ) ) {
			$engine = $this->_config->get_string( 'cdn.engine' );
			$compression = ( $this->_config->get_boolean( 'browsercache.enabled' ) && $this->_config->get_boolean( 'browsercache.html.compression' ) );

			switch ( $engine ) {
			case 'akamai':
				$engine_config = array(
					'username' => $this->_config->get_string( 'cdn.akamai.username' ),
					'password' => $this->_config->get_string( 'cdn.akamai.password' ),
					'zone' => $this->_config->get_string( 'cdn.akamai.zone' ),
					'domain' => $this->_config->get_array( 'cdn.akamai.domain' ),
					'ssl' => $this->_config->get_string( 'cdn.akamai.ssl' ),
					'email_notification' => $this->_config->get_array( 'cdn.akamai.email_notification' ),
					'compression' => false
				);
				break;

			case 'att':
				$engine_config = array(
					'account' => $this->_config->get_string( 'cdn.att.account' ),
					'token' => $this->_config->get_string( 'cdn.att.token' ),
					'domain' => $this->_config->get_array( 'cdn.att.domain' ),
					'ssl' => $this->_config->get_string( 'cdn.att.ssl' ),
					'compression' => false
				);
				break;

			case 'azure':
				$engine_config = array(
					'user' => $this->_config->get_string( 'cdn.azure.user' ),
					'key' => $this->_config->get_string( 'cdn.azure.key' ),
					'container' => $this->_config->get_string( 'cdn.azure.container' ),
					'cname' => $this->_config->get_array( 'cdn.azure.cname' ),
					'ssl' => $this->_config->get_string( 'cdn.azure.ssl' ),
					'compression' => false
				);
				break;

			case 'cf':
				$engine_config = array(
					'key' => $this->_config->get_string( 'cdn.cf.key' ),
					'secret' => $this->_config->get_string( 'cdn.cf.secret' ),
					'bucket' => $this->_config->get_string( 'cdn.cf.bucket' ),
					'bucket_location' => $this->_config->get_string( 'cdn.cf.bucket.location' ),
					'id' => $this->_config->get_string( 'cdn.cf.id' ),
					'cname' => $this->_config->get_array( 'cdn.cf.cname' ),
					'ssl' => $this->_config->get_string( 'cdn.cf.ssl' ),
					'compression' => $compression
				);
				break;

			case 'cf2':
				$engine_config = array(
					'key' => $this->_config->get_string( 'cdn.cf2.key' ),
					'secret' => $this->_config->get_string( 'cdn.cf2.secret' ),
					'id' => $this->_config->get_string( 'cdn.cf2.id' ),
					'cname' => $this->_config->get_array( 'cdn.cf2.cname' ),
					'ssl' => $this->_config->get_string( 'cdn.cf2.ssl' ),
					'compression' => false
				);
				break;

			case 'cotendo':
				$engine_config = array(
					'username' => $this->_config->get_string( 'cdn.cotendo.username' ),
					'password' => $this->_config->get_string( 'cdn.cotendo.password' ),
					'zones' => $this->_config->get_array( 'cdn.cotendo.zones' ),
					'domain' => $this->_config->get_array( 'cdn.cotendo.domain' ),
					'ssl' => $this->_config->get_string( 'cdn.cotendo.ssl' ),
					'compression' => false
				);
				break;

			case 'edgecast':
				$engine_config = array(
					'account' => $this->_config->get_string( 'cdn.edgecast.account' ),
					'token' => $this->_config->get_string( 'cdn.edgecast.token' ),
					'domain' => $this->_config->get_array( 'cdn.edgecast.domain' ),
					'ssl' => $this->_config->get_string( 'cdn.edgecast.ssl' ),
					'compression' => false
				);
				break;

			case 'ftp':
				$engine_config = array(
					'host' => $this->_config->get_string( 'cdn.ftp.host' ),
					'type' => $this->_config->get_string( 'cdn.ftp.type' ),
					'user' => $this->_config->get_string( 'cdn.ftp.user' ),
					'pass' => $this->_config->get_string( 'cdn.ftp.pass' ),
					'path' => $this->_config->get_string( 'cdn.ftp.path' ),
					'pasv' => $this->_config->get_boolean( 'cdn.ftp.pasv' ),
					'domain' => $this->_config->get_array( 'cdn.ftp.domain' ),
					'ssl' => $this->_config->get_string( 'cdn.ftp.ssl' ),
					'compression' => false,
					'docroot' => Util_Environment::document_root()
				);
				break;

			case 'google_drive':
				$state = Dispatcher::config_state();

				$engine_config = array(
					'client_id' =>
					$this->_config->get_string( 'cdn.google_drive.client_id' ),
					'access_token' =>
					$state->get_string( 'cdn.google_drive.access_token' ),
					'refresh_token' =>
					$this->_config->get_string( 'cdn.google_drive.refresh_token' ),
					'root_url' =>
					$this->_config->get_string( 'cdn.google_drive.folder.url' ),
					'root_folder_id' =>
					$this->_config->get_string( 'cdn.google_drive.folder.id' ),
					'new_access_token_callback' => array(
						$this,
						'on_google_drive_new_access_token'
					)
				);
				break;

			case 'highwinds':
				$state = Dispatcher::config_state();

				$engine_config = array(
					'domains' =>
					$this->_config->get_array( 'cdn.highwinds.host.domains' ),
					'ssl' =>
					$this->_config->get_string( 'cdn.highwinds.ssl' ),
					'api_token' =>
					$this->_config->get_string( 'cdn.highwinds.api_token' ),
					'account_hash' =>
					$this->_config->get_string( 'cdn.highwinds.account_hash' ),
					'host_hash_code' =>
					$this->_config->get_string( 'cdn.highwinds.host.hash_code' )
				);
				break;

			case 'maxcdn':
				$engine_config = array(
					'authorization_key' => $this->_config->get_string( 'cdn.maxcdn.authorization_key' ),
					'zone_id' => $this->_config->get_integer( 'cdn.maxcdn.zone_id' ),
					'domain' => $this->_config->get_array( 'cdn.maxcdn.domain' ),
					'ssl' => $this->_config->get_string( 'cdn.maxcdn.ssl' ),
					'compression' => false
				);
				break;

			case 'mirror':
				$engine_config = array(
					'domain' => $this->_config->get_array( 'cdn.mirror.domain' ),
					'ssl' => $this->_config->get_string( 'cdn.mirror.ssl' ),
					'compression' => false
				);
				break;

			case 'netdna':
				$engine_config = array(
					'authorization_key' => $this->_config->get_string( 'cdn.netdna.authorization_key' ),
					'zone_id' => $this->_config->get_integer( 'cdn.netdna.zone_id' ),
					'domain' => $this->_config->get_array( 'cdn.netdna.domain' ),
					'ssl' => $this->_config->get_string( 'cdn.netdna.ssl' ),
					'compression' => false
				);
				break;

			case 'rackspace_cdn':
				$state = Dispatcher::config_state();

				$engine_config = array(
					'user_name' => $this->_config->get_string( 'cdn.rackspace_cdn.user_name' ),
					'api_key' => $this->_config->get_string( 'cdn.rackspace_cdn.api_key' ),
					'region' => $this->_config->get_string( 'cdn.rackspace_cdn.region' ),
					'service_access_url' => $this->_config->get_string( 'cdn.rackspace_cdn.service.access_url' ),
					'service_id' => $this->_config->get_string( 'cdn.rackspace_cdn.service.id' ),
					'service_protocol' => $this->_config->get_string( 'cdn.rackspace_cdn.service.protocol' ),
					'domains' => $this->_config->get_array( 'cdn.rackspace_cdn.domains' ),
					'access_state' =>
					$state->get_string( 'cdn.rackspace_cdn.access_state' ),
					'new_access_state_callback' => array(
						$this,
						'on_rackspace_cdn_new_access_state'
					)

				);
				break;
			case 'rscf':
				$state = Dispatcher::config_state();

				$engine_config = array(
					'user_name' => $this->_config->get_string( 'cdn.rscf.user' ),
					'api_key' => $this->_config->get_string( 'cdn.rscf.key' ),
					'region' => $this->_config->get_string( 'cdn.rscf.location' ),
					'container' => $this->_config->get_string( 'cdn.rscf.container' ),
					'cname' => $this->_config->get_array( 'cdn.rscf.cname' ),
					'ssl' => $this->_config->get_string( 'cdn.rscf.ssl' ),
					'compression' => false,
					'access_state' =>
					$state->get_string( 'cdn.rackspace_cf.access_state' ),
					'new_access_state_callback' => array(
						$this,
						'on_rackspace_cf_new_access_state'
					)

				);
				break;

			case 's3':
				$engine_config = array(
					'key' => $this->_config->get_string( 'cdn.s3.key' ),
					'secret' => $this->_config->get_string( 'cdn.s3.secret' ),
					'bucket' => $this->_config->get_string( 'cdn.s3.bucket' ),
					'bucket_location' => $this->_config->get_string( 'cdn.s3.bucket.location' ),
					'cname' => $this->_config->get_array( 'cdn.s3.cname' ),
					'ssl' => $this->_config->get_string( 'cdn.s3.ssl' ),
					'compression' => $compression
				);
				break;

			case 's3_compatible':
				$engine_config = array(
					'key' => $this->_config->get_string( 'cdn.s3.key' ),
					'secret' => $this->_config->get_string( 'cdn.s3.secret' ),
					'bucket' => $this->_config->get_string( 'cdn.s3.bucket' ),
					'cname' => $this->_config->get_array( 'cdn.s3.cname' ),
					'ssl' => $this->_config->get_string( 'cdn.s3.ssl' ),
					'compression' => $compression,
					'api_host' => $this->_config->get_string( 'cdn.s3_compatible.api_host' )
				);
				break;
			}

			$engine_config = array_merge( $engine_config, array(
					'debug' => $this->_config->get_boolean( 'cdn.debug' )
				) );

			$cdn[0] = CdnEngine::instance( $engine, $engine_config );

			/**
			 * Set cache config for CDN
			 */
			if ( $this->_config->get_boolean( 'browsercache.enabled' ) ) {
				$w3_plugin_browsercache = Dispatcher::component( 'BrowserCache_Plugin' );
				$cdn[0]->cache_config = $w3_plugin_browsercache->get_cache_config();
			}
		}

		return $cdn[0];
	}

	/**
	 * Called when new access token is issued by cdnengine
	 */
	public function on_google_drive_new_access_token( $access_token ) {
		$state = Dispatcher::config_state();
		$state->set( 'cdn.google_drive.access_token', $access_token );
		$state->save();
	}

	/**
	 * Called when new access state is issued by cdnengine
	 */
	public function on_rackspace_cdn_new_access_state( $access_state ) {
		$state = Dispatcher::config_state();
		$state->set( 'cdn.rackspace_cdn.access_state', $access_state );
		$state->save();
	}

	/**
	 * Called when new access state is issued by cdnengine
	 */
	public function on_rackspace_cf_new_access_state( $access_state ) {
		$state = Dispatcher::config_state();
		$state->set( 'cdn.rackspace_cf.access_state', $access_state );
		$state->save();
	}

	/**
	 * Convert relative file which is relative to ABSPATH (wp folder on disc) to path uri
	 *
	 * @param unknown $file
	 * @return string
	 */
	function docroot_filename_to_uri( $file ) {
		$file = ltrim( $file, '/' );
		// Translate multisite subsite uploads paths
		$file = str_replace( basename( WP_CONTENT_DIR ) . '/blogs.dir/' .
			Util_Environment::blog_id() . '/', '', $file );
		return $file;

	}

	/**
	 * Convert a relative path (relative to ABSPATH (wp folder on disc) into a absolute path
	 *
	 * @param unknown $file
	 * @return string
	 */
	function docroot_filename_to_absolute_path( $file ) {
		if ( is_file( $file ) )
			return $file;

		if ( DIRECTORY_SEPARATOR != '/' )
			$file = str_replace( '/', DIRECTORY_SEPARATOR, $file );

		return  rtrim( Util_Environment::document_root(), '/\\' ) .
			DIRECTORY_SEPARATOR . ltrim( $file, '/\\' );
	}

	/**
	 * Convert local uri path to CDN type specific path
	 *
	 * @param unknown $local_uri_path
	 * @return string
	 */
	function uri_to_cdn_uri( $local_uri ) {
		$local_uri = ltrim( $local_uri, '/' );
		$remote_uri = $local_uri;

		if ( Util_Environment::is_wpmu() && defined( 'DOMAIN_MAPPING' ) && DOMAIN_MAPPING )
			$remote_uri = str_replace( site_url(), '', $local_uri );

		$engine = $this->_config->get_string( 'cdn.engine' );

		if ( Cdn_Util::is_engine_mirror( $engine ) ) {
			if ( Util_Environment::is_wpmu() && strpos( $local_uri, 'files' ) === 0 ) {
				$upload_dir = Util_Environment::wp_upload_dir();
				$remote_uri = $this->abspath_to_relative_path(
					dirname( $upload_dir['basedir'] ) ) . '/' . $local_uri;
			}
		}
		elseif ( Util_Environment::is_wpmu() &&
			!Util_Environment::is_wpmu_subdomain() &&
			Util_Environment::is_using_master_config() &&
			Cdn_Util::is_engine_push( $engine ) ) {
			// in common config files are uploaded for network home url
			// so mirror will not contain /subblog/ path in uri
			$home = trim( home_url( '', 'relative' ), '/' ) . '/';
			$network_home = trim( network_home_url( '', 'relative' ), '/' ) . '/';

			if ( $home != $network_home &&
				substr( $local_uri, 0, strlen( $home ) ) == $home ) {
				$remote_uri = $network_home . substr( $local_uri, strlen( $home ) );
			}
		}

		return ltrim( $remote_uri, '/' );
	}

	/**
	 * Returns the sitepath for multisite subfolder or subdomain path for multisite subdomain
	 *
	 * @return string
	 */
	private function _get_multisite_url_identifier() {
		if ( defined( 'DOMAIN_MAPPING' ) && DOMAIN_MAPPING ) {
			$parsedUrl = parse_url( site_url() );
			return $parsedUrl['host'];
		} elseif ( Util_Environment::is_wpmu_subdomain() ) {
			$parsedUrl = parse_url( Util_Environment::home_domain_root_url() );
			$urlparts = explode( '.', $parsedUrl['host'] );

			if ( sizeof( $urlparts ) > 2 ) {
				$subdomain = array_shift( $urlparts );
				return trim( $subdomain, '/' );
			}
		}
		return trim( Util_Environment::site_url_uri(), '/' );
	}

	/**
	 * Taks an absolute path and converts to a relative path to root
	 *
	 * @param unknown $path
	 * @return mixed
	 */
	function abspath_to_relative_path( $path ) {
		return str_replace( Util_Environment::document_root(), '', $path );
	}

	/**
	 * Takes a root relative path and converts to a full uri
	 *
	 * @param unknown $path
	 * @return string
	 */
	function relative_path_to_url( $path ) {
		$cdnuri = $this->docroot_filename_to_uri( ltrim( $path, "/" ) );
		return rtrim( Util_Environment::home_domain_root_url(), "/" ) . '/' . $cdnuri;
	}

	/**
	 * Constructs a CDN file descriptor
	 *
	 * @param unknown $local_path
	 * @param unknown $remote_path
	 * @return array
	 */
	function build_file_descriptor( $local_path, $remote_path ) {
		$file = array( 'local_path' => $local_path,
			'remote_path' => $remote_path,
			'original_url' => $this->relative_path_to_url( $local_path ) );

		$file = apply_filters( 'w3tc_build_cdn_file_array', $file );
		return $file;
	}
}
