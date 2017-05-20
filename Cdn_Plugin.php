<?php
namespace W3TC;

/**
 * W3 Total Cache CDN Plugin
 */
class Cdn_Plugin {

	/**
	 * CDN reject reason
	 *
	 * @var string
	 */
	var $cdn_reject_reason = '';

	/**
	 * Config
	 */
	private $_config = null;

	private $_replaced_urls = array();

	function __construct() {
		$this->_config = Dispatcher::config();
	}

	/**
	 * Runs plugin
	 */
	function run() {
		$cdn_engine = $this->_config->get_string( 'cdn.engine' );

        add_filter( 'wp_get_attachment_url', array( 
             $this,
            'w3tc_attachment_url'
        ), 0 );

        add_filter( 'attachment_link', array( 
            $this, 
            'w3tc_attachment_url'
        ), 0 );

		if ( Cdn_Util::is_engine_fsd( $cdn_engine ) ) {
			$this->run_fsd();
			return;
		}

		add_filter( 'cron_schedules', array(
				$this,
				'cron_schedules'
			) );

			add_filter( 'w3tc_footer_comment', array(
					$this,
					'w3tc_footer_comment'
				) );

		if ( !Cdn_Util::is_engine_mirror( $cdn_engine ) ) {
			add_action( 'delete_attachment', array(
					$this,
					'delete_attachment'
				) );

			add_filter( 'update_attached_file', array(
					$this,
					'update_attached_file'
				) );

			add_filter( 'wp_update_attachment_metadata', array(
					$this,
					'update_attachment_metadata'
				) );

			add_action( 'w3_cdn_cron_queue_process', array(
					$this,
					'cron_queue_process'
				) );

			add_action( 'w3_cdn_cron_upload', array(
					$this,
					'cron_upload'
				) );

			add_action( 'switch_theme', array(
					$this,
					'switch_theme'
				) );

			add_filter( 'update_feedback', array(
					$this,
					'update_feedback'
				) );
		}

		add_filter( 'w3tc_admin_bar_menu',
			array( $this, 'w3tc_admin_bar_menu' ) );

		if ( is_admin() ) {
			add_action( 'w3tc_config_ui_save-w3tc_cdn', array(
					$this, 'change_canonical_header' ), 0, 0 );
			add_filter( 'w3tc_module_is_running-cdn', array( $this, 'cdn_is_running' ) );
		}

		/**
		 * Start rewrite engine
		 */
		if ( $this->can_cdn() ) {
			Util_Bus::add_ob_callback( 'cdn', array( $this, 'ob_callback' ) );
		}

		if ( is_admin() && Cdn_Util::can_purge( $cdn_engine ) ) {
			add_filter( 'media_row_actions', array(
					$this,
					'media_row_actions'
				), 0, 2 );
		}
	}

	/**
	 * run code for FSD CDN
	 */
	private function run_fsd() {
		add_action( 'w3tc_flush_all', array(
				'\W3TC\Cdn_Fsd_CacheFlush',
				'w3tc_flush_all'
			), 3000, 1 );
		add_action( 'w3tc_flush_post', array(
				'\W3TC\Cdn_Fsd_CacheFlush',
				'w3tc_flush_post'
			), 3000, 1 );
		add_action( 'w3tc_flushable_posts', '__return_true', 3000 );
		add_action( 'w3tc_flush_posts', array(
				'\W3TC\Cdn_Fsd_CacheFlush',
				'w3tc_flush_all'
			), 3000 );
		add_action( 'w3tc_flush_url', array(
				'\W3TC\Cdn_Fsd_CacheFlush',
				'w3tc_flush_url'
			), 3000, 1 );
		add_filter( 'w3tc_flush_execute_delayed_operations', array(
				'\W3TC\Cdn_Fsd_CacheFlush',
				'w3tc_flush_execute_delayed_operations'
			), 3000 );

		Util_AttachToActions::flush_posts_on_actions();
	}

	/**
	 * Instantiates worker with admin functionality on demand
	 *
	 * @return Cdn_Core_Admin
	 */
	function get_admin() {
		return Dispatcher::component( 'Cdn_Core_Admin' );
	}

	/**
	 * Cron queue process event
	 */
	function cron_queue_process() {
		$queue_limit = $this->_config->get_integer( 'cdn.queue.limit' );
		return $this->get_admin()->queue_process( $queue_limit );
	}

	/**
	 * Cron upload event
	 */
	function cron_upload() {
		$files = $this->get_files();

		$upload = array();
		$results = array();

		$common = Dispatcher::component( 'Cdn_Core' );

		foreach ( $files as $file ) {
			$local_path = $common->docroot_filename_to_absolute_path( $file );
			$remote_path = $common->uri_to_cdn_uri( $common->docroot_filename_to_uri( $file ) );
			$upload[] = $common->build_file_descriptor( $local_path, $remote_path );
		}

		$common->upload( $upload, true, $results );
	}

