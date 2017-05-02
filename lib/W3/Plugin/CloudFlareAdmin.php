<?php

/**
 * W3 CloudFlareAdmin plugin
 */
if (!defined('W3TC')) {
    die();
}

w3_require_once(W3TC_LIB_W3_DIR . '/Plugin.php');

/**
 * Class W3_Plugin_CloudFlareAdmin
 */
class W3_Plugin_CloudFlareAdmin extends W3_Plugin{
    function run() {
        $this->check_ip_versions();

        w3_require_once(W3TC_LIB_W3_DIR . '/Request.php');
        $page = W3_Request::get_string('page');
        if ($page && strpos($page, 'w3tc_') !== false) {
            /**
             * Only admin can see W3TC notices and errors
             */
            add_action('admin_notices', array(
                &$this,
                'admin_notices'
            ));
            add_action('network_admin_notices', array(
                &$this,
                'admin_notices'
            ));
        }
        add_action('wp_ajax_w3tc_cloudflare_api_request', array($this, 'action_cloudflare_api_request'));

    }

    /**
     * Check if last check has expired. If so update CloudFlare ips
     */
    function check_ip_versions() {
        $checked = get_transient('w3tc_cloudflare_ip_check');

        if (false === $checked) {
            $cf = w3_instance('W3_CloudFlare');
            try {
                $cf->update_ip_ranges();
            } catch (Exception $ex) {}
            set_transient('w3tc_cloudflare_ip_check', time(), 3600*24);
        }
    }

    function admin_notices() {
        $plugins = get_plugins();
        if (array_key_exists('cloudflare/cloudflare.php', $plugins) && $this->_config->get_boolean('notes.cloudflare_plugin')) {
            w3_require_once(W3TC_INC_FUNCTIONS_DIR . '/other.php');
            echo sprintf('<div class="error"><p>%s %s</p></div>', __('CloudFlare plugin detected. We recommend removing the
            plugin as it offers no additional capabilities when W3 Total Cache is installed. This message will disappear
            when CloudFlare is removed.', 'w3-total-cache'),
                w3tc_button_hide_note('Hide this message', 'cloudflare_plugin')
            );
        }
    }


    /**
     * Send CloudFlare API request
     *
     * @return void
     */
    function action_cloudflare_api_request() {
        $result = false;
        $response = null;

        $actions = array(
            'devmode',
            'sec_lvl',
            'fpurge_ts'
        );

        w3_require_once(W3TC_LIB_W3_DIR . '/Request.php');

        $email = W3_Request::get_string('email');
        $key = W3_Request::get_string('key');
        $zone = W3_Request::get_string('zone');
        $action = W3_Request::get_string('command');
        $value = W3_Request::get_string('value');
        $nonce = W3_Request::get_string('_wpnonce');

        if (!wp_verify_nonce($nonce, 'w3tc')) {
            $error ='Access denied.';
        } elseif (!$email) {
            $error = 'Empty email.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email.';
        } elseif (!$key) {
            $error = 'Empty key.';
        } elseif (!$zone) {
            $error = 'Empty zone.';
        } elseif (strpos($zone, '.') === false) {
            $error = 'Invalid domain.';
        } elseif (!in_array($action, $actions)) {
            $error = 'Invalid action.';
        } else {
            $config = array(
                'email' => $email,
                'key' => $key,
                'zone' => $zone
            );

            w3_require_once(W3TC_LIB_W3_DIR . '/CloudFlare.php');
            @$w3_cloudflare = new W3_CloudFlare($config);

            @set_time_limit($this->_config->get_integer('timelimit.cloudflare_api_request'));

            $response = $w3_cloudflare->api_request($action, $value);

            if ($response) {
                if ($response->result == 'success') {
                    $result = true;
                    $error = 'OK';
                } else {
                    $error = $response->msg;
                }
            } else {
                $error = 'Unable to make CloudFlare API request.';
            }
        }

        $return = array(
            'result' => $result,
            'error' => $error,
            'response' => $response
        );

        echo json_encode($return);
        exit;
    }
}
