<?php
namespace W3TC;

/**
 * Interplugin communication
 */
class Dispatcher {
	/**
	 * return component instance
	 */
	static public function component( $class ) {
		static $instances = array();

		if ( !isset( $instances[$class] ) ) {
			$full_class = '\\W3TC\\' . $class;
			$instances[$class] = new $full_class();
		}

		$v = $instances[$class];   // Don't return reference
		return $v;
	}

	static public function config() {
		return self::component( 'Config' );
	}

	static public function config_master() {
		static $config_master = null;

		if ( is_null( $config_master ) ) {
			$config_master = new Config( 0 );
		}

		return $config_master;
	}

	static public function config_state() {
		if ( Util_Environment::blog_id() <= 0 )
			return self::config_state_master();

		static $config_state = null;

		if ( is_null( $config_state ) )
			$config_state = new ConfigState( false );

		return $config_state;
	}

	static public function config_state_master() {
		static $config_state = null;

		if ( is_null( $config_state ) )
			$config_state = new ConfigState( true );

		return $config_state;
	}

	static public function config_state_note() {
		static $o = null;

		if ( is_null( $o ) )
			$o = new ConfigStateNote( self::config_state_master(),
				self::config_state() );

		return $o;
	}

	/**
	 * Checks if specific local url is uploaded to CDN
	 *
	 * @param string  $url
	 * @return bool
	 */
	static public function is_url_cdn_uploaded( $url ) {
		$minify_enabled = self::config()->get_boolean( 'minify.enabled' );
		if ( $minify_enabled ) {
			$minify = Dispatcher::component( 'Minify_MinifiedFileRequestHandler' );
			$data = $minify->get_url_custom_data( $url );
			if ( is_array( $data ) && isset( $data['cdn.status'] ) && $data['cdn.status'] == 'uploaded' ) {
				return true;
			}
		}
		// supported only for minify-based urls, futher is not needed now
		return false;
	}

	/**
	 * Creates file for CDN upload.
	 * Needed because minify can handle urls of non-existing files but CDN needs
	 * real file to upload it
	 */
	static public function create_file_for_cdn( $filename ) {
		$minify_enabled = self::config()->get_boolean( 'minify.enabled' );
		if ( $minify_enabled ) {
			$minify_document_root = Util_Environment::cache_blog_dir( 'minify' ) . '/';

			if ( !substr( $filename, 0, strlen( $minify_document_root ) ) == $minify_document_root ) {
				// unexpected file name
				return;
			}

			$short_filename = substr( $filename, strlen( $minify_document_root ) );
			$minify = Dispatcher::component( 'Minify_MinifiedFileRequestHandler' );

			$data = $minify->process( $short_filename, true );

			if ( !file_exists( $filename ) &&
				isset( $data['content'] ) ) {

				if ( !file_exists( dirname( $filename ) ) )
					Util_File::mkdir_from( dirname( $filename ), W3TC_CACHE_DIR );
			}
			@file_put_contents( $filename, $data['content'] );
		}
	}

	/**
	 * Called on successful file upload to CDN
	 *
	 * @param unknown $file_name
	 */
	static public function on_cdn_file_upload( $file_name ) {
		$minify_enabled = self::config()->get_boolean( 'minify.enabled' );
		if ( $minify_enabled ) {
			$minify_document_root = Util_Environment::cache_blog_dir( 'minify' ) . '/';

			if ( !substr( $file_name, 0, strlen( $minify_document_root ) ) == $minify_document_root ) {
				// unexpected file name
				return;
			}

			$short_file_name = substr( $file_name, strlen( $minify_document_root ) );
			$minify = Dispatcher::component( 'Minify_MinifiedFileRequestHandler' );
			$minify->set_file_custom_data( $short_file_name,
				array( 'cdn.status' => 'uploaded' ) );
		}
	}

	/**
	 * Generates canonical header code for nginx if browsercache plugin has
	 * to generate it
	 *
	 * @param Config  $config
	 * @param boolean $cdnftp  if CDN FTP is used
	 * @param string  $section
	 * @return string
	 */
	static public function on_browsercache_rules_generation_for_section( $config, $cdnftp,
		$section, $add_header_rules ) {
		if ( $section != 'other' )
			return '';
		if ( self::canonical_generated_by( $config, $cdnftp ) != 'browsercache' )
			return '';

		return Util_RuleSnippet::canonical_without_location( $cdnftp,
			$add_header_rules );
	}

	/**
	 * Called when minify going to process request of some minified file
	 */
	static public function requested_minify_filename( $config, $file ) {
		// browsercache may alter filestructure, allow it to remove its
		// uniqualizator
		if ( $config->get_boolean( 'browsercache.enabled' ) &&
			$config->get_boolean( 'browsercache.rewrite' ) ) {
			if ( preg_match( '~(.+)\.([0-9a-z]+)(\.[^.]+)$~', $file, $m ) )
				$file = $m[1] . $m[3];
		}
		return $file;
	}