	/**
	 * Update attachment file
	 *
	 * Upload _wp_attached_file
	 *
	 * @param string  $attached_file
	 * @return string
	 */
	function update_attached_file( $attached_file ) {
		$common = Dispatcher::component( 'Cdn_Core' );
		$files = $common->get_files_for_upload( $attached_file );
		$files = apply_filters( 'w3tc_cdn_update_attachment', $files );

		$results = array();

		$common->upload( $files, true, $results );

		return $attached_file;
	}

	/**
	 * On attachment delete action
	 *
	 * Delete _wp_attached_file, _wp_attachment_metadata, _wp_attachment_backup_sizes
	 *
	 * @param integer $attachment_id
	 */
	function delete_attachment( $attachment_id ) {
		$common = Dispatcher::component( 'Cdn_Core' );
		$files = $common->get_attachment_files( $attachment_id );
		$files = apply_filters( 'w3tc_cdn_delete_attachment', $files );

		$results = array();

		$common->delete( $files, true, $results );
	}

	/**
	 * Update attachment metadata filter
	 *
	 * Upload _wp_attachment_metadata
	 *
	 * @param array   $metadata
	 * @return array
	 */
	function update_attachment_metadata( $metadata ) {
		$common = Dispatcher::component( 'Cdn_Core' );
		$files = $common->get_metadata_files( $metadata );
		$files = apply_filters( 'w3tc_cdn_update_attachment_metadata', $files );

		$results = array();

		$common->upload( $files, true, $results );

		return $metadata;
	}

	/**
	 * Cron schedules filter
	 *
	 * @param array   $schedules
	 * @return array
	 */
	function cron_schedules( $schedules ) {
		$c = $this->_config;

		if ( $c->get_boolean( 'cdn.enabled' ) &&
			!Cdn_Util::is_engine_mirror( $c->get_string( 'cdn.engine' ) ) ) {
			$queue_interval = $c->get_integer( 'cdn.queue.interval' );
			$schedules['w3_cdn_cron_queue_process'] = array(
					'interval' => $queue_interval,
					'display' => sprintf(
						'[W3TC] CDN queue process (every %d seconds)', $queue_interval
					)
				);
		}

		if ( $c->get_boolean( 'cdn.enabled' ) &&
			$c->get_boolean( 'cdn.autoupload.enabled' ) &&
			!Cdn_Util::is_engine_mirror( $c->get_string( 'cdn.engine' ) ) ) {
			$autoupload_interval = $c->get_integer( 'cdn.autoupload.interval' );
			$schedules['w3_cdn_cron_upload'] = array(
					'interval' => $autoupload_interval,
					'display' => sprintf(
						'[W3TC] CDN auto upload (every %d seconds)', $autoupload_interval
					)
				);
		}

		return $schedules;
	}

	/**
	 * Switch theme action
	 */
	function switch_theme() {
		$state = Dispatcher::config_state();
		$state->set( 'cdn.show_note_theme_changed', true );
		$state->save();
	}

	/**
	 * WP Upgrade action hack
	 *
	 * @param string  $message
	 */
	function update_feedback( $message ) {
		if ( $message == __( 'Upgrading database' ) ) {
			$state = Dispatcher::config_state();
			$state->set( 'cdn.show_note_wp_upgraded', true );
			$state->save();
		}
	}

	/**
	 * OB Callback
	 *
	 * @param string  $buffer
	 * @return string
	 */
	function ob_callback( $buffer ) {
		if ( $buffer != '' && Util_Content::is_html_xml( $buffer ) ) {
			if ( $this->can_cdn2( $buffer ) ) {
				$srcset_helper = new _Cdn_Plugin_ContentFilter();
				$buffer = $srcset_helper->replace_all_links( $buffer );
				$this->_replaced_urls = $srcset_helper->get_replaced_urls();
			}
		}

		return $buffer;
	}

	/**
	 * Returns array of files to upload
	 *
	 * @return array
	 */
	function get_files() {
		$files = array();

		if ( $this->_config->get_boolean( 'cdn.includes.enable' ) ) {
			$files = array_merge( $files, $this->get_files_includes() );
		}

		if ( $this->_config->get_boolean( 'cdn.theme.enable' ) ) {
			$files = array_merge( $files, $this->get_files_theme() );
		}

		if ( $this->_config->get_boolean( 'cdn.minify.enable' ) ) {
			$files = array_merge( $files, $this->get_files_minify() );
		}

		if ( $this->_config->get_boolean( 'cdn.custom.enable' ) ) {
			$files = array_merge( $files, $this->get_files_custom() );
		}

		return $files;
	}

	/**
	 * Exports includes to CDN
	 *
	 * @return array
	 */
	function get_files_includes() {
		$includes_root = Util_Environment::normalize_path( ABSPATH . WPINC );
		$doc_root = Util_Environment::normalize_path( Util_Environment::document_root() );
		$includes_path = ltrim( str_replace( $doc_root, '', $includes_root ), '/' );

		$files = Cdn_Util::search_files(
			$includes_root, $includes_path, $this->_config->get_string( 'cdn.includes.files' )
		);

		return $files;
	}

