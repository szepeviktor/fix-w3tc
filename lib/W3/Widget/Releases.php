<?php
/**
 * W3 Releases Widget
 */
if (!defined('W3TC')) {
    die();
}

w3_require_once(W3TC_LIB_W3_DIR . '/Plugin.php');
w3_require_once(W3TC_INC_DIR . '/functions/widgets.php');

/**
 * Class W3_Widget_Releases
 */
class W3_Widget_Releases extends W3_Plugin {

    function run() {
        w3_require_once(W3TC_INC_FUNCTIONS_DIR . '/admin.php');
        if(w3tc_get_current_wp_page() == 'w3tc_dashboard')
            add_action('admin_enqueue_scripts', array($this,'enqueue'));

        add_action('w3tc_dashboard_setup', array(
            &$this,
            'wp_dashboard_setup'
        ));
        add_action('w3tc_network_dashboard_setup', array(
            &$this,
            'wp_dashboard_setup'
        ));

        if (is_admin()) {
            add_action('wp_ajax_w3tc_widget_latest_ajax', array($this, 'action_widget_latest_ajax'));
        }
    }

    /**
     * Dashboard setup action
     *
     * @return void
     */
    function wp_dashboard_setup() {

    }
    
    /**
     * Enqueue style and scripts.
     * 
     * @return void
     */
    public function enqueue() {
        wp_enqueue_style('w3tc-widget');
        wp_enqueue_script('w3tc-metadata');
        wp_enqueue_script('w3tc-widget');
    }
}
