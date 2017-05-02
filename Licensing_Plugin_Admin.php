<?php
namespace W3TC;

class Licensing_Plugin_Admin {
	private $site_inactivated = false;
	private $site_activated = false;
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
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'wp_ajax_w3tc_verify_plugin_license_key', array( $this, 'action_verify_plugin_license_key' ) );
		add_action( "w3tc_config_ui_save-w3tc_general", array( $this, 'possible_state_change' ), 2, 10 );

		add_action( 'w3tc_message_action_licensing_upgrade',
			array( $this, 'w3tc_message_action_licensing_upgrade' ) );

		if ( !Util_Environment::is_w3tc_pro( $this->_config ) )
			add_filter( 'w3tc_admin_bar_menu', array( $this, 'w3tc_admin_bar_menu' ) );
	}



	public function w3tc_admin_bar_menu( $menu_items ) {
		$menu_items['00020.licensing'] = array(
			'id' => 'w3tc_overlay_upgrade',
			'parent' => 'w3tc',
			'title' => __(
				'<span style="color: red; background: none;">Upgrade Performance</span>',
				'w3-total-cache'
			),
			'href' => wp_nonce_url( network_admin_url(
					'admin.php?page=w3tc_dashboard&amp;' .
					'w3tc_message_action=licensing_upgrade' ), 'w3tc' )
		);

		if ( defined( 'W3TC_DEBUG' ) && W3TC_DEBUG ) {
			$menu_items['90040.licensing'] = array(
				'id' => 'w3tc_overlay_upgrade',
				'parent' => 'w3tc_debug_overlays',
				'title' => __( 'Upgrade', 'w3-total-cache' ),
				'href' => wp_nonce_url( network_admin_url(
						'admin.php?page=w3tc_dashboard&amp;' .
						'w3tc_message_action=licensing_upgrade' ), 'w3tc' )
			);
		}

		return $menu_items;
	}

	public function w3tc_message_action_licensing_upgrade() {
		add_action( 'admin_head', array( $this, 'admin_head_licensing_upgrade' ) );
	}

	public function admin_head_licensing_upgrade() {
?>
        <script type="text/javascript">
        jQuery(function() {
	        w3tc_lightbox_upgrade(w3tc_nonce);
	        jQuery('#w3tc-license-instruction').show();
	    });
       	</script>
    	<?php
	}

	/**
	 *
	 *
	 * @param Config  $config
	 * @param Config  $old_config
	 */
	function possible_state_change( $config, $old_config ) {
		if ( $old_config->get_string( 'plugin.license_key' ) !='' &&  $config->get_string( 'plugin.license_key' ) == '' ) {
			$result = Licensing_Core::deactivate_license( $old_config->get_string( 'plugin.license_key' ) );
			if ( $result ) {
				$this->site_inactivated = true;
			}
			delete_transient( 'w3tc_license_status' );
		} elseif ( $old_config->get_string( 'plugin.license_key' ) =='' &&  $config->get_string( 'plugin.license_key' ) != '' ) {
			$result = Licensing_Core::activate_license( $config->get_string( 'plugin.license_key' ), W3TC_VERSION );
			if ( $result ) {
				$this->site_activated = true;
				$config->set( 'common.track_usage', true );
			}
			delete_transient( 'w3tc_license_status' );
		} elseif ( $old_config->get_string( 'plugin.license_key' ) != $config->get_string( 'plugin.license_key' ) ) {
			$result = Licensing_Core::activate_license( $config->get_string( 'plugin.license_key' ), W3TC_VERSION );
			if ( $result ) {
				$this->site_activated = true;
			}
			delete_transient( 'w3tc_license_status' );
		}
	}

	/**
	 * Setup notices actions
	 */
	function admin_init() {
		$capability = apply_filters( 'w3tc_capability_admin_notices',
			'manage_options' );

		if ( current_user_can( $capability ) ) {
			if ( is_admin() && Util_Admin::is_w3tc_admin_page() ) {
				/**
				 * Only admin can see W3TC notices and errors
				 */
				if ( !Util_Environment::is_wpmu() ) {
					add_action( 'admin_notices', array(
							$this,
							'admin_notices'
						), 1, 1 );
				}
				add_action( 'network_admin_notices', array(
						$this,
						'admin_notices'
					), 1, 1 );
			}
		}
	}

	private function _status_is( $s, $starts_with ) {
		$s .= '.';
		$starts_with .= '.';
		return substr( $s, 0, strlen( $starts_with ) ) == $starts_with;
	}



	/**
	 * Run license status check and display messages
	 */
	function admin_notices() {
		$message = '';
		$status = get_transient( 'w3tc_license_status' );
		$set_transient = false;
		if ( !$status ) {
			$status = $this->update_license_status();
			$set_transient = true;
			$transient_timeout = 3600 * 24 * 5;
		}


		if ( $status == 'no_key' ) {
		} elseif ( $this->_status_is( $status, 'inactive.expired' ) ) {
			$message = sprintf( __( 'The W3 Total Cache license key has expired. Please renew it: %s', 'w3-total-cache' ),
				'<input type="button" class="button-primary button-buy-plugin {nonce: \''. wp_create_nonce( 'w3tc' ).'\'}" value="'.__( 'Renew', 'w3-total-cache' ) . '" />' );
		} elseif ( $this->_status_is( $status, 'invalid' ) ) {
			$message = __( 'The W3 Total Cache license key you entered is not valid.', 'w3-total-cache' ) .
				'<a href="' . ( is_network_admin() ? network_admin_url( 'admin.php?page=w3tc_general#licensing' ):
				admin_url( 'admin.php?page=w3tc_general#licensing' ) ) . '"> ' . __( 'Please enter it again.', 'w3-total-cache' ) . '</a>';
		} elseif ( $this->_status_is( $status, 'inactive.by_rooturi.activations_limit_not_reached' ) ) {
			$message = __( 'The W3 Total Cache license key is not active for this site.', 'w3-total-cache' );
		} elseif ( $this->_status_is( $status, 'inactive.by_rooturi' ) ) {
			$message = __( 'The W3 Total Cache license key is not active for this site. ', 'w3-total-cache' ) .
				sprintf(
				__( 'You can switch your license to this website following <a class="w3tc_licensing_reset_rooturi" href="%s">this link</a>', 'w3-total-cache' ),
				Util_Ui::url( array( 'page' => 'w3tc_general', 'w3tc_licensing_reset_rooturi' => 'y' ) )
			);
		} elseif ( $this->_status_is( $status, 'inactive' ) ) {
			$message = __( 'The W3 Total Cache license key is not active.', 'w3-total-cache' );
		} elseif ( $this->_status_is( $status, 'active' ) ) {
		} else {
			$message = __( 'The W3 Total Cache license key can\'t be verified.', 'w3-total-cache' );
			$transient_timeout = 60;
		}

		if ( $set_transient ) {
			set_transient( 'w3tc_license_status', $status, $transient_timeout );
		}

		if ( $message )
			Util_Ui::error_box( sprintf( "<p>$message. <a class='w3tc_licensing_check' href='%s'>" . __( 'check again' ) . '</a></p>',
					Util_Ui::url( array( 'page' => 'w3tc_general', 'w3tc_licensing_check_key' => 'y' ) ) )
			);


		if ( $this->site_inactivated ) {
			Util_Ui::error_box( "<p>" . __( 'The W3 Total Cache license key is deactivated for this site.', 'w3-total-cache' ) ."</p>" );
		}

		if ( $this->site_activated ) {
			Util_Ui::error_box( "<p>" . __( 'The W3 Total Cache license key is activated for this site.', 'w3-total-cache' ) ."</p>" );
		}
	}

	/**
	 *
	 *
	 * @return string
	 */
	function update_license_status() {
		$status = '';
		$license_key = $this->get_license_key();

		$old_plugin_type = $this->_config->get_string( 'plugin.type' );
		$plugin_type = '';

		if ( !empty( $license_key ) || defined( 'W3TC_LICENSE_CHECK' ) ) {
			$license = Licensing_Core::check_license( $license_key, W3TC_VERSION );

			if ( $license ) {
				$status = $license->license_status;
				if ( $this->_status_is( $status, 'active' ) ) {
					$plugin_type = 'pro';
				} elseif ( $this->_status_is( $status, 'inactive.by_rooturi' ) &&
					Util_Environment::is_w3tc_pro_dev() ) {
					$status = 'valid';
					$plugin_type = 'pro_dev';
				}
			}

			$this->_config->set( 'plugin.type', $plugin_type );
		} else {
			$status = 'no_key';
		}

		if ( $old_plugin_type != $plugin_type ) {
			try {
				$this->_config->set( 'plugin.type', $plugin_type );
				$this->_config->save();
			} catch ( \Exception $ex ) {
			}
		}
		return $status;
	}

	/**
	 *
	 *
	 * @return string
	 */
	function get_license_key() {
		$license_key = $this->_config->get_string( 'plugin.license_key', '' );
		if ( $license_key == '' )
			$license_key = ini_get( 'w3tc.license_key' );
		return $license_key;
	}

	function action_verify_plugin_license_key() {
		$license = Util_Request::get_string( 'license_key', '' );

		if ( $license ) {
			$status = Licensing_Core::check_license( $license, W3TC_VERSION );
			echo $status->license_status;
		} else {
			echo 'invalid';
		}
		exit();
	}
}