	/**
	 * Exports theme to CDN
	 *
	 * @return array
	 */
	function get_files_theme() {
		/**
		 * If mobile or referrer support enabled
		 * we should upload whole themes directory
		 */
		if ( $this->_config->get_boolean( 'mobile.enabled' )
			|| $this->_config->get_boolean( 'referrer.enabled' ) ) {
			$themes_root = get_theme_root();
		} else {
			$themes_root = get_stylesheet_directory();
		}

		$themes_root = Util_Environment::normalize_path( $themes_root );
		$themes_path = ltrim( str_replace(
				Util_Environment::normalize_path( Util_Environment::document_root() ), '', $themes_root ), '/' );
		$files = Cdn_Util::search_files(
			$themes_root, $themes_path, $this->_config->get_string( 'cdn.theme.files' )
		);

		return $files;
	}

	/**
	 * Exports min files to CDN
	 *
	 * @return array
	 */
	function get_files_minify() {
		$files = array();

		if ( $this->_config->get_boolean( 'minify.rewrite' ) &&
			Util_Rule::can_check_rules() &&
			( !$this->_config->get_boolean( 'minify.auto' ) ||
				Cdn_Util::is_engine_mirror( $this->_config->get_string( 'cdn.engine' ) ) ) ) {


			$minify = Dispatcher::component( 'Minify_Plugin' );

			$document_root = Util_Environment::normalize_path(
				Util_Environment::document_root() );
			$minify_root = Util_Environment::normalize_path(
				Util_Environment::cache_blog_dir( 'minify' ) );
			$minify_path = ltrim( str_replace( $document_root, '', $minify_root ), '/' );
			$urls = $minify->get_urls();

			// in WPMU + network admin (this code used for minify manual only)
			// common minify files are stored under context of main blog (i.e. 1)
			// but have urls of 0 blog, so download has to be used
			if ( $this->_config->get_string( 'minify.engine' ) == 'file' &&
				!( Util_Environment::is_wpmu() && is_network_admin() ) ) {

				foreach ( $urls as $url ) {
					Util_Http::get( $url );
				}

				$files = Cdn_Util::search_files( $minify_root,
					$minify_path, '*.css;*.js' );

			} else {
				foreach ( $urls as $url ) {
					$file = Util_Environment::normalize_file_minify( $url );
					$file = Util_Environment::translate_file( $file );

					if ( !Util_Environment::is_url( $file ) ) {
						$file = $document_root . '/' . $file;
						$file = ltrim( str_replace( $minify_root, '', $file ), '/' );

						$dir = dirname( $file );

						if ( $dir ) {
							Util_File::mkdir( $dir, 0777, $minify_root );
						}

						if ( Util_Http::download( $url, $minify_root . '/' . $file ) !== false ) {
							$files[] = $minify_path . '/' . $file;
						}
					}
				}
			}
		}

		return $files;
	}

	/**
	 * Exports custom files to CDN
	 *
	 * @return array
	 */
	function get_files_custom() {
		$files = array();
		$document_root = Util_Environment::normalize_path(
			Util_Environment::document_root() );
		$custom_files = $this->_config->get_array( 'cdn.custom.files' );
		$custom_files = array_map( array( '\W3TC\Util_Environment', 'parse_path' ), $custom_files );
		$site_root = Util_Environment::normalize_path( Util_Environment::site_root() );
		$path = Util_Environment::site_url_uri();
		$site_root_dir = str_replace( $document_root, '', $site_root );
		if ( strstr( WP_CONTENT_DIR, Util_Environment::site_root() ) === false ) {
			$site_root = Util_Environment::normalize_path( Util_Environment::document_root() );
			$path = '';
		}

		$content_path = trim( str_replace( WP_CONTENT_DIR, '', $site_root ), '/\\' );

		foreach ( $custom_files as $custom_file ) {
			if ( $custom_file != '' ) {
				$custom_file = Cdn_Util::replace_folder_placeholders( $custom_file );
				$custom_file = Util_Environment::normalize_file( $custom_file );

				if ( !Util_Environment::is_wpmu() ) {
					$dir = trim( dirname( $custom_file ), '/\\' );
					$rel_path = trim( dirname( $custom_file ), '/\\' );
				} else
					$rel_path = $dir = trim( dirname( $custom_file ), '/\\' );

				if ( strpos( $dir, '<currentblog>' ) != false ) {
					$rel_path = $dir = str_replace(
						'<currentblog>', 'blogs.dir/'
						. Util_Environment::blog_id(), $dir
					);
				}

				if ( $dir == '.' ) {
					$rel_path = $dir = '';
				}
				$mask = basename( $custom_file );
				$files = array_merge(
					$files, Cdn_Util::search_files( $document_root . '/'
						. $dir, $rel_path, $mask )
				);
			}
		}

		return $files;
	}

