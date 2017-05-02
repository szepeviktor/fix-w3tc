<?php
namespace W3TC;

/**
 * Setups Google PageSpeed dashboard widget
 */
class PageSpeed_Plugin_Widget {
	public function run() {
		add_filter( 'w3tc_monitoring_score', array(
				$this,
				'w3tc_monitoring_score' ) );

		add_action( 'admin_init_w3tc_dashboard', array(
				$this,
				'admin_init_w3tc_dashboard' ) );
		add_action( 'w3tc_ajax', array(
				$this,
				'w3tc_ajax' ) );
	}



	public function w3tc_ajax() {
		add_action( 'w3tc_ajax_pagespeed_widgetdata', array(
				$this, 'w3tc_ajax_pagespeed_widgetdata' ) );
	}



	public function admin_init_w3tc_dashboard() {
		add_action( 'w3tc_widget_setup',
			array( $this, 'wp_dashboard_setup' ), 500 );
		add_action( 'w3tc_network_dashboard_setup',
			array( $this, 'wp_dashboard_setup' ), 500 );

		wp_enqueue_script( 'w3tc-widget-pagespeed',
			plugins_url( 'PageSpeed_Widget_View.js', W3TC_FILE ),
			array(), W3TC_VERSION );
		wp_enqueue_style( 'w3tc-widget-pagespeed',
			plugins_url( 'PageSpeed_Widget_View.css', W3TC_FILE ),
			array(), W3TC_VERSION );
	}



	/**
	 * Dashboard setup action
	 *
	 * @return void
	 */
	public function wp_dashboard_setup() {
		Util_Widget::add( 'w3tc_pagespeed',
			'<div class="w3tc-widget-pagespeed-logo"></div>' .
			'<div class="w3tc-widget-text">' .
			__( 'Page Speed Report', 'w3-total-cache' ) .
			'</div>',
			array( $this, 'widget_pagespeed' ),
			Util_Ui::admin_url( 'admin.php?page=w3tc_general#miscellaneous' ),
			'normal' );
	}



	/**
	 * PageSpeed widget
	 *
	 * @return void
	 */
	public function widget_pagespeed() {
		$config = Dispatcher::config();
		$key = $config->get_string( 'widget.pagespeed.key' );

		if ( empty( $key ) )
			include W3TC_DIR . '/PageSpeed_Widget_View_NotConfigured.php';
		else
			include W3TC_DIR . '/PageSpeed_Widget_View.php';
	}



	public function w3tc_ajax_pagespeed_widgetdata() {
		if ( Util_Request::get( 'cache' ) != 'no' ) {
			$response = get_transient( 'w3tc_pagespeed_widgetdata' );
			$response = @json_decode( $response, true );
			if ( is_array( $response ) && isset( $response['time'] ) &&
				$response['time'] >= time() - 60 ) {
				echo json_encode( $response );
				return;
			}
		}

		$config = Dispatcher::config();
		$key = $config->get_string( 'widget.pagespeed.key' );
		$ref = $config->get_string( 'widget.pagespeed.key.restrict.referrer' );
		
		$w3_pagespeed = new PageSpeed_Api( $key, $ref );
		$r = $w3_pagespeed->analyze( get_home_url() );

		if ( !$r ) {
			echo json_encode( array( 'error' => 'API call failed' ) );
			return;
		}

		$details = '<ul class="w3tc-widget-ps-rules">';
		foreach ( $r['rules'] as $index => $rule ) {
			if ( $index >= 5 )
				break;

			$details .=
				'<li class="w3tc-widget-ps-rule w3tc-widget-ps-priority-' .
				$rule['priority'] . '">' .
				'<div class="w3tc-widget-ps-icon"><div></div></div>' .
				'<p>' . $rule['name'] . '</p>' .
				'</li>';
		}

		$details .= '</ul>';

		$response = array(
			'score' => $r['score'] . ' / 100',
			'details' => $details,
			'time' => time()
		);

		set_transient( 'w3tc_pagespeed_widgetdata', json_encode( $response ), 60 );
		echo json_encode( $response );
	}



	public function w3tc_monitoring_score( $score ) {
		$url = $_SERVER['HTTP_REFERER'];

		$config = Dispatcher::config();
		$key = $config->get_string( 'widget.pagespeed.key' );
		$ref = $config->get_string( 'widget.pagespeed.key.restrict.referrer' );
		$w3_pagespeed = new PageSpeed_Api( $key, $ref );

		$r = $w3_pagespeed->analyze( $url );

		if ( $r )
			$score .= $r['score'] . ' / 100';

		return $score;
	}
}
