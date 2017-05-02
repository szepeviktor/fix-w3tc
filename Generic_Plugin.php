<?php
namespace W3TC;

/**
 * W3 Total Cache plugin
 */
class Generic_Plugin {

	private $_translations = array();
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
		add_filter( 'cron_schedules', array(
				$this,
				'cron_schedules'
			), 5 );

		add_action( 'init', array(
				$this,
				'init'
			), 1 /* need that to run before wp-cron to issue w3tc redirect */ );
		if ( Util_Environment::is_w3tc_pro_dev() && Util_Environment::is_w3tc_pro( $this->_config ) )
			add_action( 'wp_footer', array( $this, 'pro_dev_mode' ) );

		add_action( 'admin_bar_menu', array(
				$this,
				'admin_bar_menu'
			), 150 );

		if ( isset( $_REQUEST['w3tc_theme'] ) && isset( $_SERVER['HTTP_USER_AGENT'] ) &&
			stristr( $_SERVER['HTTP_USER_AGENT'], W3TC_POWERED_BY ) !== false ) {
			add_filter( 'template', array(
					$this,
					'template_preview'
				) );

			add_filter( 'stylesheet', array(
					$this,
					'stylesheet_preview'
				) );
		} elseif ( $this->_config->get_boolean( 'mobile.enabled' ) || $this->_config->get_boolean( 'referrer.enabled' ) ) {
			add_filter( 'template', array(
					$this,
					'template'
				) );

			add_filter( 'stylesheet', array(
					$this,
					'stylesheet'
				) );
		}

		/**
		 * Create cookies to flag if a pgcache role was loggedin
		 */
		if ( !$this->_config->get_boolean( 'pgcache.reject.logged' ) && $this->_config->get_array( 'pgcache.reject.logged_roles' ) ) {
			add_action( 'set_logged_in_cookie', array(
					$this,
					'check_login_action'
				), 0, 5 );
			add_action( 'clear_auth_cookie', array(
					$this,
					'check_login_action'
				), 0, 5 );
		}

		if ( $this->_config->get_string( 'common.support' ) == 'footer' ) {
			add_action( 'wp_footer', array(
					$this,
					'footer'
				) );
		}