	/**
	 * Check if we can do CDN logic
	 *
	 * @return boolean
	 */
	function can_cdn() {
		/**
		 * Skip if admin
		 */
		if ( defined( 'WP_ADMIN' ) ) {
			$this->cdn_reject_reason = 'wp-admin';

			return false;
		}

		/**
		 * Check for WPMU's and WP's 3.0 short init
		 */
		if ( defined( 'SHORTINIT' ) && SHORTINIT ) {
			$this->cdn_reject_reason = 'Short init';

			return false;
		}

		/**
		 * Check User agent
		 */
		if ( !$this->check_ua() ) {
			$this->cdn_reject_reason = 'user agent is rejected';

			return false;
		}

		/**
		 * Check request URI
		 */
		if ( !$this->_check_request_uri() ) {
			$this->cdn_reject_reason = 'request URI is rejected';

			return false;
		}

		/**
		 * Do not replace urls if SSL and SSL support is do not replace
		 */
		if ( Util_Environment::is_https() && $this->_config->get_boolean( 'cdn.reject.ssl' ) ) {
			$this->cdn_reject_reason = 'SSL is rejected';

			return false;
		}

		return true;
	}

	/**
	 * Returns true if we can do CDN logic
	 *
	 * @param unknown $buffer
	 * @return string
	 */
	function can_cdn2( $buffer ) {
		/**
		 * Check for database error
		 */
		if ( Util_Content::is_database_error( $buffer ) ) {
			$this->cdn_reject_reason = 'Database Error occurred';

			return false;
		}

		/**
		 * Check for DONOTCDN constant
		 */
		if ( defined( 'DONOTCDN' ) && DONOTCDN ) {
			$this->cdn_reject_reason = 'DONOTCDN constant is defined';

			return false;
		}

		/**
		 * Check logged users roles
		 */
		if ( $this->_config->get_boolean(
				'cdn.reject.logged_roles' ) && !$this->_check_logged_in_role_allowed()
		) {
			$this->cdn_reject_reason = 'logged in role is rejected';

			return false;
		}

		return true;
	}

	/**
	 * Checks User Agent
	 *
	 * @return boolean
	 */
	function check_ua() {
		$uas = array_merge( $this->_config->get_array( 'cdn.reject.ua' ), array(
				W3TC_POWERED_BY
			) );

		foreach ( $uas as $ua ) {
			if ( !empty( $ua ) ) {
				if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && stristr(
						$_SERVER['HTTP_USER_AGENT'], $ua ) !== false
				)
					return false;
			}
		}

