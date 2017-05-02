<?php
namespace W3TC;

/**
 * W3 Total Cache ExtensionsAdmin plugin
 */
class Extensions_Plugin_Admin {
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
		// attach w3tc-bundled extensions
		add_filter( 'w3tc_extensions', array(
				'\W3TC\Extension_CloudFlare_Plugin_Admin',
				'w3tc_extensions' ),
			10, 2 );
		add_filter( 'w3tc_extensions', array(
				'\W3TC\Extension_FeedBurner_Plugin_Admin',
				'w3tc_extensions' ),
			10, 2 );
		add_filter( 'w3tc_extensions', array(
				'\W3TC\Extension_FragmentCache_Plugin_Admin',
				'w3tc_extensions' ),
			10, 2 );
		add_filter( 'w3tc_extensions', array(
				'\W3TC\Extension_Genesis_Plugin_Admin',
				'w3tc_extensions' ),
			10, 2 );
		add_filter( 'w3tc_extensions_hooks', array(
				'\W3TC\Extension_Genesis_Plugin_Admin',
				'w3tc_extensions_hooks' ) );
		add_filter( 'w3tc_notes_genesis_theme', array(
				'\W3TC\Extension_Genesis_Plugin_Admin',
				'w3tc_notes_genesis_theme' ) );
		add_filter( 'w3tc_extensions', array(
				'\W3TC\Extension_NewRelic_Plugin_Admin',
				'w3tc_extensions' ),
			10, 2 );
		add_filter( 'w3tc_extensions', array(
				'\W3TC\Extension_Swarmify_Plugin_Admin',
				'w3tc_extensions' ),
			10, 2 );
		add_filter( 'w3tc_extensions', array(
				'\W3TC\Extension_WordPressSeo_Plugin_Admin',
				'w3tc_extensions' ),
			10, 2 );
		add_filter( 'w3tc_extensions_hooks', array(
				'\W3TC\Extension_WordPressSeo_Plugin_Admin',
				'w3tc_extensions_hooks' ) );
		add_action( 'w3tc_notes_wordpress_seo', array(
				'\W3TC\Extension_WordPressSeo_Plugin_Admin',
				'w3tc_notes_wordpress_seo' ) );
		add_filter( 'w3tc_extensions', array(
				'\W3TC\Extension_Wpml_Plugin_Admin',
				'w3tc_extensions' ),
			10, 2 );
		add_filter( 'w3tc_extensions', array(
				'\W3TC\Extension_Amp_Plugin_Admin',
				'w3tc_extensions' ),
			10, 2 );
		add_filter( 'w3tc_extensions_hooks', array(
				'\W3TC\Extension_Wpml_Plugin_Admin',
				'w3tc_extensions_hooks' ) );
		add_action( 'w3tc_notes_wpml', array(
				'\W3TC\Extension_Wpml_Plugin_Admin',
				'w3tc_notes_wpml' ) );

		add_action( 'admin_init', array( $this, 'admin_init' ), 1 );
		add_filter( 'pre_update_option_active_plugins', array(
				$this, 'pre_update_option_active_plugins' ) );
		add_filter( 'w3tc_admin_menu', array( $this, 'w3tc_admin_menu' ), 10000 );
		add_action( 'w3tc_settings_page-w3tc_extensions',
			array( $this, 'w3tc_settings_page_w3tc_extensions' ) );

