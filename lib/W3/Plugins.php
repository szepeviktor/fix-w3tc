<?php

class W3_Plugins {
    /**
     * Enabled Plugins that has been run
     * @var W3_Plugin[]
     */
    private $_loaded_plugins = array();

    /**
     * List of plugins and criterias to be met for them to run
     * @var array
     */
    private $_plugins = array();

    /**
     * @var null|W3_Config
     */
    private $_config = null;

    function __construct() {
        $this->_plugins = array(
            array('class_name' => 'W3_Plugin_DbCache', 'enable_options' => 'dbcache.enabled'),
            array('class_name' => 'W3_Plugin_ObjectCache', 'enable_options' => 'objectcache.enabled'),
            array('class_name' => 'W3_Pro_Plugin_FragmentCache', 'enable_options' => 'fragmentcache.enabled'),
            array('class_name' => 'W3_Plugin_PgCache', 'enable_options' => 'pgcache.enabled'),
            array('class_name' => 'W3_Plugin_Cdn', 'enable_options' => 'cdn.enabled'),
            array('class_name' => 'W3_Plugin_CdnCache', 'enable_options' => array('cdn.enabled', 'cdncache.enabled')),
            array('class_name' => 'W3_Plugin_CloudFlare', 'enable_options' => 'cloudflare.enabled'),
            array('class_name' => 'W3_Plugin_BrowserCache', 'enable_options' => 'browsercache.enabled'),
            array('class_name' => 'W3_Plugin_Minify', 'enable_options' => 'minify.enabled'),
            array('class_name' => 'W3_Plugin_Varnish', 'enable_options' => 'varnish.enabled'),
            array('class_name' => 'W3_Plugin_NewRelic', 'enable_options' => 'newrelic.enabled')
        );
        if (is_admin()) {
		    $this->_plugins[] = array('class_name' => 'W3_Plugin_CloudFlareAdmin', 'enable_options' => 'cloudflare.enabled');
            $this->_plugins[] = array('class_name' => 'W3_Plugin_TotalCacheAdmin', 'enable_options' => null);
            $this->_plugins[] = array('class_name' => 'W3_Plugin_NewRelicAdmin', 'enable_options' => null);
            $this->_plugins[] = array('class_name' => 'W3_Widget_SpreadTheWord', 'enable_options' => null);
            $this->_plugins[] = array('class_name' => 'W3_Widget_Services', 'enable_options' => null);
            $this->_plugins[] = array('class_name' => 'W3_Widget_News', 'enable_options' => null);
            $this->_plugins[] = array('class_name' => 'W3_Widget_Forum', 'enable_options' => null);
            $this->_plugins[] = array('class_name' => 'W3_Widget_PageSpeed', 'enable_options' => 'widget.pagespeed.enabled');
            $this->_plugins[] = array('class_name' => 'W3_Widget_NewRelic', 'enable_options' => null);
        }
        $this->_config = w3_instance('W3_Config');

        $this->_load_plugins();
        register_activation_hook(W3TC_FILE, array(
            &$this,
            'activate'
        ));

        register_deactivation_hook(W3TC_FILE, array(
            &$this,
            'deactivate'
        ));
    }

    /**
     * Run plugins
     */
    function run() {
        foreach ($this->_loaded_plugins as $plugin) {
            $plugin->run();
        }
    }

    /**
     * Returns an array[filename]=rules of rules for .htaccess or nginx files
     * @return array
     */
    function get_required_rules() {
        $rewrite_rules_descriptors = array();
        $rules = array();
        foreach ($this->_loaded_plugins as $plugin) {
            $admin = $plugin->get_admin();
            if ($admin) {
                $required_rules = $admin->get_required_rules();

                if ($required_rules) {
                    if ($plugin instanceof W3_Plugin_PgCache) {
                        $rules = array_merge($rules, $required_rules);
                    } else {
                        foreach ($required_rules as $descriptor) {
                            $filename = $descriptor['filename'];
                            $content = isset($rewrite_rules_descriptors[$filename]) ? $rewrite_rules_descriptors[$filename]['content'] : '';
                            $rewrite_rules_descriptors[$filename] = array('filename' => $filename, 'content' => $content . $descriptor['content']);
                        }
                    }
                }
            }
        }
        if ($rules) {
            foreach ($rules as $descriptor) {
                $filename = $descriptor['filename'];
                $content = isset($rewrite_rules_descriptors[$filename]) ? $rewrite_rules_descriptors[$filename]['content'] : '';
                $rewrite_rules_descriptors[$filename] = array('filename' => $filename, 'content' => $content . $descriptor['content']);
            }
        }
        ksort($rewrite_rules_descriptors);
        reset($rewrite_rules_descriptors);
        return $rewrite_rules_descriptors;
    }