		return true;
	}

	/**
	 * Checks request URI
	 *
	 * @return boolean
	 */
	function _check_request_uri() {
		$reject_uri = $this->_config->get_array( 'cdn.reject.uri' );
		$reject_uri = array_map( array( '\W3TC\Util_Environment', 'parse_path' ), $reject_uri );

		foreach ( $reject_uri as $expr ) {
			$expr = trim( $expr );
			$expr = str_replace( '~', '\~', $expr );

			if ( $expr != '' && preg_match( '~' . $expr . '~i', $_SERVER['REQUEST_URI'] ) ) {
				return false;
			}
		}


		if ( Util_Request::get_string( 'wp_customize' ) )
			return false;

		return true;
	}
	/**
	 * Check if logged in user role is allwed to use CDN
	 *
	 * @return boolean
	 */
	private function _check_logged_in_role_allowed() {
		$current_user = wp_get_current_user();

		if ( !is_user_logged_in() )
			return true;

		$roles = $this->_config->get_array( 'cdn.reject.roles' );

		if ( empty( $roles ) || empty( $current_user->roles ) ||
			!is_array( $current_user->roles ) )
			return true;

		foreach ( $current_user->roles as $role ) {
			if ( in_array( $role, $roles ) )
				return false;
		}

		return true;
	}

	/**
	 * media_row_actions filter
	 *
	 * @param array   $actions
	 * @param object  $post
	 * @return array
	 */
	function media_row_actions( $actions, $post ) {
		return $this->get_admin()->media_row_actions( $actions, $post );
	}


	/**
	 *
	 *
	 * @param unknown $current_state
	 * @return bool
	 */
	function cdn_is_running( $current_state ) {
		$admin = $this->get_admin();
		return $admin->is_running();
	}

	/**
	 * Change canonical header
	 */
	function change_canonical_header() {
		$admin = $this->get_admin();
		$admin->change_canonical_header();
	}

	public function w3tc_admin_bar_menu( $menu_items ) {
		$cdn_engine = $this->_config->get_string( 'cdn.engine' );

		if ( Cdn_Util::can_purge_all( $cdn_engine ) ) {
			$menu_items['20710.cdn'] = array(
				'id' => 'w3tc_cdn_flush_all',
				'parent' => 'w3tc_flush',
				'title' => __( 'CDN: All', 'w3-total-cache' ),
				'href' => wp_nonce_url( network_admin_url(
						'admin.php?page=w3tc_cdn&amp;w3tc_flush_cdn' ),
					'w3tc' )
			);
		}

		if ( Cdn_Util::can_purge( $cdn_engine ) ) {
			$menu_items['20790.cdn'] = array(
				'id' => 'w3tc_cdn_flush',
				'parent' => 'w3tc_flush',
				'title' => __( 'CDN: Manual Purge', 'w3-total-cache' ),
				'href' => wp_nonce_url( network_admin_url( 'admin.php?page=w3tc_cdn&amp;w3tc_cdn_purge' ), 'w3tc' ),
				'meta' => array( 'onclick' => "w3tc_popupadmin_bar(this.href); return false" )
			);
		}

		return $menu_items;
	}

	public function w3tc_footer_comment( $strings ) {
		$common = Dispatcher::component( 'Cdn_Core' );
		$cdn = $common->get_cdn();
		$via = $cdn->get_via();

		$comment = sprintf(
			__( 'Content Delivery Network via %s%s', 'w3-total-cache' ),
			( $via ? $via : 'N/A' ),
			( empty( $this->cdn_reject_reason ) ? '' :
				sprintf( ' (%s)', $this->cdn_reject_reason ) ) );

		if ( $this->_config->get_boolean( 'cdn.debug' ) ) {
			$strings[] = "~~~~~~~~~~~~~~~~~~~~~~~~~~";
			$strings[] = "CDN debug info:";
			$strings[] = "~~~~~~~~~~~~~~~~~~~~~~~~~~";
			$strings[] = sprintf( "%s%s via %s", str_pad( 'Engine: ', 20 ),
				$this->_config->get_string( 'cdn.engine' ),
				( $via ? $via : 'N/A' ) );

			if ( $this->cdn_reject_reason ) {
				$strings[] = sprintf( "%s%s", str_pad( 'Reject reason: ', 20 ),
					$this->cdn_reject_reason );
			}

			if ( count( $this->_replaced_urls ) ) {
				$strings[] = "Replaced URLs:";

				foreach ( $this->_footer_comment_postfix as $old_url => $new_url ) {
					$strings[] = sprintf( "%s => %s",
						Util_Content::escape_comment( $old_url ),
						Util_Content::escape_comment( $new_url ) );
				}
			}
		} elseif ( $this->_config->get_string( 'common.support' ) == '' &&
					!$this->_config->get_boolean( 'common.tweeted' ) ){
			$strings[] = $comment;
		}

		return $strings;
	}

	/**
	 * Adjusts attachment urls to cdn. This is for those who rely on
	 * wp_get_attachment_url()
	 *
	 * @param 	string   $url	the local url to modify
	 * @return 	string
	 */
    function w3tc_attachment_url( $url ) {
        static $allowed_files = null;

		if ( ( defined( 'WP_ADMIN' ) && $this->_config->get_boolean( 'cdn.admin.media_library' ) ) ||
			 ( $this->can_cdn() && $this->can_cdn2( '' ) ) ) {
			$url = trim( $url );

			if ( !empty( $url ) ) {
				if ( empty( $allowed_files ) ) {
					$allowed_files = $this->get_files();
				}

				$parsed = parse_url( $url );
				$rel_url = ( isset( $parsed['path'] ) ? $parsed['path'] : '/' ) .
						   ( isset( $parsed['query'] ) ? '?' . $parsed['query'] : '' );

				if ( in_array( ltrim( $rel_url, '/' ), $allowed_files ) ) {
					$common = Dispatcher::component( 'Cdn_Core' );
					$cdn = $common->get_cdn();
					$remote_path = $common->uri_to_cdn_uri( $rel_url );
					$url = $cdn->_format_url( $remote_path );
				}
			}
		}

        return $url;
    }		
}

class _Cdn_Plugin_ContentFilter {

	private $_regexps = array();
	private $_placeholders = array();
	private $_config;
	private $_replaced_urls;
	/**
	 * If background uploading already scheduled
	 *
	 * @var boolean
	 */
	private static $_upload_scheduled = false;

	function __construct() {
		$this->_config = Dispatcher::config();
	}

	function replace_all_links( $buffer ) {
		$this->fill_regexps();

		$srcset_pattern = '~srcset\s*=\s*[\"\'](.*?)[\"\']~';
		$buffer = preg_replace_callback(
			$srcset_pattern, array( $this, '_srcset_replace_callback' ), $buffer
		);

		foreach ( $this->_regexps as $regexp ) {
			$buffer = preg_replace_callback(
				$regexp, array( $this, '_link_replace_callback' ), $buffer
			);
		}

		if ( $this->_config->get_boolean( 'cdn.minify.enable' ) ) {
			if ( $this->_config->get_boolean( 'minify.auto' ) ) {
				$regexp = '~(["\'(=])\s*' .
					$this->minify_url_regexp( '/[a-zA-Z0-9-_]+\.(css|js)' ) .
					'~U';
				if ( Cdn_Util::is_engine_mirror( $this->_config->get_string( 'cdn.engine' ) ) )
					$processor = array( $this, '_link_replace_callback' );
				else
					$processor = array( $this, '_minify_auto_pushcdn_link_replace_callback' );
			} else {
				$regexp = '~(["\'(=])\s*' .
					$this->minify_url_regexp(
					'/[a-z0-9]+\..+\.include(-(footer|body))?(-nb)?\.[a-f0-9]+\.(css|js)' )
					.'~U';
				$processor = array( $this, '_link_replace_callback' );
			}

			$buffer = preg_replace_callback( $regexp, $processor, $buffer );
		}

		$buffer = $this->replace_placeholders( $buffer );

		return $buffer;
	}

