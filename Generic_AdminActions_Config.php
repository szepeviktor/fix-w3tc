<?php
namespace W3TC;



class Generic_AdminActions_Config {

	private $_config = null;

	function __construct() {
		$this->_config = Dispatcher::config();
	}

	/**
	 * Import config action
	 *
	 * @return void
	 */
	function w3tc_config_import() {
		$error = '';

		$config = new Config();

		if ( !isset( $_FILES['config_file']['error'] ) ||
			$_FILES['config_file']['error'] == UPLOAD_ERR_NO_FILE ) {
			$error = 'config_import_no_file';
		} elseif ( $_FILES['config_file']['error'] != UPLOAD_ERR_OK ) {
			$error = 'config_import_upload';
		} else {
			$imported = $config->import( $_FILES['config_file']['tmp_name'] );

			if ( !$imported ) {
				$error = 'config_import_import';
			}
		}

		if ( $error ) {
			Util_Admin::redirect( array(
					'w3tc_error' => $error
				), true );
			return;
		}

		Util_Admin::config_save( $this->_config, $config );
		Util_Admin::redirect( array(
				'w3tc_note' => 'config_import'
			), true );
	}

	/**
	 * Export config action
	 *
	 * @return void
	 */
	function w3tc_config_export() {
		$filename = substr( get_home_url(), strpos( get_home_url(), '//' )+2 );
		@header( sprintf( __( 'Content-Disposition: attachment; filename=%s.json', 'w3-total-cache' ), $filename ) );
		echo $this->_config->export();
		die();
	}

	/**
	 * Reset config action
	 *
	 * @return void
	 */
	function w3tc_config_reset() {
		$config = new Config();
		$config->set_defaults();
		Util_Admin::config_save( $this->_config, $config );

		$config_state = Dispatcher::config_state();
		$config_state->reset();
		$config_state->save();

		$config_state = Dispatcher::config_state_master();
		$config_state->reset();
		$config_state->save();

		Util_Admin::redirect( array(
				'w3tc_note' => 'config_reset'
			), true );
	}


	/**
	 * Save preview option
	 *
	 * @return void
	 */
	function w3tc_config_preview_enable() {
		$this->preview_production_copy( -1 );
		Util_Environment::set_preview( true );

		Util_Admin::redirect( array(
				'w3tc_note' => 'preview_enable'
			) );
	}

	/**
	 * Save preview option
	 *
	 * @return void
	 */
	function w3tc_config_preview_disable() {
		$blog_id = Util_Environment::blog_id();
		$preview_filename = Config::util_config_filename( $blog_id, true );
		@unlink( $preview_filename );

		Util_Environment::set_preview( false );

		Util_Admin::redirect( array(
				'w3tc_note' => 'preview_disable'
			) );
	}

	/**
	 * Deploy preview settings action
	 *
	 * @return void
	 */
	function w3tc_config_preview_deploy() {
		$this->preview_production_copy( 1 );
		Util_Environment::set_preview( false );

		Util_Admin::redirect( array(
				'w3tc_note' => 'preview_deploy'
			) );
	}



	/**
	 * Deploys the config file from a preview config file
	 *
	 * @param integer $direction     +1: preview->production
	 *                           -1: production->preview
	 * @param boolean $remove_source remove source file
	 */
	private function preview_production_copy( $direction = 1 ) {
		$blog_id = Util_Environment::blog_id();

		$preview_filename = Config::util_config_filename( $blog_id, true );
		$production_filename = Config::util_config_filename( $blog_id, false );

		if ( $direction > 0 ) {
			$src = $preview_filename;
			$dest = $production_filename;
		} else {
			$src = $production_filename;
			$dest = $preview_filename;
		}

		if ( !@copy( $src, $dest ) ) {
			Util_Activation::throw_on_write_error( $dest );
		}
	}



	/**
	 * Save dbcluster config action
	 *
	 * @return void
	 */
	function w3tc_config_dbcluster_config_save() {
		$params = array( 'page' => 'w3tc_general' );

		if ( !file_put_contents( W3TC_FILE_DB_CLUSTER_CONFIG,
				stripslashes( $_REQUEST['newcontent'] ) ) ) {
			try {
				Util_Activation::throw_on_write_error( W3TC_FILE_DB_CLUSTER_CONFIG );
			} catch ( \Exception $e ) {
				$error = $e->getMessage();
				Util_Admin::redirect_with_custom_messages( $params, array(
						'dbcluster_save_failed' => $error ) );
			}
		}

		Util_Admin::redirect_with_custom_messages( $params, null,
			array( 'dbcluster_save' => __( 'Database Cluster configuration file has been successfully saved', 'w3-total-cache' ) ) );
	}

	/**
	 * Save support us action
	 *
	 * @return void
	 */
	function w3tc_config_save_support_us() {
		$support = Util_Request::get_string( 'support' );
		$tweeted = Util_Request::get_boolean( 'tweeted' );
		$signmeup = Util_Request::get_boolean( 'signmeup' );
		$track_usage = Util_Request::get_boolean( 'track_usage' );
		$this->_config->set( 'common.support', $support );
		$this->_config->set( 'common.tweeted', $tweeted );
		if ( $track_usage )
			$this->_config->set( 'common.track_usage', true );
		else
			$this->_config->set( 'common.track_usage', false );

		if ( $signmeup ) {
			if ( Util_Environment::is_w3tc_enterprise( $this->_config ) )
				$license = 'enterprise';
			elseif ( Util_Environment::is_w3tc_pro( $this->_config ) )
				$license = 'pro';
			else
				$license = 'community';
			$email = filter_input( INPUT_POST, 'email', FILTER_SANITIZE_EMAIL );
			wp_remote_post( W3TC_MAILLINGLIST_SIGNUP_URL, array(
					'body' => array( 'email' => $email, 'license' => $license )
				) );
		}
		$this->_config->save();

		Generic_AdminLinks::link_update( $this->_config );

		Util_Admin::redirect( array(
				'w3tc_note' => 'config_save'
			) );
	}

	/**
	 * Update upload path action
	 *
	 * @return void
	 */
	function w3tc_config_update_upload_path() {
		update_option( 'upload_path', '' );

		Util_Admin::redirect();
	}

	public function w3tc_config_overloaded_disable( $http_key ) {
		$c = Dispatcher::config();
		$key = Util_Ui::config_key_from_http_name( $http_key );
		$c->set( $key, false );
		$c->save();

		Util_Admin::redirect( array() );
	}

	public function w3tc_config_overloaded_enable( $http_key ) {
		$c = Dispatcher::config();
		$key = Util_Ui::config_key_from_http_name( $http_key );
		$c->set( $key, true );
		$c->save();

		Util_Admin::redirect( array() );
	}
}