    /**
     * Instantiate all plugins
     */
    private function _load_plugins() {
        foreach ($this->_plugins as $plugin) {
            $this->_load_plugin($plugin);
        }
    }

    /**
     * Instantiate plugin
     * @param $plugin_descriptor array('class_name' => '', 'enable_options' => '')
     */
    private function _load_plugin($plugin_descriptor) {
        $criteria = $plugin_descriptor['enable_options'];
        if (is_array($criteria)){
            $enabled = true;
            foreach ($criteria as $val) {
                $enabled = $enabled && $this->_config->get_boolean($val);
            }
        } else {
            $enabled = is_null($criteria) || $this->_config->get_boolean($criteria);
        }

        if ($enabled) {
            $plugin = w3_instance($plugin_descriptor['class_name']);
            $this->_loaded_plugins[] = $plugin;
        }
    }

    /**
     * Run activate on loaded plugins
     */
    public function activate() {
        foreach ($this->_loaded_plugins as $plugin)
            $plugin->activate();
    }

    /**
     * Run deactivate on loaded plugins
     */
    public function deactivate() {
        w3_require_once(W3TC_INC_DIR . '/functions/activation.php');
        $ftp_form = null;
        try {
            w3_enable_maintenance_mode();
        } catch(Exception $ex){}

        $errors = array('errors' => array());
        try {
            if (!$this->_config->get_boolean('pgcache.enabled')) {
                $plugin = w3_instance('W3_Plugin_PgCache');
                $this->_loaded_plugins[] = $plugin;
            }

            foreach ($this->_loaded_plugins as $plugin) {
                $result = $plugin->deactivate();
                if ($result) {
                    $plugin_errors = isset($result['errors_short_form']) ? $result['errors_short_form'] : $result['errors'];
                    $errors['errors'] = array_merge($errors['errors'], $plugin_errors);
                    if (isset($result['ftp_form']))
                        $errors['ftp_form'] = $result['ftp_form'];
                }
            }
        } catch (Exception $ex) {
            $errors['errors'][] = $ex->getMessage();
        }

        $result = $this->delete_files_and_folders();
        try {
            w3_disable_maintenance_mode();
        } catch(Exception $ex){}
        if ($result['errors']) {
            $errors['errors'] = array_merge($errors['errors'], $result['errors']);
            if (isset($result['ftp_form']))
                $errors['ftp_form'] = $result['ftp_form'];
        }

        return $errors;
    }

    private function delete_files_and_folders() {
        $ftp_form = null;
        $errors = array();

        $dirs = array(W3TC_CACHE_DIR);
        $files = array(W3TC_ADDIN_FILE_DB, W3TC_ADDIN_FILE_OBJECT_CACHE, W3TC_ADDIN_FILE_ADVANCED_CACHE);

        if (file_exists(W3TC_WP_LOADER))
            $files[] = W3TC_WP_LOADER;

        foreach ($files as $file) {
            try {
                w3_wp_delete_file($file);
            } catch(Exception $e) {
                if ($e instanceof FilesystemCredentialException)
                    $ftp_form = $e->ftp_form();
            }
            if (file_exists($file))
                $errors[] = sprintf('Delete file: <strong>%s</strong>', $file);

        }
        try {
            @set_time_limit(60);
            w3_wp_delete_folders($dirs);
        } catch(Exception $e) {
            if ($e instanceof FilesystemCredentialException)
                $ftp_form = $e->ftp_form();
        }
        foreach($dirs as $folder)
            if (@is_dir($folder))
                $errors[] = sprintf('Delete folder: <strong>%s</strong>',$folder);

        return array('errors' => $errors, 'ftp_form' => $ftp_form);
    }
}