	/**
	 * Link replace callback
	 *
	 * @param array   $matches
	 * @return string
	 */
	function _link_replace_callback( $matches ) {
		list( $match, $quote, $url, , , , $path ) = $matches;
		$path = ltrim( $path, '/' );
		$r = $this->_link_replace_callback_checks( $match, $quote, $url, $path );
		if ( is_null( $r ) ) {
			$r = $this->_link_replace_callback_ask_cdn( $match, $quote, $url, $path );
		}

		return $r;
	}

	function _srcset_replace_callback( $matches ) {
		list( $match, $srcset ) = $matches;
		if ( empty( $this->_regexps ) )
			return $match;
		$index = "%srcset-" . count( $this->_placeholders ) . "%";

		$srcset_urls = explode( ',', $srcset );
		$new_srcset_urls = array();

		foreach ( $srcset_urls as $set ) {

			preg_match( "~(?P<spaces>^\s*)(?P<url>\S+)(?P<rest>.*)~", $set, $parts );
			if ( isset( $parts['url'] ) ) {

				foreach ( $this->_regexps as $regexp ) {
					$new_url = preg_replace_callback( $regexp, array(
							$this,
							'_link_replace_callback'
						), '"' . $parts['url'] . '">' );

					if ( '"' . $parts['url'] . '">' != $new_url ) {
						$parts['url'] = substr( $new_url, 1, -2 );
						break;
					}
				}
				$new_srcset_urls[] = $parts['spaces'] .$parts['url']
					. $parts['rest'];
			} else {
				$new_srcset_urls[] = $set;
			}

		}
		$this->_placeholders[$index] = implode( ',', $new_srcset_urls );
		return 'srcset="' . $index . '"';
	}

	private function replace_placeholders( $buffer ) {
		foreach ( $this->_placeholders as $srcset_id => $srcset_content ) {
			$buffer = str_replace( $srcset_id, $srcset_content, $buffer );
		}
		return $buffer;
	}

	/**
	 * Gets regexp for minified files
	 *
	 * @return string
	 */
	private function minify_url_regexp( $filename_mask ) {
		$minify_base_url = Util_Environment::filename_to_url(
			Util_Environment::cache_blog_minify_dir()
		);
		$matches = null;
		if ( !preg_match( '~((https?://)?([^/]+))(.+)~i', $minify_base_url, $matches ) )
			return '';

		$protocol_domain_regexp = Util_Environment::get_url_regexp( $matches[1] );
		$path_regexp = Util_Environment::preg_quote( $matches[4] );

		$regexp =
			'(' .
			'(' . $protocol_domain_regexp . ')?' .
			'(' . $path_regexp . $filename_mask . ')' .
			')';
		return $regexp;
	}

	/**
	 *
	 *
	 * @param unknown $domain_url_regexp
	 * @param unknown $baseurl
	 * @param unknown $upload_info
	 * @param unknown $regexps
	 * @return array
	 */
	private function make_uploads_regexes( $domain_url_regexp, $baseurl,
		$upload_info, $regexps ) {
		if ( preg_match( '~' . $domain_url_regexp . '~i', $baseurl ) ) {
			$regexps[] = '~(["\'(=])\s*((' . $domain_url_regexp . ')?('
				. Util_Environment::preg_quote( $upload_info['baseurlpath'] )
				. '([^"\')>]+)))~i';
		} else {
			$parsed = @parse_url( $baseurl );
			$upload_url_domain_regexp = isset( $parsed['host'] )
				? Util_Environment::get_url_regexp( $parsed['scheme'] . '://'
				. $parsed['host'] ) : $domain_url_regexp;
			$baseurlpath = isset( $parsed['path'] ) ? rtrim( $parsed['path'], '/' ) : '';
			if ( $baseurlpath )
				$regexps[] = '~(["\'])\s*((' . $upload_url_domain_regexp . ')?('
					. Util_Environment::preg_quote( $baseurlpath )
					. '([^"\'>]+)))~i';
			else
				$regexps[] = '~(["\'])\s*((' . $upload_url_domain_regexp
					. ')(([^"\'>]+)))~i';
		}
		return $regexps;
	}

