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
        w3tc_add_dashboard_widget('w3tc_latest', __('Latest release', 'w3-total-cache'), array(
            &$this,
            'widget_latest'
        ), null, 'side');
    }

    /**
     * Returns key for transient cache of "widget latest"
     *
     * @return string
     */
    function _widget_latest_cache_key() {
        return 'dash_' . md5('w3tc_latest_release');
    }

    /**
     * Prints latest widget contents
     *
     * @return void
     */
    function widget_latest() {
        if (false !== ($output = get_transient($this->_widget_latest_cache_key()))){
            echo $output;
        } else {
            echo '<p class="widget-loading hide-if-no-js {nonce: \''. wp_create_nonce('w3tc') .'\'}">';
            echo __( 'Loading&#8230;' );
            echo '</p>';
            echo '<p class="hide-if-js">'. __( 'This widget requires JavaScript.' ) .'</p>';
        }
    }

    /**
     * Prints latest widget contents
     *
     * @return void
     */
    function action_widget_latest_ajax() {
        // load content of feed
        global $wp_version;

        $items = array();
        $items_count = 1;

        if ($wp_version >= 2.8) {
            include_once (ABSPATH . WPINC . '/feed.php');
            $feed = fetch_feed(W3TC_FEED_RELEASES_URL);

            if (!is_wp_error($feed)) {
                $feed_items = $feed->get_items(0, $items_count);

                foreach ($feed_items as $feed_item) {
                    $items[] = array(
                        'link' => $feed_item->get_link(),
                        'title' => $feed_item->get_title(),
                        'description' => $feed_item->get_description(),
                    	'updated_date' => $feed_item->get_updated_date()
                    );
                }
            }
        } else {
            include_once (ABSPATH . WPINC . '/rss.php');
            $rss = fetch_rss(W3TC_FEED_RELEASES_URL);

            if (is_object($rss)) {
                $items = array_slice($rss->items, 0, $items_count);
            }
        }

        $result = '';
        
        foreach ($items as $item){
        	$result .= '<h4><a href="'. $item['link'] .'" target="_blank">'. $item['title'] .'<br><small>'. $item['updated_date'] .'</small></a></h4>';
        }

        // Default lifetime in cache of 12 hours (same as the feeds)
        if( !empty($result) ){
        	set_transient($this->_widget_latest_cache_key(), $result, 43200);
        }
        
        echo $result;
        die(); // otherwise in the response also appears a 0
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
