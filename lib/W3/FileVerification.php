<?php

class W3_FileVerification {

    public function verify_filesetup() {
        $folders = $this->check_default_folders();
        $files = $this->verify_addins();
        return empty($folders) && empty($files);
    }

    /**
     * Checks to see if default folders exists and if they do not tries to create them.
     * If it cannot create a folder it is returned as part of array.
     * @return array
     */
    public function check_default_folders()
    {
        $cache_folders = array();

        $directories = array(
            W3TC_CACHE_DIR,
            W3TC_CONFIG_DIR,
            W3TC_CACHE_CONFIG_DIR,
            W3TC_CACHE_TMP_DIR);

        foreach ($directories as $dir) {
            if (!@is_dir($dir)) {
                $cache_folders[] = $dir;
            }
        }
        return $cache_folders;
    }

    /**
     * Checks if addins in wp-content is available and correct version.
     * @return array array('addin_files' => $addin_files, 'addin_files_messages' => $addin_files_messages);
     */
    public function verify_addins() {
        $addin_files = array();
        $addin_files_messages = array();
        if (!$this->db_installed()) {
            $addin_files[W3TC_INSTALL_FILE_DB] = W3TC_ADDIN_FILE_DB;
            $addin_files_messages[] = sprintf('Database caching is not available: %s is not installed.'
                , W3TC_ADDIN_FILE_DB);
        }

        if ( $this->db_check_old_add_in()) {
            $addin_files[W3TC_INSTALL_FILE_DB] = W3TC_ADDIN_FILE_DB;
            $addin_files_messages[] = sprintf('Database caching will not function properly: %s is not latest version.'
                , W3TC_ADDIN_FILE_DB);
        }

        if (!$this->objectcache_installed()) {
            $addin_files[W3TC_INSTALL_FILE_OBJECT_CACHE] = W3TC_ADDIN_FILE_OBJECT_CACHE;
            $addin_files_messages[] = sprintf('Object caching is not available: %s is not installed.'
                , W3TC_ADDIN_FILE_OBJECT_CACHE);
        }

        if ($this->objectcache_installed() && ($this->objectcache_check_old_add_in() || !$this->objectcache_check())) {
            $addin_files[W3TC_INSTALL_FILE_OBJECT_CACHE] = W3TC_ADDIN_FILE_OBJECT_CACHE;
            $addin_files_messages[] = sprintf('Object caching will not function properly: %s is not latest version.'
                , W3TC_ADDIN_FILE_OBJECT_CACHE);
        }

        if (!$this->advanced_cache_installed()) {
            $addin_files[W3TC_INSTALL_FILE_ADVANCED_CACHE] = W3TC_ADDIN_FILE_ADVANCED_CACHE;
            $addin_files_messages[] = sprintf('Page caching is not available: %s is not installed. '
                , W3TC_ADDIN_FILE_ADVANCED_CACHE);
        }

        if ($this->advanced_cache_check_old_add_in()) {
            $addin_files[W3TC_INSTALL_FILE_ADVANCED_CACHE] = W3TC_ADDIN_FILE_ADVANCED_CACHE;
            $addin_files_messages[] = sprintf('Page caching will not function properly: %s is not latest version. '
                , W3TC_ADDIN_FILE_ADVANCED_CACHE);
        }

        if ($addin_files)
            return array('files' => $addin_files, 'messages' => $addin_files_messages);
        return array();
    }

    /**
     * Returns true if advanced-cache.php is installed
     *
     * @return boolean
     */
    public function advanced_cache_installed() {
        return file_exists(W3TC_ADDIN_FILE_ADVANCED_CACHE);
    }

    /**
     * Returns true if advanced-cache.php is old version.
     * @return boolean
     */
    public function advanced_cache_check_old_add_in() {
        return (($script_data = @file_get_contents(W3TC_ADDIN_FILE_ADVANCED_CACHE))
            && strstr($script_data, '& w3_instance') !== false);
    }

    /**
     * Checks if advanced-cache.php exists
     *
     * @return boolean
     */
    public function advanced_cache_check() {
        return (($script_data = @file_get_contents(W3TC_ADDIN_FILE_ADVANCED_CACHE))
            && strstr($script_data, 'W3_PgCache') !== false);
    }

    /**
     * Returns true if db.php is installed
     *
     * @return boolean
     */
    public function db_installed() {
        return file_exists(W3TC_ADDIN_FILE_DB);
    }

    /**
     * Returns true if db.php is old version.
     * @return boolean
     */
    public function db_check_old_add_in() {
        return (($script_data = @file_get_contents(W3TC_ADDIN_FILE_DB))
            && strstr($script_data, '& w3_instance') !== false);
    }

    /**
     * Checks if db.php exists
     *
     * @return boolean
     */
    public function db_check() {
        return (($script_data = @file_get_contents(W3TC_ADDIN_FILE_DB))
            && strstr($script_data, 'W3_Db') !== false);
    }

    /**
     * Returns true if object-cache.php is installed
     *
     * @return boolean
     */
    public function objectcache_installed() {
        return file_exists(W3TC_ADDIN_FILE_OBJECT_CACHE);
    }

    /**
     * Returns true if object-cache.php is old version.
     *
     * @return boolean
     */
    public function objectcache_check_old_add_in() {
        return (($script_data = @file_get_contents(W3TC_ADDIN_FILE_OBJECT_CACHE))
            && strstr($script_data, '& w3_instance') !== false
            || strstr($script_data, "'W3_ObjectCache'") !== false);
    }

    /**
     * Checks if object-cache.php is latest version
     *
     * @return boolean
     */
    public function objectcache_check() {
        return (($script_data = @file_get_contents(W3TC_ADDIN_FILE_OBJECT_CACHE))
            && strstr($script_data, '//ObjectCache Version: 1.1') !== false);
    }

    /**
     * Verify that WordPress install folder is part of WP_CONTENT_DIR path
     * @return bool
     */
    public function should_create_wp_loader_file() {
        if (defined('DONOTVERIFY_WP_LOADER') && DONOTVERIFY_WP_LOADER)
            return false;
        if (w3_get_site_path() != '/'
            && strpos(WP_PLUGIN_DIR, w3_get_site_path()) === false) {
            return true;
        }
        return false;
    }
}
