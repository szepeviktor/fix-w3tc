<?php
namespace W3TC;
/**
 * W3 Forum Widget
 */



/**
 * Class Generic_Plugin_WidgetServices
 */
class Generic_Plugin_WidgetServices {
	/**
	 * Array of request types
	 *
	 * @var array
	 */
	var $_request_types = array();
	var $_json_request_types = array();

	/**
	 * Array of request groups
	 *
	 * @var array
	 */
	var $_request_groups = array(
		'email_support',
		'phone_support',
		'plugin_config',
		'theme_config',
		'linux_config'
	);

	/**
	 * Request price list
	 *
	 * @var array
	 */
	var $_request_prices = array(
		'email_support' => 175,
		'phone_support' => 250,
		'plugin_config' => 200,
		'theme_config' => 350,
		'linux_config' => 450
	);

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
		if ( Util_Admin::get_current_wp_page() == 'w3tc_dashboard' )
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );

		$this->_json_request_types = array(
			'email_support' => sprintf( __( 'Less than 15 Minute Email Support Response %s', 'w3-total-cache' ), '(M-F 9AM - 5PM EDT): $175 USD' ),
			'phone_support' => sprintf( __( 'Less than 15 Minute Phone Support Response %s', 'w3-total-cache' ), '(M-F 9AM - 5PM EDT): $250 USD' ),
			'plugin_config' => sprintf( __( 'Professional Plugin Configuration %s', 'w3-total-cache' ), 'Starting @ $200 USD' ),
			'theme_config' => sprintf( __( 'Theme Performance Optimization & Plugin Configuration %s', 'w3-total-cache' ), 'Starting @ $350 USD' ),
			'linux_config' => sprintf( __( 'Linux Server Optimization & Plugin Configuration %s', 'w3-total-cache' ), 'Starting @ $450 USD' )
		);
		$this->_request_types = array(
			'email_support' => sprintf( __( 'Less than 15 Minute Email Support Response %s', 'w3-total-cache' ), '<br /><span>(M-F 9AM - 5PM EDT): $175 USD</span>' ),
			'phone_support' => sprintf( __( 'Less than 15 Minute Phone Support Response %s', 'w3-total-cache' ), '<br /><span>(M-F 9AM - 5PM EDT): $250 USD</span>' ),
			'plugin_config' => sprintf( __( 'Professional Plugin Configuration %s', 'w3-total-cache' ), '<br /><span>Starting @ $200 USD</span>' ),
			'theme_config' => sprintf( __( 'Theme Performance Optimization & Plugin Configuration %s', 'w3-total-cache' ), '<br /><span>Starting @ $350 USD</span>' ),
			'linux_config' => sprintf( __( 'Linux Server Optimization & Plugin Configuration %s', 'w3-total-cache' ), '<br /><span>Starting @ $450 USD</span>' )
		);
		add_action( 'w3tc_widget_setup', array(
				$this,
				'wp_dashboard_setup'
			) );
		add_action( 'w3tc_network_dashboard_setup', array(
				$this,
				'wp_dashboard_setup'
			) );

		if ( is_admin() ) {
			add_action( 'wp_ajax_w3tc_action_payment_code', array( $this, 'action_payment_code' ) );
		}
	}

	/**
	 * Dashboard setup action
	 *
	 * @return void
	 */
	function wp_dashboard_setup() {
		Util_Widget::add( 'w3tc_services',
			'<div class="w3tc-widget-services-logo"></div>' .
			'<div class="w3tc-widget-text">' .
			__( 'Premium Services', 'w3-total-cache' ) .
			'</div>',
			array( $this, 'widget_form' ),
			null, 'normal' );
	}

	function widget_form() {
		include W3TC_INC_WIDGET_DIR . '/services.php';
	}

	function action_payment_code() {


		$request_type = Util_Request::get_string( 'request_type' );

		$request_id = date( 'YmdHi' );
		$return_url = admin_url( 'admin.php?page=w3tc_support&request_type=' . $request_type . '&payment=1&request_id=' . $request_id );
		$cancel_url = admin_url( 'admin.php?page=w3tc_dashboard' );
		$form_values = array(
			"cmd" => "_xclick",
			"business" =>  W3TC_PAYPAL_BUSINESS,
			"item_name" => esc_attr( sprintf( '%s: %s (#%s)', ucfirst( Util_Environment::host() ), $this->_json_request_types[$request_type], $request_id ) ),
			"amount" => sprintf( '%.2f', $this->_request_prices[$request_type] ),
			"currency_code" => "USD",
			"no_shipping" => "1",
			"rm" => "2",
			"return" => esc_attr( $return_url ),
			"cancel_return" => esc_attr( $cancel_url ) );
		echo json_encode( $form_values );
		die();
	}

	public function enqueue() {
		wp_enqueue_style( 'w3tc-widget' );
		wp_enqueue_script( 'w3tc-metadata' );
		wp_enqueue_script( 'w3tc-widget' );
	}
}