		if ( Util_Admin::is_w3tc_admin_page() ) {
			if ( isset( $_GET['extension'] ) && isset( $_GET['action'] ) ) {
				if ( in_array( $_GET['action'], array( 'activate', 'deactivate' ) ) ) {
					add_action( 'init', array( $this, 'change_extension_status' ) );
				}
			} elseif ( isset( $_POST['checked'] ) ) {
				add_action( 'admin_init', array( $this, 'change_extensions_status' ) );
			}
		}
	}

	/**
	 * Adds menu
	 *
	 * @param unknown $menu
	 * @return array
	 */
	public function w3tc_admin_menu( $menu ) {
		$menu_item = array(
			'w3tc_extensions' => array(
				'page_title' => __( 'Extensions', 'w3-total-cache' ),
				'menu_text' => __( 'Extensions', 'w3-total-cache' ),
				'visible_always' => false
			)
		);
		return array_merge( $menu, $menu_item );
	}

	/**
	 * Loads options page and corresponding view
	 */
	public function w3tc_settings_page_w3tc_extensions() {
		$o = new Extensions_Page();
		$o->render_content();
	}

	public function pre_update_option_active_plugins( $o ) {
		delete_option( 'w3tc_extensions_hooks' );

		return $o;
	}

	public function admin_init() {
		// used to load even inactive extensions if they want to
		$s = get_option( 'w3tc_extensions_hooks' );
		$hooks = @json_decode( $s, true );
		if ( !isset( $hooks['next_check_date'] ) ||
			$hooks['next_check_date'] < time() ) {
			$hooks = array(
				'actions' => array(),
				'filters' => array(),
				'next_check_date' => time() + 24 * 60 * 60
			);
			$hooks = apply_filters( 'w3tc_extensions_hooks', $hooks );
			update_option( 'w3tc_extensions_hooks', json_encode( $hooks ) );
		}

		if ( isset( $hooks['actions'] ) ) {
			foreach ( $hooks['actions'] as $hook => $actions_to_call ) {
				if ( is_array( $actions_to_call ) ) {
					add_action( $hook, function() use ( $actions_to_call ) {
							foreach ( $actions_to_call as $action )
							do_action( $action );
						} );
				}
			}
		}

		if ( isset( $hooks['filters'] ) ) {
			foreach ( $hooks['filters'] as $hook => $filters_to_call ) {
				if ( is_array( $filters_to_call ) ) {
					add_filter( $hook, function( $v ) use ( $filters_to_call ) {
							foreach ( $filters_to_call as $filter )
							$v = apply_filters( $filter, $v );

							return $v;
						} );
				}
			}
		}
	}

	/**
	 * Alters the active state of multiple extensions
	 */
	public function change_extensions_status() {
		$extensions = Util_Request::get_array( 'checked' );
		$action = Util_Request::get( 'action' );
		if ( '-1' == $action )
			$action = Util_Request::get( 'action2' );   // dropdown at bottom

		$message = '';
		if ( 'activate-selected' == $action ) {
			foreach ( $extensions as $extension ) {
				if ( Extensions_Util::activate_extension( $extension, $this->_config ) )
					$message .= '&activated=' . $extension;
			}
			wp_redirect( Util_Ui::admin_url( sprintf( 'admin.php?page=w3tc_extensions%s', $message ) ) );
		} elseif ( 'deactivate-selected' == $action ) {
			foreach ( $extensions as $extension ) {
				if ( Extensions_Util::deactivate_extension( $extension, $this->_config ) )
					$message .= '&deactivated=' . $extension;
			}
			wp_redirect( Util_Ui::admin_url( sprintf( 'admin.php?page=w3tc_extensions%s', $message ) ) );
		} else {
			wp_redirect( Util_Ui::admin_url( 'admin.php?page=w3tc_extensions' ) );
		}
	}

	/**
	 * Alters the active state of an extension
	 */
	public function change_extension_status() {
		$action = Util_Request::get_string( 'action' );

		if ( in_array( $action, array( 'activate', 'deactivate' ) ) ) {
			$extension = Util_Request::get_string( 'extension' );
			if ( 'activate' == $action ) {
				Extensions_Util::activate_extension( $extension, $this->_config );
				wp_redirect( Util_Ui::admin_url( sprintf( 'admin.php?page=w3tc_extensions&activated=%s', $extension ) ) );
			} elseif ( 'deactivate' == $action ) {
				Extensions_Util::deactivate_extension( $extension, $this->_config );
				wp_redirect( Util_Ui::admin_url( sprintf( 'admin.php?page=w3tc_extensions&deactivated=%s', $extension ) ) );
			}
		}
	}
}
