<?php
namespace W3TC;

/**
 * W3 Total Cache plugin
 */




/**
 * class Root_AdminActivation
 */
class Root_AdminActivation {
	/**
	 * Activate plugin action
	 *
	 * @param bool    $network_wide
	 * @return void
	 */
	static public function activate( $network_wide ) {
		// decline non-network activation at WPMU
		if ( Util_Environment::is_wpmu() ) {
			if ( $network_wide ) {
				// we are in network activation
			} else if ( $_GET['action'] == 'error_scrape' &&
					strpos( $_SERVER['REQUEST_URI'], '/network/' ) !== false ) {
					// workaround for error_scrape page called after error
					// really we are in network activation and going to throw some error
				} else {
				echo 'Please <a href="' . network_admin_url( 'plugins.php' ) . '">network activate</a> W3 Total Cache when using WordPress Multisite.';
				die;
			}
		}

		try {
			$e = Dispatcher::component( 'Root_Environment' );

			$config = Dispatcher::config();
			$e->fix_on_event( $config, 'activate' );

			Generic_AdminLinks::link_update( $config );

			// try to save config file if needed, optional thing so exceptions
			// hidden
			if ( !file_exists( Config::util_config_filename( 0, false ) ) ) {
				try {
					// create folders
					$e->fix_in_wpadmin( $config );
				} catch ( \Exception $ex ) {
				}

				try {
					Util_Admin::config_save( Dispatcher::config(), $config );
				} catch ( \Exception $ex ) {
				}
			}
		} catch ( \Exception $e ) {
			Util_Activation::error_on_exception( $e );
		}
	}

	/**
	 * Deactivate plugin action
	 *
	 * @return void
	 */
	static public function deactivate() {
		try {
			Util_Activation::enable_maintenance_mode();
		} catch ( \Exception $ex ) {
		}

		try {
			$e = Dispatcher::component( 'Root_Environment' );
			$e->fix_after_deactivation();

			Generic_AdminLinks::link_delete();
		} catch ( Util_Environment_Exceptions $exs ) {
			$r = Util_Activation::parse_environment_exceptions( $exs );

			if ( strlen( $r['required_changes'] ) > 0 ) {
				$changes_style = 'border: 1px solid black; ' .
					'background: white; ' .
					'margin: 10px 30px 10px 30px; ' .
					'padding: 10px;';

				$error = '<strong>W3 Total Cache Error:</strong> ' .
					'Files and directories could not be automatically ' .
					'removed to complete the deactivation. ' .
					'<br />Please execute commands manually:<br />' .
					'<div style="' . $changes_style . '">' .
					$r['required_changes'] . '</div>';

				// this is not shown since wp redirects from that page
				// not solved now
				echo '<div class="error"><p>' . $error . '</p></div>';
			}
		}

		try {
			Util_Activation::disable_maintenance_mode();
		} catch ( \Exception $ex ) {
		}
	}
}
