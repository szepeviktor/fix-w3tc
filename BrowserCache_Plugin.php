<?php
namespace W3TC;

/**
 * W3 ObjectCache plugin
 */
class BrowserCache_Plugin {
	private $_config = null;
	private $browsercache_rewrite;

	function __construct() {
		$this->_config = Dispatcher::config();
	}

	/**
	 * Runs plugin
	 */
	function run() {
		add_filter( 'w3tc_admin_bar_menu',
			array( $this, 'w3tc_admin_bar_menu' ) );

		if ( $this->_config->get_boolean( 'browsercache.html.w3tc' ) ) {
			add_action( 'send_headers',
				array( $this, 'send_headers' ) );
		}

		if ( !$this->_config->get_boolean( 'browsercache.html.etag' ) ) {
			add_filter( 'wp_headers',
				array( $this, 'filter_wp_headers' ),
				0, 2 );
		}

		if ( $this->can_ob() ) {
			$this->browsercache_rewrite =
				$this->_config->get_boolean( 'browsercache.rewrite' );
			Util_Bus::add_ob_callback( 'browsercache', array( $this, 'ob_callback' ) );

			// modify CDN urls too
			add_filter( 'w3tc_cdn_url',
				array( $this, 'w3tc_cdn_url' ),
				0, 3 );
		}
		
		@ini_set( 'session.use_only_cookies', $this->_config->get_boolean( 'browsercache.security.session.use_only_cookies' ) ? '1' : '0' );
		@ini_set( 'session.cookie_httponly', $this->_config->get_boolean( 'browsercache.security.session.cookie_httponly' ) ? '1' : '0' );
		@ini_set( 'session.cookie_secure', $this->_config->get_boolean( 'browsercache.security.session.cookie_secure' ) ? '1' : '0' );
	}

