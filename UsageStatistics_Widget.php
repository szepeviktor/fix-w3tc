<?php
namespace W3TC;

/**
 * widget with stats
 */
class UsageStatistics_Widget {
	private $enabled = false;

	public function init() {
		$c = Dispatcher::config();
		$this->enabled = ( $c->get_boolean( 'stats.enabled' ) &&
			Util_Environment::is_w3tc_pro( $c ) );

		add_action( 'admin_print_styles-toplevel_page_w3tc_dashboard',
			array( $this, 'admin_print_styles_w3tc_dashboard' ) );

		if ( $this->enabled )
			add_action( 'admin_print_scripts-toplevel_page_w3tc_dashboard',
				array( $this, 'admin_print_scripts_w3tc_dashboard' ) );

		add_action( 'w3tc_widget_setup', array(
				$this,
				'w3tc_widget_setup'
			), 300 );
		add_action( 'w3tc_ajax_ustats_get', array( $this, 'w3tc_ajax_ustats_get' ) );
	}



	public function w3tc_widget_setup() {
		Util_Widget::add( 'w3tc_usage_statistics',
			'<div class="w3tc-widget-w3tc-logo"></div>' .
			'<div class="w3tc-widget-text">' .
			__( 'Caching Statistics', 'w3-total-cache' ) .
			'</div>',
			array( $this, 'widget_form' ),
			Util_Ui::admin_url( 'admin.php?page=w3tc_general#miscellaneous' ),
			'normal' );
	}



	public function widget_form() {
		$storage = new UsageStatistics_StorageReader();
		$summary_promise = $storage->get_history_summary_promise();

		if ( $this->enabled )
			include  W3TC_DIR . '/UsageStatistics_Widget_View.php';
		else
			include  W3TC_DIR . '/UsageStatistics_Widget_View_Disabled.php';
	}



	public function admin_print_styles_w3tc_dashboard() {
		wp_enqueue_style( 'w3tc-widget' );
		wp_enqueue_style( 'w3tc-widget-usage-statistics',
			plugins_url( 'UsageStatistics_Widget_View.css', W3TC_FILE ),
			array(), W3TC_VERSION );
	}



	public function admin_print_scripts_w3tc_dashboard() {
		wp_enqueue_script( 'w3tc-widget-usage-statistics',
			plugins_url( 'UsageStatistics_Widget_View.js', W3TC_FILE ),
			array(), W3TC_VERSION );
	}



	public function w3tc_ajax_ustats_get() {
		$storage = new UsageStatistics_StorageReader();
		$summary = $storage->get_history_summary();

		echo json_encode( $summary );
		exit();
	}
}