		if ( $this->can_ob() ) {
			ob_start( array(
					$this,
					'ob_callback'
				) );
		}
	}

	/**
	 * Cron schedules filter
	 *
	 * @param array   $schedules
	 * @return array
	 */
	function cron_schedules( $schedules ) {
		// Sets default values which are overriden by apropriate plugins
		// if they are enabled
		//
		// absense of keys (if e.g. pgcaching became disabled, but there is
		// cron event scheduled in db) causes PHP notices
		return array_merge( $schedules, array(
				'w3_cdn_cron_queue_process' => array(
					'interval' => 0,
					'display' => '[W3TC] CDN queue process (disabled)'
				),
				'w3_cdn_cron_upload' => array(
					'interval' => 0,
					'display' => '[W3TC] CDN auto upload (disabled)'
				),
				'w3_dbcache_cleanup' => array(
					'interval' => 0,
					'display' => '[W3TC] Database Cache file GC (disabled)'
				),
				'w3_fragmentcache_cleanup' => array(
					'interval' => 0,
					'display' => '[W3TC] Fragment Cache file GC (disabled)'
				),
				'w3_minify_cleanup' => array(
					'interval' => 0,
					'display' => '[W3TC] Minify file GC (disabled)'
				),
				'w3_objectcache_cleanup' => array(
					'interval' => 0,
					'display' => '[W3TC] Object Cache file GC (disabled)'
				),
				'w3_pgcache_cleanup' => array(
					'interval' => 0,
					'display' => '[W3TC] Page Cache file GC (disabled)'
				),
				'w3_pgcache_prime' => array(
					'interval' => 0,
					'display' => '[W3TC] Page Cache file GC (disabled)'
				)
			) );
	}

	/**
	 * Init action
	 *
	 * @return void
	 */
	function init() {
		// Load plugin text domain
		load_plugin_textdomain( W3TC_TEXT_DOMAIN, null, plugin_basename( W3TC_DIR ) . '/languages/' );

		if ( is_multisite() && !is_network_admin() ) {
			global $w3_current_blog_id, $current_blog;
			if ( $w3_current_blog_id != $current_blog->blog_id && !isset( $GLOBALS['w3tc_blogmap_register_new_item'] ) ) {
				$url = Util_Environment::host_port() . $_SERVER['REQUEST_URI'];
				$pos = strpos( $url, '?' );
				if ( $pos !== false )
					$url = substr( $url, 0, $pos );
				$GLOBALS['w3tc_blogmap_register_new_item'] = $url;
			}
		}

		if ( isset( $GLOBALS['w3tc_blogmap_register_new_item'] ) ) {
			$do_redirect = false;
			// true value is a sign to just generate config cache
			if ( $GLOBALS['w3tc_blogmap_register_new_item'] != 'cache_options' ) {
				if ( Util_Environment::is_wpmu_subdomain() )
					$blog_home_url = $GLOBALS['w3tc_blogmap_register_new_item'];
				else {
					$home_url = rtrim( get_home_url(), '/' );
					if ( substr( $home_url, 0, 7 ) == 'http://' )
						$home_url = substr( $home_url, 7 );
					else if ( substr( $home_url, 0, 8 ) == 'https://' )
							$home_url = substr( $home_url, 8 );

						if ( substr( $GLOBALS['w3tc_blogmap_register_new_item'], 0,
								strlen( $home_url ) ) == $home_url )
							$blog_home_url = $home_url;
						else
							$blog_home_url = $GLOBALS['w3tc_blogmap_register_new_item'];
				}


				$do_redirect = Util_WpmuBlogmap::register_new_item( $blog_home_url,
					$this->_config );

				// reset cache of blog_id
				global $w3_current_blog_id;
				$w3_current_blog_id = null;

				// change config to actual blog, it was master before
				$this->_config = new Config();

				// fix environment, potentially it's first request to a specific blog
				$environment = Dispatcher::component( 'Root_Environment' );
				$environment->fix_on_event( $this->_config, 'first_frontend',
					$this->_config );
			}

			// need to repeat request processing, since we was not able to realize
			// blog_id before so we are running with master config now.
			// redirect to the same url causes "redirect loop" error in browser,
			// so need to redirect to something a bit different
			if ( $do_redirect ) {
				if ( strpos( $_SERVER['REQUEST_URI'], '?' ) === false )
					Util_Environment::redirect_temp( $_SERVER['REQUEST_URI'] . '?repeat=w3tc' );
				else {
					if ( strpos( $_SERVER['REQUEST_URI'], 'repeat=w3tc' ) === false )
						Util_Environment::redirect_temp( $_SERVER['REQUEST_URI'] . '&repeat=w3tc' );
				}
			}
		}

		/**
		 * Check for rewrite test request
		 */
		$rewrite_test = Util_Request::get_boolean( 'w3tc_rewrite_test' );

		if ( $rewrite_test ) {
			echo 'OK';
			exit();
		}
		$admin_bar = false;
		if ( function_exists( 'is_admin_bar_showing' ) )
			$admin_bar = is_admin_bar_showing();

		if ( $admin_bar ) {
			add_action( 'wp_print_scripts', array( $this, 'popup_script' ) );
		}


		// dont add system stuff to search results
		if ( ( isset( $_GET['repeat'] ) && $_GET['repeat'] == 'w3tc' ) ||
			Util_Environment::is_preview_mode() ) {
			header( 'X-Robots-Tag: noindex' );
		}
	}

	/**
	 * Admin bar menu
	 *
	 * @return void
	 */
	function admin_bar_menu() {
		global $wp_admin_bar;

		$base_capability = apply_filters( 'w3tc_capability_admin_bar',
			'manage_options' );

		if ( current_user_can( $base_capability ) ) {
			$modules = Dispatcher::component( 'ModuleStatus' );

			$menu_postfix = '';
			if ( !is_admin() &&
				$this->_config->get_boolean( 'widget.pagespeed.show_in_admin_bar' ) ) {
				$menu_postfix = ' <span id="w3tc_monitoring_score">...</span>';
				add_action( 'wp_after_admin_bar_render',
					array( $this, 'wp_after_admin_bar_render' ) );
			}

			$menu_items = array();

			$menu_items['00010.generic'] = array(
				'id' => 'w3tc',
				'title' =>
				'<img src="' .
				plugins_url( 'pub/img/w3tc-sprite-admin-bar.png', W3TC_FILE ) .
				'" style="vertical-align:middle; margin-right:5px; width: 29px; height: 29px" />' .
				__( 'Performance', 'w3-total-cache' ) .
				$menu_postfix,
				'href' => network_admin_url( 'admin.php?page=w3tc_dashboard' )
			);

			if ( $modules->plugin_is_enabled() ) {
				$menu_items['10010.generic'] = array(
					'id' => 'w3tc_flush_all',
					'parent' => 'w3tc',
					'title' => __( 'Purge All Caches', 'w3-total-cache' ),
					'href' => wp_nonce_url( network_admin_url(
							'admin.php?page=w3tc_dashboard&amp;w3tc_flush_all' ),
						'w3tc' )
				);
			if ( !is_admin() )
				$menu_items['10020.generic'] = array(
					'id' => 'w3tc_flush_current_page',
					'parent' => 'w3tc',
					'title' => __( 'Purge Current Page', 'w3-total-cache' ),
					'href' => wp_nonce_url( admin_url(
							'admin.php?page=w3tc_dashboard&amp;w3tc_flush_current_page' ),
						'w3tc' )
				);

				$menu_items['20010.generic'] = array(
					'id' => 'w3tc_flush',
					'parent' => 'w3tc',
					'title' => __( 'Purge Modules', 'w3-total-cache' )
				);
			}

			$menu_items['40010.generic'] = array(
				'id' => 'w3tc_settings_general',
				'parent' => 'w3tc',
				'title' => __( 'General Settings', 'w3-total-cache' ),
				'href' => wp_nonce_url( network_admin_url( 'admin.php?page=w3tc_general' ), 'w3tc' )
			);
			$menu_items['40020.generic'] = array(
				'id' => 'w3tc_settings_extensions',
				'parent' => 'w3tc',
				'title' => __( 'Manage Extensions', 'w3-total-cache' ),
				'href' => wp_nonce_url( network_admin_url( 'admin.php?page=w3tc_extensions' ), 'w3tc' )
			);

			$menu_items['40030.generic'] = array(
				'id' => 'w3tc_settings_faq',
				'parent' => 'w3tc',
				'title' => __( 'FAQ', 'w3-total-cache' ),
				'href' => wp_nonce_url( network_admin_url( 'admin.php?page=w3tc_faq' ), 'w3tc' )
			);

			$menu_items['60010.generic'] = array(
				'id' => 'w3tc_support',
				'parent' => 'w3tc',
				'title' => __( 'Support', 'w3-total-cache' ),
				'href' => network_admin_url( 'admin.php?page=w3tc_support' )
			);

			if ( defined( 'W3TC_DEBUG' ) && W3TC_DEBUG ) {
				$menu_items['90010.generic'] = array(
					'id' => 'w3tc_debug_overlays',
					'parent' => 'w3tc',
					'title' => __( 'Debug: Overlays', 'w3-total-cache' ),
				);
				$menu_items['90020.generic'] = array(
					'id' => 'w3tc_overlay_support_us',
					'parent' => 'w3tc_debug_overlays',
					'title' => __( 'Support Us', 'w3-total-cache' ),
					'href' => wp_nonce_url( network_admin_url(
							'admin.php?page=w3tc_dashboard&amp;' .
							'w3tc_message_action=generic_support_us' ), 'w3tc' )
				);
				$menu_items['60030.generic'] = array(
					'id' => 'w3tc_overlay_edge',
					'parent' => 'w3tc_debug_overlays',
					'title' => __( 'Edge', 'w3-total-cache' ),
					'href' => wp_nonce_url( network_admin_url(
							'admin.php?page=w3tc_dashboard&amp;' .
							'w3tc_message_action=generic_edge' ), 'w3tc' )
				);
			}

			$menu_items = apply_filters( 'w3tc_admin_bar_menu', $menu_items );

			$keys = array_keys( $menu_items );
			asort( $keys );

			foreach ( $keys as $key ) {
				$capability = apply_filters(
					'w3tc_capability_admin_bar_' . $menu_items[$key]['id'],
					$base_capability );

				if ( current_user_can( $capability ) )
					$wp_admin_bar->add_menu( $menu_items[$key] );
			}
		}
	}

	public function wp_after_admin_bar_render() {
		$url = admin_url( 'admin-ajax.php', 'relative' ) .
			'?action=w3tc_monitoring_score&' . md5( $_SERVER['REQUEST_URI'] );

?>
        <script type= "text/javascript">
        var w3tc_monitoring_score = document.createElement('script');
        w3tc_monitoring_score.type = 'text/javascript';
        w3tc_monitoring_score.src = '<?php echo $url ?>';
        document.getElementsByTagName('HEAD')[0].appendChild(w3tc_monitoring_score);
        </script>
        <?php
	}

	/**
	 * Template filter
	 *
	 * @param unknown $template
	 * @return string
	 */
	function template( $template ) {
		$w3_mobile = Dispatcher::component( 'Mobile_UserAgent' );

		$mobile_template = $w3_mobile->get_template();

		if ( $mobile_template ) {
			return $mobile_template;
		} else {
			$w3_referrer = Dispatcher::component( 'Mobile_Referrer' );

			$referrer_template = $w3_referrer->get_template();

			if ( $referrer_template ) {
				return $referrer_template;
			}
		}

		return $template;
	}

	/**
	 * Stylesheet filter
	 *
	 * @param unknown $stylesheet
	 * @return string
	 */
	function stylesheet( $stylesheet ) {
		$w3_mobile = Dispatcher::component( 'Mobile_UserAgent' );

		$mobile_stylesheet = $w3_mobile->get_stylesheet();

		if ( $mobile_stylesheet ) {
			return $mobile_stylesheet;
		} else {
			$w3_referrer = Dispatcher::component( 'Mobile_Referrer' );

			$referrer_stylesheet = $w3_referrer->get_stylesheet();

			if ( $referrer_stylesheet ) {
				return $referrer_stylesheet;
			}
		}

		return $stylesheet;
	}

	/**
	 * Template filter
	 *
	 * @param unknown $template
	 * @return string
	 */
	function template_preview( $template ) {
		$theme_name = Util_Request::get_string( 'w3tc_theme' );

		$theme = Util_Theme::get( $theme_name );

		if ( $theme ) {
			return $theme['Template'];
		}

		return $template;
	}

	/**
	 * Stylesheet filter
	 *
	 * @param unknown $stylesheet
	 * @return string
	 */
	function stylesheet_preview( $stylesheet ) {
		$theme_name = Util_Request::get_string( 'w3tc_theme' );

		$theme = Util_Theme::get( $theme_name );

		if ( $theme ) {
			return $theme['Stylesheet'];
		}

		return $stylesheet;
	}

	/**
	 * Footer plugin action
	 *
	 * @return void
	 */
	function footer() {
		echo '<div style="text-align: center;"><a href="https://www.w3-edge.com/products/" rel="external">Optimization WordPress Plugins &amp; Solutions by W3 EDGE</a></div>';
	}

	/**
	 * Output buffering callback
	 *
	 * @param string  $buffer
	 * @return string
	 */
	function ob_callback( $buffer ) {
		global $wpdb;

		global $w3_late_caching_succeeded;
		if ( $w3_late_caching_succeeded ) {
			return $buffer;
		}

		if ( Util_Content::is_database_error( $buffer ) ) {
			status_header( 503 );
		} else {
			if ( Util_Content::can_print_comment( $buffer ) ) {
				/**
				 * Add footer comment
				 */
				$date = date_i18n( 'Y-m-d H:i:s' );
				$host = ( !empty( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : 'localhost' );

				if ( Util_Environment::is_preview_mode() )
					$buffer .= "\r\n<!-- W3 Total Cache used in preview mode -->";

                $buffer .= "\r\n<!--\r\n";

					$strings = array();
					$strings = apply_filters( 'w3tc_footer_comment', $strings );

                if ( $this->_config->get_string( 'common.support' ) == '' &&
                    !$this->_config->get_boolean( 'common.tweeted' ) ) {
                    $buffer .= "Performance optimized by W3 Total Cache. Learn more: https://www.w3-edge.com/products/\r\n";
					if ( count( $strings ) ) {
                        $buffer .= "\r\n";
                    }
					}

                if ( count( $strings ) ) {
                    $buffer .= Util_Content::escape_comment( implode( "\r\n", $strings ) ) . "\r\n\r\n";
				}

                $buffer .= sprintf( "Served from: %s @ %s by W3 Total Cache\r\n-->",
                            Util_Content::escape_comment( $host ), $date );

				$buffer = apply_filters( 'w3tc_process_content', $buffer );
			}

			$buffer = Util_Bus::do_ob_callbacks(
				array( 'swarmify', 'minify', 'newrelic', 'cdn', 'browsercache', 'pagecache' ),
				$buffer );
		}

		return $buffer;
	}

	/**
	 * Check if we can do modify contents
	 *
	 * @return boolean
	 */
	function can_ob() {
		global $w3_late_init;
		if ( $w3_late_init ) {
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
	 * User login hook
	 * Check if current user is not listed in pgcache.reject.* rules
	 * If so, set a role cookie so the requests wont be cached
	 */
	function check_login_action( $logged_in_cookie = false, $expire = ' ', $expiration = 0, $user_id = 0, $action = 'logged_out' ) {
		$current_user = wp_get_current_user();
		if ( isset( $current_user->ID ) && !$current_user->ID )
			$user_id = new \WP_User( $user_id );
		else
			$user_id = $current_user;

		if ( is_string( $user_id->roles ) ) {
			$roles = array( $user_id->roles );
		} elseif ( !is_array( $user_id->roles ) || count( $user_id->roles ) <= 0 ) {
			return;
		} else {
			$roles = $user_id->roles;
		}

		$rejected_roles = $this->_config->get_array( 'pgcache.reject.roles' );

		if ( 'logged_out' == $action ) {
			foreach ( $rejected_roles as $role ) {
				$role_hash = md5( NONCE_KEY . $role );
				setcookie( 'w3tc_logged_' . $role_hash, $expire,
					time() - 31536000, COOKIEPATH, COOKIE_DOMAIN );
			}

			return;
		}

		if ( 'logged_in' != $action )
			return;

		foreach ( $roles as $role ) {
			if ( in_array( $role, $rejected_roles ) ) {
				$role_hash = md5( NONCE_KEY . $role );
				setcookie( 'w3tc_logged_' . $role_hash, true, $expire,
					COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			}
		}
	}

	function popup_script() {
?>
        <script type="text/javascript">
            function w3tc_popupadmin_bar(url) {
                return window.open(url, '', 'width=800,height=600,status=no,toolbar=no,menubar=no,scrollbars=yes');
            }
        </script>
            <?php
	}

	private function is_debugging() {
		$debug = $this->_config->get_boolean( 'pgcache.enabled' ) && $this->_config->get_boolean( 'pgcache.debug' );
		$debug = $debug || ( $this->_config->get_boolean( 'dbcache.enabled' ) && $this->_config->get_boolean( 'dbcache.debug' ) );
		$debug = $debug || ( $this->_config->get_boolean( 'objectcache.enabled' ) && $this->_config->get_boolean( 'objectcache.debug' ) );
		$debug = $debug || ( $this->_config->get_boolean( 'browsercache.enabled' ) && $this->_config->get_boolean( 'browsercache.debug' ) );
		$debug = $debug || ( $this->_config->get_boolean( 'minify.enabled' ) && $this->_config->get_boolean( 'minify.debug' ) );
		$debug = $debug || ( $this->_config->get_boolean( 'cdn.enabled' ) && $this->_config->get_boolean( 'cdn.debug' ) );

		return $debug;
	}

	public function pro_dev_mode() {
		echo '<!-- W3 Total Cache is currently running in Pro version Development mode. --><div style="border:2px solid red;text-align:center;font-size:1.2em;color:red"><p><strong>W3 Total Cache is currently running in Pro version Development mode.</strong></p></div>';
	}
}
