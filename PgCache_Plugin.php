<?php
namespace W3TC;

/**
 * W3 PgCache plugin
 */
class PgCache_Plugin {
	/**
	 * Config
	 */
	private $_config = null;

	function __construct() {
		$this->_config = Dispatcher::config();
	}

	/**
	 * Runs plugin
	 */
	function run() {
		add_action( 'w3tc_flush_all',
			array( $this, 'w3tc_flush_posts' ),
			1100, 1 );
		add_action( 'w3tc_flush_post',
			array( $this, 'w3tc_flush_post' ),
			1100, 1 );
		add_action( 'w3tc_flushable_posts',
			'__return_true',
			1100 );
		add_action( 'w3tc_flush_posts',
			array( $this, 'w3tc_flush_posts' ),
			1100 );
		add_action( 'w3tc_flush_url',
			array( $this, 'w3tc_flush_url' ),
			1100, 1 );
		add_filter( 'w3tc_admin_bar_menu',
			array( $this, 'w3tc_admin_bar_menu' ) );

		add_filter( 'cron_schedules',
			array( $this, 'cron_schedules' ) );

		$o = Dispatcher::component( 'PgCache_ContentGrabber' );

		add_filter( 'w3tc_footer_comment',
			array( $o, 'w3tc_footer_comment' ) );

		add_action( 'w3tc_usage_statistics_of_request',
			array( $o, 'w3tc_usage_statistics_of_request' ),
			10, 1 );
		add_filter( 'w3tc_usage_statistics_metrics',
			array( $this, 'w3tc_usage_statistics_metrics' ) );


		if ( $this->_config->get_string( 'pgcache.engine' ) == 'file' ||
			$this->_config->get_string( 'pgcache.engine' ) == 'file_generic' ) {
			add_action( 'w3_pgcache_cleanup',
				array( $this, 'cleanup' ) );
		}

		add_action( 'w3_pgcache_prime', array( $this, 'prime' ) );
        
        add_action( 'w3_pgcache_prime_cli', array( $this, 'prime_cli' ), 10, 4 );

		Util_AttachToActions::flush_posts_on_actions();

		add_filter( 'comment_cookie_lifetime',
			array( $this, 'comment_cookie_lifetime' ) );

		if ( $this->_config->get_string( 'pgcache.engine' ) == 'file_generic' ) {
			add_action( 'wp_logout',
				array( $this, 'on_logout' ),
				0 );

			add_action( 'wp_login',
				array( $this, 'on_login' ),
				0 );
		}

		if ( $this->_config->get_boolean( 'pgcache.prime.post.enabled', false ) ) {
			add_action( 'publish_post',
				array( $this, 'prime_post' ),
				30 );
		}

		if ( ( $this->_config->get_boolean( 'pgcache.late_init' ) ||
				$this->_config->get_boolean( 'pgcache.late_caching' ) ) &&
			!is_admin() ) {
			$o = Dispatcher::component( 'PgCache_ContentGrabber' );
			add_action( 'init',
				array( $o, 'delayed_cache_print' ),
				99999 );
		}

		if ( !$this->_config->get_boolean( 'pgcache.mirrors.enabled' ) &&
			!Util_Environment::is_wpmu_subdomain() ) {
			add_action( 'init',
				array( $this, 'redirect_on_foreign_domain' ) );
		}
	}

	/**
	 * Does disk cache cleanup
	 *
	 * @return void
	 */
	function cleanup() {
		$this->_get_admin()->cleanup();
	}

	/**
	 * Prime cache
	 *
	 * @param integer $start
	 * @return void
	 */
	function prime( $start = 0 ) {
		$this->_get_admin()->prime( $start );
	}

    /**
     * Prime cache (WP_CLI)
     *
     * @param   integer $user_limit     Pages per batch size
     * @param   integer $user_interval  Number of seconds to wait before creating another batch
     * @param   string  $user_sitemap   The sitemap url to use
     * @param   integer $start          Index position within the sitemap to prime cache
     * @param   boolean $boot           Indicates if the prime cache is about to start for the first time
     * @return  void
     */
    function prime_cli( $user_limit, $user_interval, $user_sitemap, $start, $boot = false ) {
        $this->_get_admin()->prime_cli( $user_limit, $user_interval, $user_sitemap, $start, $boot );
    }

	/**
	 * Instantiates worker on demand
	 */
	private function _get_admin() {
		return Dispatcher::component( 'PgCache_Plugin_Admin' );
	}