	/**
	 * Check if we can start OB
	 *
	 * @return boolean
	 */
	function can_ob() {
		/**
		 * Replace feature should be enabled
		 */
		if ( !$this->_config->get_boolean( 'browsercache.cssjs.replace' ) &&
			!$this->_config->get_boolean( 'browsercache.html.replace' ) &&
			!$this->_config->get_boolean( 'browsercache.other.replace' ) &&
			!$this->_config->get_boolean( 'browsercache.cssjs.querystring' ) &&
			!$this->_config->get_boolean( 'browsercache.html.querystring' ) &&
			!$this->_config->get_boolean( 'browsercache.other.querystring' )) {
			return false;
		}

		/**
		 * Skip if admin
		 */
		if ( defined( 'WP_ADMIN' ) ) {
			return false;
		}

		/**
		 * Skip if doing AJAX
		 */
		if ( defined( 'DOING_AJAX' ) ) {
			return false;
		}

		/**
		 * Skip if doing cron
		 */
		if ( defined( 'DOING_CRON' ) ) {
			return false;
		}

		/**
		 * Skip if APP request
		 */
		if ( defined( 'APP_REQUEST' ) ) {
			return false;
		}

		/**
		 * Skip if XMLRPC request
		 */
		if ( defined( 'XMLRPC_REQUEST' ) ) {
			return false;
		}

		/**
		 * Check for WPMU's and WP's 3.0 short init
		 */
		if ( defined( 'SHORTINIT' ) && SHORTINIT ) {
			return false;
		}

		/**
		 * Check User Agent
		 */
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) &&
			stristr( $_SERVER['HTTP_USER_AGENT'], W3TC_POWERED_BY ) !== false ) {
			return false;
		}

		return true;
	}

	/**
	 * Output buffer callback
	 *
	 * @param string  $buffer
	 * @return mixed
	 */
	function ob_callback( $buffer ) {
		if ( $buffer != '' && Util_Content::is_html_xml( $buffer ) ) {
			$domain_url_regexp = Util_Environment::home_domain_root_url_regexp();

			$buffer = preg_replace_callback(
				'~(href|src|action|extsrc|asyncsrc|w3tc_load_js\()=?([\'"])((' .
				$domain_url_regexp .
				')?(/[^\'"/][^\'"]*\.([a-z-_]+)([\?#][^\'"]*)?))[\'"]~Ui', array(
					$this,
					'link_replace_callback'
				), $buffer );
		}

		return $buffer;
	}

	/**
	 * Link replace callback
	 *
	 * @param string  $matches
	 * @return string
	 */
	function link_replace_callback( $matches ) {
		list ( $match, $attr, $quote, $url, , , , , $extension ) = $matches;

		$ops = $this->_get_url_mutation_operations( $url, $extension );
		if ( is_null( $ops ) )
			return $match;

		$url = $this->mutate_url( $url, $ops, !$this->browsercache_rewrite );

		if ( $attr != 'w3tc_load_js(' )
			return $attr . '=' . $quote . $url . $quote;
		return sprintf( '%s\'%s\'', $attr, $url );
	}

	/**
	 * Link replace for CDN url
	 *
	 * @param string  $matches
	 * @return string
	 */
	function w3tc_cdn_url( $url, $original_url, $is_cdn_mirror ) {
		// decouple extension
		$matches = array();
		if ( !preg_match( '/\.([a-zA-Z0-9]+)($|[\?])/', $original_url, $matches ) )
			return $url;
		$extension = $matches[1];

		$ops = $this->_get_url_mutation_operations( $original_url, $extension );
		if ( is_null( $ops ) )
			return $url;

		// for push cdns each flush would require manual reupload of files
		$mutate_by_querystring = !$this->browsercache_rewrite || !$is_cdn_mirror;

		$url = $this->mutate_url( $url, $ops, $mutate_by_querystring );
		return $url;
	}

	private function mutate_url( $url, $ops, $mutate_by_querystring ) {
		$query_pos = strpos( $url, '?' );
		if ( isset( $ops['querystring'] ) && $query_pos !== false ) {
			$url = substr( $url, 0, $query_pos );
			$query_pos = false;
		}

		if ( isset( $ops['replace'] ) ) {
			$id = $this->get_filename_uniqualizator();

			if ( $mutate_by_querystring ) {
				if ( $query_pos !== false ) {
					$url = substr( $url, 0, $query_pos + 1 ) . $id . '&amp;' .
						substr( $url, $query_pos + 1 );
				} else {
					$tag_pos = strpos( $url, '#' );
					if ( $tag_pos === false ) {
						$url .= '?' . $id;
					} else {
						$url = substr( $url, 0, $tag_pos ) . '?' . $id .
							substr( $url, $tag_pos );
					}
				}

			} else {
				// add $id to url before extension

				$url_query = '';
				if ( $query_pos !== false ) {
					$url_query = substr( $url, $query_pos );
					$url = substr( $url, 0, $query_pos );
				}

				$ext_pos = strrpos( $url, '.' );
				$extension = substr( $url, $ext_pos );

				$url = substr( $url, 0, strlen( $url ) - strlen( $extension ) ) .
					'.' . $id . $extension . $url_query;
			}
		}

		return $url;
	}

	function _get_url_mutation_operations( $url, $extension ) {
		static $extensions = null;
		if ( $extensions === null ) {
			$core = Dispatcher::component( 'BrowserCache_Core' );
			$extensions = $core->get_replace_querystring_extensions( $this->_config );
		}

		static $exceptions = null;
		if ( $exceptions === null )
			$exceptions = $this->_config->get_array( 'browsercache.replace.exceptions' );

		if ( !isset( $extensions[$extension] ) )
			return null;

		$test_url = Util_Environment::remove_query( $url );
		foreach ( $exceptions as $exception ) {
			$escaped = str_replace( '~', '\~', $exception );
			if ( trim( $exception ) && preg_match( '~' . $escaped . '~', $test_url ) )
				return null;
		}

		return $extensions[$extension];
	}

	/**
	 * Returns replace ID
	 *
	 * @return string
	 */
	function get_filename_uniqualizator() {
		static $cache_id = null;

		if ( $cache_id === null ) {
			$value = get_option( 'w3tc_browsercache_flush_timestamp' );

			if ( empty( $value ) ) {
				$value = rand( 10000, 99999 ) . '';
				update_option( 'w3tc_browsercache_flush_timestamp', $value );
			}

			$cache_id = substr( $value, 0, 5 );
		}

		return 'x' . $cache_id;
	}

	public function w3tc_admin_bar_menu( $menu_items ) {
		$browsercache_update_media_qs =
			( $this->_config->get_boolean( 'browsercache.cssjs.replace' ) ||
			$this->_config->get_boolean( 'browsercache.other.replace' ) );

		if ( $browsercache_update_media_qs ) {
			$menu_items['20190.browsercache'] = array(
				'id' => 'w3tc_flush_browsercache',
				'parent' => 'w3tc_flush',
				'title' => __( 'Browser Cache: Update Media Query String', 'w3-total-cache' ),
				'href' => wp_nonce_url( network_admin_url(
						'admin.php?page=w3tc_dashboard&amp;w3tc_flush_browser_cache' ),
					'w3tc' )
			);
		}

		return $menu_items;
	}

	/**
	 * Send headers
	 */
	function send_headers() {
		@header( 'X-Powered-By: ' . Util_Environment::w3tc_header() );
	}

	/**
	 * Returns cache config for CDN
	 *
	 * @return array
	 */
	function get_cache_config() {
		$config = array();

		$e = Dispatcher::component( 'BrowserCache_Environment' );
		$mime_types = $e->get_mime_types();

		foreach ( $mime_types as $type => $extensions )
			$this->_get_cache_config( $config, $extensions, $type );

		return $config;
	}

	/**
	 * Writes cache config
	 *
	 * @param string  $config
	 * @param array   $mime_types
	 * @param array   $section
	 * @return void
	 */
	function _get_cache_config( &$config, $mime_types, $section ) {
		$expires = $this->_config->get_boolean( 'browsercache.' . $section . '.expires' );
		$lifetime = $this->_config->get_integer( 'browsercache.' . $section . '.lifetime' );
		$cache_control = $this->_config->get_boolean( 'browsercache.' . $section . '.cache.control' );
		$cache_policy = $this->_config->get_string( 'browsercache.' . $section . '.cache.policy' );
		$etag = $this->_config->get_boolean( 'browsercache.' . $section . '.etag' );
		$w3tc = $this->_config->get_boolean( 'browsercache.' . $section . '.w3tc' );

		foreach ( $mime_types as $mime_type ) {
			if ( is_array( $mime_type ) ) {
				foreach ( $mime_type as $mime_type2 )
					$config[$mime_type2] = array(
						'etag' => $etag,
						'w3tc' => $w3tc,
						'lifetime' => $lifetime,
						'expires' => $expires,
						'cache_control' => ( $cache_control ? $cache_policy : false )
					);
			} else
				$config[$mime_type] = array(
					'etag' => $etag,
					'w3tc' => $w3tc,
					'lifetime' => $lifetime,
					'expires' => $expires,
					'cache_control' => ( $cache_control ? $cache_policy : false )
				);
		}
	}

	/**
	 * Filters headers set by WordPress
	 *
	 * @param unknown $headers
	 * @param unknown $wp
	 * @return
	 */
	function filter_wp_headers( $headers, $wp ) {
		if ( !empty( $wp->query_vars['feed'] ) )
			unset( $headers['ETag'] );
		return $headers;
	}
}
