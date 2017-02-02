<?php
namespace W3TC;



class Generic_AdminActions_EdgeMode {

	private $_config = null;

	function __construct() {
		$this->_config = Dispatcher::config();
	}

	public function w3tc_edge_mode_enable() {
		$this->_config->set( 'common.edge', true );
		$this->_config->set( 'common.track_usage', true );
		$this->_config->save();

		Util_Admin::redirect( array( 'w3tc_note' => 'enabled_edge' ) );
	}

	public function w3tc_edge_mode_disable() {
		$this->_config->set( 'common.edge', false );
		$this->_config->set( 'common.track_usage', false );
		$this->_config->save();

		Util_Admin::redirect( array( 'w3tc_note' => 'disabled_edge' ) );
	}
}