	private function fill_regexps() {
		$regexps = array();

		$site_path = Util_Environment::site_url_uri();
		$domain_url_regexp = Util_Environment::home_domain_root_url_regexp();

		$site_domain_url_regexp = false;
		if ( $domain_url_regexp != Util_Environment::get_url_regexp(
				Util_Environment::url_to_host( site_url() ) ) )
			$site_domain_url_regexp = Util_Environment::get_url_regexp(
				Util_Environment::url_to_host( site_url() )
			);

		if ( $this->_config->get_boolean( 'cdn.uploads.enable' ) ) {
			$upload_info = Util_Http::upload_info();

			if ( $upload_info ) {
				$baseurl = $upload_info['baseurl'];

				if ( defined( 'DOMAIN_MAPPING' ) && DOMAIN_MAPPING ) {
					$parsed = @parse_url( $upload_info['baseurl'] );
					$baseurl = home_url() . $parsed['path'];
				}

				$regexps = $this->make_uploads_regexes(
					$domain_url_regexp, $baseurl, $upload_info, $regexps
				);
				if ( $site_domain_url_regexp )
					$regexps = $this->make_uploads_regexes(
						$site_domain_url_regexp, $baseurl, $upload_info, $regexps
					);
			}
		}

		if ( $this->_config->get_boolean( 'cdn.includes.enable' ) ) {
			$mask = $this->_config->get_string( 'cdn.includes.files' );
			if ( $mask != '' ) {
				$regexps[] = '~(["\'(=])\s*((' . $domain_url_regexp .
					')?(' .
					Util_Environment::preg_quote( $site_path . WPINC ) .
					'/(' . Cdn_Util::get_regexp_by_mask( $mask ) . ')([^"\'() >]*)))~i';
				if ( $site_domain_url_regexp )
					$regexps[] = '~(["\'(=])\s*((' .
						$site_domain_url_regexp . ')?(' .
						Util_Environment::preg_quote( $site_path . WPINC ) .
						'/(' . Cdn_Util::get_regexp_by_mask( $mask ) .
						')([^"\'() >]*)))~i';
			}
		}

		if ( $this->_config->get_boolean( 'cdn.theme.enable' ) ) {
			$theme_dir = preg_replace( '~'
				. $domain_url_regexp . '~i', '', get_theme_root_uri() );

			$mask = $this->_config->get_string( 'cdn.theme.files' );

			if ( $mask != '' ) {
				$regexps[] = '~(["\'(=])\s*((' . $domain_url_regexp . ')?(' .
					Util_Environment::preg_quote( $theme_dir ) . '/(' .
					Cdn_Util::get_regexp_by_mask( $mask ) . ')([^"\'() >]*)))~i';
				if ( $site_domain_url_regexp ) {
					$theme_dir2 = preg_replace( '~' . $site_domain_url_regexp
						. '~i', '', get_theme_root_uri() );
					$regexps[] = '~(["\'(=])\s*((' .
						$site_domain_url_regexp . ')?(' .
						Util_Environment::preg_quote( $theme_dir ) . '/(' .
						Cdn_Util::get_regexp_by_mask( $mask ) .
						')([^"\'() >]*)))~i';
					$regexps[] = '~(["\'(=])\s*((' .
						$site_domain_url_regexp . ')?(' .
						Util_Environment::preg_quote( $theme_dir2 ) .
						'/(' . Cdn_Util::get_regexp_by_mask( $mask ) .
						')([^"\'() >]*)))~i';
				}
			}
		}

		if ( $this->_config->get_boolean( 'cdn.custom.enable' ) ) {
			$masks = $this->_config->get_array( 'cdn.custom.files' );
			$masks = array_map( array( '\W3TC\Cdn_Util', 'replace_folder_placeholders_to_uri' ), $masks );
			$masks = array_map( array( '\W3TC\Util_Environment', 'parse_path' ), $masks );

			if ( count( $masks ) ) {
				$custom_regexps_urls = array();
				$custom_regexps_uris = array();
				$custom_regexps_docroot_related = array();

				foreach ( $masks as $mask ) {
					if ( !empty( $mask ) ) {
						if ( Util_Environment::is_url( $mask ) ) {
							$url_match = array();
							if ( preg_match( '~^((https?:)?//([^/]*))(.*)~', $mask, $url_match ) ) {
								$custom_regexps_urls[] = array(
									'domain_url' => Util_Environment::get_url_regexp(
										$url_match[1] ),
									'uri' => Cdn_Util::get_regexp_by_mask( $url_match[4] )
								);
							}
						} elseif ( substr( $mask, 0, 1 ) == '/' ) {   // uri
							$custom_regexps_uris[] = Cdn_Util::get_regexp_by_mask( $mask );
						} else {
							$file = Util_Environment::normalize_path( $mask );   // \ -> backspaces
							$file = str_replace( Util_Environment::site_root(), '', $file );
							$file = ltrim( $file, '/' );

							$custom_regexps_docroot_related[] = Cdn_Util::get_regexp_by_mask( $mask );
						}
					}
				}

				if ( count( $custom_regexps_urls ) > 0 ) {
					foreach ( $custom_regexps_urls as $regexp ) {
						$regexps[] = '~(["\'(=])\s*((' . $regexp['domain_url'] .
						')?((' . $regexp['uri'] . ')([^"\'() >]*)))~i';
					}
				}
				if ( count( $custom_regexps_uris ) > 0 ) {
					$regexps[] = '~(["\'(=])\s*((' . $domain_url_regexp .
						')?((' . implode( '|', $custom_regexps_uris ) . ')([^"\'() >]*)))~i';
				}

				if ( count( $custom_regexps_docroot_related ) > 0 ) {
					$regexps[] = '~(["\'(=])\s*((' . $domain_url_regexp .
						')?(' . Util_Environment::preg_quote( $site_path ) .
						'(' . implode( '|', $custom_regexps_docroot_related ) . ')([^"\'() >]*)))~i';
					if ( $site_domain_url_regexp )
						$regexps[] = '~(["\'(=])\s*((' .
							$site_domain_url_regexp . ')?(' .
							Util_Environment::preg_quote( $site_path ) . '(' .
							implode( '|', $custom_regexps_docroot_related ) . ')([^"\'() >]*)))~i';
				}
			}
		}

		$this->_regexps = $regexps;
	}

