<?php
namespace W3TC;



class Extension_Swarmify_Plugin_Admin {
	/**
	 * @param unknown $extensions
	 * @param Config  $config
	 * @return mixed
	 */
	static public function w3tc_extensions( $extensions, $config ) {
		$extensions['swarmify'] = array (
			'name' => 'Swarmify',
			'author' => 'W3 EDGE',
			'description' =>  __( 'Optimize your video performance by enabling the Swarmify SmartVideo™ solution.', 'w3-total-cache' ),
			'author_uri' => 'https://www.w3-edge.com/',
			'extension_uri' => 'https://www.w3-edge.com/',
			'extension_id' => 'swarmify',
			'settings_exists' => true,
			'version' => '1.0',
			'enabled' => true,
			'requirements' => '',
			'active_frontend_own_control' => true,
			'path' => 'w3-total-cache/Extension_Swarmify_Plugin.php'
		);

		return $extensions;
	}



	function run() {
		add_action( 'w3tc_config_save', array( $this, 'w3tc_config_save' ), 10, 1 );

		add_action( 'admin_init_w3tc_dashboard', array(
				'\W3TC\Extension_Swarmify_Widget',
				'admin_init_w3tc_dashboard' ) );

		add_action( 'w3tc_extension_page_swarmify',
			array( $this, 'w3tc_extension_page_swarmify' ) );

		add_filter( 'w3tc_admin_actions', array( $this, 'w3tc_admin_actions' ) );

		add_filter( 'w3tc_notes', array( $this, 'w3tc_notes' ) );
	}



	public function w3tc_extension_page_swarmify() {
		$v = new Extension_Swarmify_Page();
		$v->render_content();
	}



	public function w3tc_admin_actions( $handlers ) {
		$handlers['swarmify'] = 'Extension_Swarmify_AdminActions';
		return $handlers;
	}



	public function w3tc_notes( $notes ) {
		$config = Dispatcher::config();
		$state = Dispatcher::config_state();

		$api_key = $config->get_string( array( 'swarmify', 'api_key' ) );
		$is_filled = !empty( $api_key );

		if ( !$is_filled && !$state->get_boolean( 'extension.swarmify.hide_note_activate_suggestion' ) ) {
			$notes['swarmify_activate_suggestion'] = sprintf(
				__( 'Just as the load time and overall performance of your website impacts user satisfaction, so does the performance of your online videos. Optimize your video performance by enabling the <a href="%s">Swarmify SmartVideo™ solution</a>. %s',
					'w3-total-cache' ),
				esc_attr( Extension_Swarmify_Core::signup_url() ),
				Util_Ui::button_hide_note2( array(
						'w3tc_default_config_state' => 'y',
						'key' => 'extension.swarmify.hide_note_activate_suggestion',
						'value' => 'true' ) ) );
		}

		return $notes;
	}



	public function w3tc_config_save( $config ) {
		// frontend activity
		$api_key = $config->get_string( array( 'swarmify', 'api_key' ) );
		$is_filled = !empty( $api_key );

		$config->set_extension_active_frontend( 'swarmify', $is_filled );
	}
}
