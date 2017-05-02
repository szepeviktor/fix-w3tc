<?php

/**
 * W3 ObjectCache plugin
 */
if (!defined('W3TC')) {
    die();
}

w3_require_once(W3TC_LIB_W3_DIR . '/Plugin.php');

/**
 * Class W3_Plugin_CloudFlare
 */
class W3_Plugin_CloudFlare extends W3_Plugin{
    /**
     * Runs plugin
     */
    function run() {
        add_action('wp_set_comment_status', array($this, 'set_comment_status'), 1, 2);
    }

    /**
     * @param $id
     * @param $status
     */
    function set_comment_status($id, $status) {
        $cf = w3_instance('W3_CloudFlare');
        $cf->report_if_spam($id, $status);
    }
}