	/**
	 * Link replace callback, basic checks step
	 *
	 * @param string  $match
	 * @param string  $quote
	 * @param string  $url
	 * @param string  $path
	 * @return null|string
	 */
	function _link_replace_callback_checks( $match, $quote, $url, $path ) {
		global $wpdb;
		static $queue = null, $reject_files = null;

		/**
		 * Check if URL was already replaced
		 */
		if ( isset( $this->replaced_urls[$url] ) ) {
			return $quote . $this->replaced_urls[$url];
		}

		/**
		 * Check URL for rejected files
		 */
		if ( $reject_files === null ) {
			$reject_files = $this->_config->get_array( 'cdn.reject.files' );
		}

		foreach ( $reject_files as $reject_file ) {
			if ( $reject_file != '' ) {
				$reject_file = Cdn_Util::replace_folder_placeholders( $reject_file );

				$reject_file = Util_Environment::normalize_file( $reject_file );

				$reject_file_regexp = '~^('
					. Cdn_Util::get_regexp_by_mask( $reject_file ) . ')~i';

				if ( preg_match( $reject_file_regexp, $path ) ) {
					return $match;
				}
			}
		}

		/**
		 * Don't replace URL for files that are in the CDN queue
		 */
		if ( $queue === null ) {
			if ( !Cdn_Util::is_engine_mirror( $this->_config->get_string( 'cdn.engine' ) ) ) {
				$sql = $wpdb->prepare( 'SELECT remote_path FROM '
					. $wpdb->base_prefix . W3TC_CDN_TABLE_QUEUE
					. ' WHERE remote_path = %s', $path );
				$queue = $wpdb->get_var( $sql );
			}
			else {
				$queue = false;
			}
		}
		if ( $queue ) {
			return $match;
		}
		return null;
	}

	/**
	 * Link replace callback, url replacement using cdn engine
	 *
	 * @param string  $match
	 * @param string  $quote
	 * @param string  $url
	 * @param string  $path
	 * @return null|string
	 */
	function _link_replace_callback_ask_cdn( $match, $quote, $url, $path ) {
		$common = Dispatcher::component( 'Cdn_Core' );
		$cdn = $common->get_cdn();
		$remote_path = $common->uri_to_cdn_uri( $path );
		$new_url = $cdn->format_url( $remote_path );
		if ( $new_url ) {
			$is_engine_mirror = Cdn_Util::is_engine_mirror(
				$this->_config->get_string( 'cdn.engine' ) );

			$new_url = apply_filters( 'w3tc_cdn_url', $new_url, $url,
				$is_engine_mirror );
			$this->replaced_urls[$url] = $new_url;
			return $quote . $new_url;
		}

		return $match;
	}

	/**
	 * Link replace callback for urls from minify module using auto mode and in cdn of push type
	 *
	 * @param array   $matches
	 * @return string
	 */
	function _minify_auto_pushcdn_link_replace_callback( $matches ) {
		static $dispatcher = null;

		list( $match, $quote, $url, , , , $path ) = $matches;
		$path = ltrim( $path, '/' );
		$r = $this->_link_replace_callback_checks( $match, $quote, $url, $path );

		/**
		 * Check if we can replace that URL (for auto mode it should be uploaded)
		 */
		if ( !Dispatcher::is_url_cdn_uploaded( $url ) ) {
			Dispatcher::component( 'Cdn_Core' )->queue_upload_url( $url );
			if ( !self::$_upload_scheduled ) {
				wp_schedule_single_event( time(), 'w3_cdn_cron_queue_process' );
				add_action( 'shutdown', 'wp_cron' );

				self::$_upload_scheduled = true;
			}


			return $match;
		}

		if ( is_null( $r ) ) {
			$r = $this->_link_replace_callback_ask_cdn( $match, $quote, $url, $path );
		}
		return $r;
	}

	function get_replaced_urls() {
		$strings = array();
		if ( count( $this->_replaced_urls ) ) {
			$strings[] = "Replaced URLs:";

			foreach ( $this->_replaced_urls as $old_url => $new_url ) {
				$strings[] = sprintf( "%s => %s",
					Util_Content::escape_comment( $old_url ),
					Util_Content::escape_comment( $new_url ) );
			}
		}
		return $strings;
	}

}