	/**
	 * Cron schedules filter
	 *
	 * @param array   $schedules
	 * @return array
	 */
	function cron_schedules( $schedules ) {
		$c = $this->_config;

		if ( $c->get_boolean( 'pgcache.enabled' ) &&
			( $c->get_string( 'pgcache.engine' ) == 'file' ||
				$c->get_string( 'pgcache.engine' ) == 'file_generic' ) ) {
			$v = $c->get_integer( 'pgcache.file.gc' );
			$schedules['w3_pgcache_cleanup'] = array(
				'interval' => $v,
				'display' => sprintf( '[W3TC] Page Cache file GC (every %d seconds)',
					$v )
			);
		}

		if ( $c->get_boolean( 'pgcache.enabled' ) &&
			$c->get_boolean( 'pgcache.prime.enabled' ) ) {
			$v = $c->get_integer( 'pgcache.prime.interval' );
			$schedules['w3_pgcache_prime'] = array(
				'interval' => $v,
				'display' => sprintf( '[W3TC] Page Cache prime (every %d seconds)',
					$v )
			);
		}

		return $schedules;
	}

	public function redirect_on_foreign_domain() {
		$request_host = Util_Environment::host();
		// host not known, potentially we are in console mode not http request
		if ( empty( $request_host ) )
			return;

		$home_url = get_home_url();
		$parsed_url = @parse_url( $home_url );

		if ( isset( $parsed_url['host'] ) &&
			strtolower( $parsed_url['host'] ) != strtolower( $request_host ) ) {
			$redirect_url = $parsed_url['scheme'] . '://';
			if ( !empty( $parsed_url['user'] ) ) {
				$redirect_url .= $parsed_url['user'];
				if ( !empty( $parsed_url['pass'] ) )
					$redirect_url .= ':' . $parsed_url['pass'];
			}
			if ( !empty( $parsed_url['host'] ) )
				$redirect_url .= $parsed_url['host'];

			if ( !empty( $parsed_url['port'] ) && $parsed_url['port'] != 80 ) {
				$redirect_url .= ':' . (int)$parsed_url['port'];
			}

			$redirect_url .= $_SERVER['REQUEST_URI'];

			//echo $redirect_url;
			wp_redirect( $redirect_url, 301 );
			exit();
		}
	}

	/**
	 *
	 *
	 * @param integer $lifetime
	 * @return integer
	 */
	function comment_cookie_lifetime( $lifetime ) {
		$l = $this->_config->get_integer( 'pgcache.comment_cookie_ttl' );
		if ( $l != -1 )
			return $l;
		else
			return $lifetime;
	}

	/**
	 * Add cookie on logout to circumvent pagecache due to browser cache resulting in 304s
	 */
	function on_logout() {
		setcookie( 'w3tc_logged_out' );
	}

	/**
	 * Remove logout cookie on logins
	 */
	function on_login() {
		if ( isset( $_COOKIE['w3tc_logged_out'] ) )
			setcookie( 'w3tc_logged_out', '', 1 );
	}

	/**
	 *
	 *
	 * @param unknown $post_id
	 * @return boolean
	 */
	function prime_post( $post_id ) {
		$w3_pgcache = Dispatcher::component( 'CacheFlush' );
		return $w3_pgcache->prime_post( $post_id );
	}

	public function w3tc_usage_statistics_metrics( $metrics ) {
		return array_merge( $metrics, array(
				'pagecache_requests_total', 'pagecache_requests_hits',
				'pagecache_requests_time_10ms' ) );
	}

	public function w3tc_admin_bar_menu( $menu_items ) {
		$menu_items['20110.pagecache'] = array(
			'id' => 'w3tc_flush_pgcache',
			'parent' => 'w3tc_flush',
			'title' => __( 'Page Cache: All', 'w3-total-cache' ),
			'href' => wp_nonce_url( network_admin_url(
					'admin.php?page=w3tc_dashboard&amp;w3tc_flush_pgcache' ),
				'w3tc' )
		);

		if ( Util_Environment::detect_post_id() && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) ) {
			$menu_items['20120.pagecache'] = array(
				'id' => 'w3tc_pgcache_flush_post',
				'parent' => 'w3tc_flush',
				'title' => __( 'Page Cache: Current Page', 'w3-total-cache' ),
				'href' => wp_nonce_url( admin_url(
						'admin.php?page=w3tc_dashboard&amp;w3tc_flush_post&amp;post_id=' .
						Util_Environment::detect_post_id() ), 'w3tc' )
			);
		}

		return $menu_items;
	}

	/**
	 * Flushes all caches
	 *
	 * @return boolean
	 */
	function w3tc_flush_posts( $extras = array() ) {
		if ( isset( $extras['only'] ) && $extras['only'] != 'pagecache' )
			return;

		$pgcacheflush = Dispatcher::component( 'PgCache_Flush' );
		$v = $pgcacheflush->flush();

		return $v;
	}

	/**
	 * Flushes post cache
	 *
	 * @param integer $post_id
	 * @return boolean
	 */
	function w3tc_flush_post( $post_id ) {
		$pgcacheflush = Dispatcher::component( 'PgCache_Flush' );
		$v = $pgcacheflush->flush_post( $post_id );

		return $v;
	}

	/**
	 * Flushes post cache
	 *
	 * @param string  $url
	 * @return boolean
	 */
	function w3tc_flush_url( $url ) {
		$pgcacheflush = Dispatcher::component( 'PgCache_Flush' );
		$v = $pgcacheflush->flush_url( $url );

		return $v;
	}
}