	/**
	 * Checks whether canonical should be generated or not by browsercache plugin
	 *
	 * @param Config  $config
	 * @param boolean $cdnftp
	 * @return string|null
	 */
	static public function canonical_generated_by( $config, $cdnftp = false ) {
		if ( !self::_should_canonical_be_generated( $config, $cdnftp ) )
			return null;

		if ( Util_Environment::is_nginx() ) {
			// in nginx - browsercache generates canonical if its enabled,
			// since it does not allow multiple location tags
			if ( $config->get_boolean( 'browsercache.enabled' ) )
				return 'browsercache';
		}

		if ( $config->get_boolean( 'cdn.enabled' ) )
			return 'cdn';

		return null;
	}

	/**
	 * Basic check if canonical generation should be done
	 *
	 * @param Config  $config
	 * @param boolean $cdnftp
	 * @return bool
	 */
	static private function _should_canonical_be_generated( $config, $cdnftp ) {
		if ( !$config->get_boolean( 'cdn.canonical_header' ) )
			return false;

		$cdncommon = Dispatcher::component( 'Cdn_Core' );

		$cdn = $cdncommon->get_cdn();
		return ( ( $config->get_string( 'cdn.engine' ) != 'ftp' || $cdnftp ) &&
			$cdn->headers_support() == W3TC_CDN_HEADER_MIRRORING );
	}

	/**
	 * Checks whether canonical should be generated or not by browsercache plugin
	 *
	 * @param Config  $config
	 * @return string|null
	 */
	static public function allow_origin_generated_by( $config ) {
		if ( $config->get_boolean( 'cdn.enabled' ) )
			return 'cdn';

		return null;
	}

	/**
	 * If BrowserCache should generate rules specific for CDN. Used with CDN FTP
	 *
	 * @param Config  $config
	 * @return boolean;
	 */
	static public function should_browsercache_generate_rules_for_cdn( $config ) {
		if ( $config->get_boolean( 'cdn.enabled' ) &&
			$config->get_string( 'cdn.engine' ) == 'ftp' ) {
			$cdncommon = Dispatcher::component( 'Cdn_Core' );
			$cdn = $cdncommon->get_cdn();
			$domain = $cdn->get_domain();

			if ( $domain )
				return true;
		}
		return false;
	}

	/**
	 * Returns the domain used with the cdn.
	 *
	 * @param string
	 * @return string
	 */
	static public function get_cdn_domain( $path = '' ) {
		$cdncommon = Dispatcher::component( 'Cdn_Core' );
		$cdn = $cdncommon->get_cdn();
		return $cdn->get_domain( $path );
	}

	/**
	 * Usage statistics uses one of other module's cache
	 * to store its temporary data
	 */
	static public function get_usage_statistics_cache() {
		static $cache = null;
		if ( is_null( $cache ) ) {
			$c = Dispatcher::config();
			if ( $c->get_boolean( 'objectcache.enabled' ) )
				$provider = Dispatcher::component( 'ObjectCache_WpObjectCache_Regular' );
			else if ( $c->get_boolean( 'dbcache.enabled' ) )
					$provider = Dispatcher::component( 'DbCache_Core' );
				else if ( $c->get_boolean( 'pgcache.enabled' ) )
						$provider = Dispatcher::component( 'PgCache_ContentGrabber' );
					else if ( $c->get_boolean( 'minify.enabled' ) )
							$provider = Dispatcher::component( 'Minify_Core' );
						else
							return null;

						$engineConfig = $provider->get_usage_statistics_cache_config();
					$engineConfig['module'] = 'stats';
				$engineConfig['blog_id'] = 0;   // count wpmu-wide stats

			if ( $engineConfig['engine'] == 'file' ) {
				$engineConfig['cache_dir'] = Util_Environment::cache_dir( 'stats' );
			}

			$cache = Cache::instance( $engineConfig['engine'],
				$engineConfig );
		}

		return $cache;
	}

	/**
	 * In a case request processing has been finished before WP initialized,
	 * but usage statistics metrics should be counted.
	 * To work properly $metrics_function has to be added also by plugin
	 * when add_action is available.
	 */
	static public function usage_statistics_apply_before_init_and_exit(
		$metrics_function ) {
		$c = Dispatcher::config();
		if ( !$c->get_boolean( 'stats.enabled' ) )
			exit();

		$core = Dispatcher::component( 'UsageStatistics_Core' );
		$core->apply_metrics_before_init_and_exit( $metrics_function );
	}
}
