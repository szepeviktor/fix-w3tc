<?php
namespace W3TC;



class Extension_Swarmify_AdminActions {
	public function w3tc_swarmify_set_key() {
		if ( isset( $_REQUEST['status'] ) && isset( $_REQUEST['swarmcdnkey'] ) && $_REQUEST['status'] == '1' ) {
			$config = Dispatcher::config();
			$config->set( array( 'swarmify', 'api_key' ), $_REQUEST['swarmcdnkey'] );
			$config->save();
		}

		Util_Environment::redirect( Util_Ui::admin_url(
			'admin.php?page=w3tc_extensions&extension=swarmify&action=view' ) );
		exit();
	}
}
