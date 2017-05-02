<?php

/**
 * W3 Total Cache plugin
 */
if (!defined('W3TC')) {
    die();
}

w3_require_once(W3TC_INC_DIR . '/functions/file.php');
w3_require_once(W3TC_LIB_W3_DIR . '/Plugin.php');

/**
 * Class W3_Plugin_TotalCacheActivation
 */
class W3_Plugin_TotalCacheActivation extends W3_Plugin {
    /**
     * Activate plugin action
     *
     * @return void
     */
    function activate($network_wide) {
        w3_require_once(W3TC_INC_DIR . '/functions/activation.php');

        if (w3_is_network()) {
            if ($network_wide) {
                // we are in network activation
            } else if ($_GET['action'] == 'error_scrape' && 
                    strpos($_SERVER['REQUEST_URI'], '/network/') !== false) {
                // workaround for error_scrape page called after error
                // really we are in network activation and going to throw some error
            } else {
                echo 'Please <a href="' . network_admin_url('plugins.php') . '">network activate</a> W3 Total Cache when using WordPress Multisite.';
                die;
            }
        }

        /**
         * Create cache folder and extension files
         */
        try {
            w3_activation_create_required_files();
            
            if (!$this->_config->own_config_exists()) {
                $this->_config->save();
            }
            
            // save admin config
            $admin_config = w3_instance('W3_ConfigAdmin');
            if (!$admin_config->own_config_exists())
                $admin_config->save();
        } catch (Exception $e) {
            w3_activation_error_on_exception($e);
        }

        delete_option('w3tc_request_data');
        add_option('w3tc_request_data', '', null, 'no');
    }

    /**
     * Deactivate plugin action
     *
     * @return void
     */
    function deactivate() {
        delete_option('w3tc_request_data');

        w3_rmdir(W3TC_CACHE_DIR);
    }
}
