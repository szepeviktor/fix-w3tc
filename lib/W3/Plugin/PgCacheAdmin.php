<?php

/**
 * W3 PgCache plugin - administrative interface
 */
if (!defined('W3TC')) {
    die();
}

w3_require_once(W3TC_INC_DIR . '/functions/file.php');
w3_require_once(W3TC_INC_DIR . '/functions/rule.php');
w3_require_once(W3TC_LIB_W3_DIR . '/Plugin.php');

/**
 * Class W3_Plugin_PgCacheAdmin
 */
class W3_Plugin_PgCacheAdmin extends W3_Plugin {

    function run() {
        add_filter('w3tc_save_options', array($this, 'remove_old_files'),10,2);
    }

    function cleanup() {
        // We check to see if we're dealing with a cluster
        $config = w3_instance('W3_Config');
        $is_cluster = $config->get_boolean('cluster.messagebus.enabled');

        // If we are, we notify the subscribers. If not, we just cleanup in here
        if ($is_cluster) {
            $this->cleanup_cluster();
        } else {
            $this->cleanup_local();
        }

    }
    
    /**
     * Will trigger notifications to be sent to the cluster to 'order' them to clean their page cache.
     */
    function cleanup_cluster() {
        $sns_client = w3_instance('W3_Enterprise_SnsClient');
        $sns_client->pgcache_cleanup();
    }
    
    function cleanup_local() {
        $engine = $this->_config->get_string('pgcache.engine');

        switch ($engine) {
            case 'file':
                w3_require_once(W3TC_LIB_W3_DIR . '/Cache/File/Cleaner.php');

                $w3_cache_file_cleaner = new W3_Cache_File_Cleaner(array(
                    'cache_dir' => w3_cache_blog_dir('page'),
                    'clean_timelimit' => $this->_config->get_integer('timelimit.cache_gc')
                ));

                $w3_cache_file_cleaner->clean();
                break;

            case 'file_generic':
                w3_require_once(W3TC_LIB_W3_DIR . '/Cache/File/Cleaner/Generic.php');

                if (w3_get_blog_id() == 0)
                    $flush_dir = W3TC_CACHE_PAGE_ENHANCED_DIR;
                else
                    $flush_dir = W3TC_CACHE_PAGE_ENHANCED_DIR . '/' . w3_get_domain(w3_get_host());

                $w3_cache_file_cleaner_generic = new W3_Cache_File_Cleaner_Generic(array(
                    'exclude' => array(
                        '.htaccess'
                    ),
                    'cache_dir' => $flush_dir,
                    'expire' => $this->_config->get_integer('browsercache.html.lifetime'),
                    'clean_timelimit' => $this->_config->get_integer('timelimit.cache_gc')
                ));

                $w3_cache_file_cleaner_generic->clean();
                break;
        }
    }

    /**
     * Prime cache
     *
     * @param integer $start
     * @return void
     */
    function prime($start = 0) {
        $start = (int) $start;

        /**
         * Don't start cache prime if queues are still scheduled
         */
        if ($start == 0) {
            $crons = _get_cron_array();

            foreach ($crons as $timestamp => $hooks) {
                foreach ($hooks as $hook => $keys) {
                    foreach ($keys as $key => $data) {
                        if ($hook == 'w3_pgcache_prime' && count($data['args'])) {
                            return;
                        }
                    }
                }
            }
        }

        if (!$this->_config->get_boolean('pgcache.prime.enabled')) return;
        
        $interval = $this->_config->get_integer('pgcache.prime.interval');
        $limit = $this->_config->get_integer('pgcache.prime.limit');
        $sitemap = $this->_config->get_string('pgcache.prime.sitemap');

        /**
         * Parse XML sitemap
         */
        $urls = $this->parse_sitemap($sitemap);

        if ($limit <= 0) $limit = count($urls);
        
        /**
         * Queue URLs
         */
        $queue = array_slice($urls, $start, $limit);

        if (count($urls) > ($start + $limit)) {
            wp_schedule_single_event(time() + $interval, 'w3_pgcache_prime', array(
                $start + $limit
            ));
        }

        /**
         * Make HTTP requests and prime cache
         */
        w3_require_once(W3TC_INC_DIR . '/functions/http.php');
        w3_require_once(W3TC_INC_DIR . '/functions/url.php');

        // use user-agent "Wordpress" since by default we use W3TC-powered by
        // which blocks caching
        foreach ($queue as $url)
            w3_http_get($url, array('user-agent' => 'Wordpress'));
    }

    /**
     * Prime cache (WP-CLI)
     *
     */
    function prime_cli($limit=0,$interval=0,$sitemap="",$start,$boot=false) {
        if ($boot)
        {
            /**
             * Don't start cache prime if queues are still scheduled
             */

            if (extension_loaded('sysvmsg')) {
                $que = msg_stat_queue(msg_get_queue(99909));
                if ($que['msg_qnum'] > 0) {
                	return false;
                }
            }
            
            if ($this->get_cli_pids()!==false) {
                return false;
            }

            $crons = _get_cron_array();

            foreach ($crons as $timestamp => $hooks) {
                foreach ($hooks as $hook => $keys) {
                    foreach ($keys as $key => $data) {
                        if ($hook == 'w3_pgcache_prime_cli' && count($data['args'])) {
                            return false;
                        }
                    }
                }
            }
            
            $this->delete_cli_urls();

            if ($limit < 0) $limit = 0;
            if ($interval < 0) $interval = 0;

            wp_schedule_single_event(time(), 'w3_pgcache_prime_cli', array(
                            $limit,
                            $interval,
                            $sitemap,
                            $start
            ));

            return "(" . ($limit == 0 ? "" : "batch: $limit pages every $interval secs - ") . "sitemap: $sitemap)";
        }
        else
        {
            /**
             * Parse XML sitemap
             */
             
            $write = true;
            
            if (($urls=$this->get_cli_urls())===false) {
                $urls = $this->parse_sitemap($sitemap);
            }
            else
                $write = false;

            if ($limit == 0) $limit = count($urls);

            if (count($urls) == 0) {
                error_log('WP-CLI: Prime page cache halted - Unable to load sitemap. A sitemap is needed to prime the page cache.');
                return;
            }
            else if ($write && ($start + $limit) < count($urls)){
                $this->set_cli_urls($urls);
            }

            /**
             * Queue URLs
             */
            $queue = array_slice($urls,$start,$limit);

            if (count($queue) > 0)
            {
                $usefile=true;
                $msgidmain = null;
                $msgidproc = null;

                if (extension_loaded('sysvmsg'))
                {
                    $usefile = false;
                    $msgidmain = msg_get_queue(99909);
                    $msgidproc = msg_get_queue(99910);

                    msg_send($msgidproc,99,"prime_proc");
                }
            
                /**
                 * Make HTTP requests and prime cache
                 */
                w3_require_once(W3TC_INC_DIR . '/functions/http.php');
                w3_require_once(W3TC_INC_DIR . '/functions/url.php');

                if ($usefile)
                {
                    $pid = getmypid();

                    if (($pids=$this->get_cli_pids()) === false)
                        $pids = array();

                    $pids[] = $pid;
                    $this->set_cli_pids($pids);
                }
                
                if (count($urls) > ($start + $limit)) {
                    wp_schedule_single_event(time() + $interval, 'w3_pgcache_prime_cli', array(
                        $limit,
                        $interval,
                        $sitemap,
                        $start + $limit
                    ));
                }
                else
                    $done = true;

                // use user-agent "Wordpress" since by default we use W3TC-powered by
                // which blocks caching
                foreach ($queue as $url)
                {
                    w3_http_get($url, array('user-agent' => 'Wordpress'));
                    if ($msgidmain != null) {
                    	$que = msg_stat_queue($msgidmain);
                    	if ($que['msg_qnum'] == 0) {
                    		break;
                    	}
                    }
                }

                if ($usefile)
                {
                    if (($pids=$this->get_cli_pids()) !== false)
                    {
                        unset($pids[array_search($pid,$pids)]);
                        $this->set_cli_pids($pids);
                    }

                    if (isset($done) && ($pids===false || count($pids) == 0))
                    {
                        $this->delete_cli_pids();
                        $this->delete_cli_urls();
                        
                        exit("Page cache priming via WP-CLI has successfully completed.\n");
                    }
                }
                else
                {
                    msg_receive($msgidproc,99,$t,1024,$data,true,MSG_IPC_NOWAIT);
					$que = msg_stat_queue($msgidproc);
					
                    if ($que['msg_qnum'] == 0) {
                        msg_remove_queue($msgidproc);
						$que = msg_stat_queue($msgidmain);
						
                        if ($que['msg_qnum'] == 0) {
                            msg_remove_queue($msgidmain);
                            
                            if (isset($done)) {
                                $this->delete_cli_urls();
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Gets the active process ids handling WP-CLI prime caching
     *     
     * @return array
     */
    function get_cli_pids()
    {
        return w3_lock_read($this->generate_filename(W3TC_CLI_PIDS));
    }

    /**
     * Adds (or Removes) a process id handling WP-CLI prime caching
     *
     * @param array 
     * @return void
     */
    function set_cli_pids($data)
    {
        w3_lock_write($this->generate_filename(W3TC_CLI_PIDS),$data);
    }

    /**
     * Deletes the WP-CLI prime caching support file for process IDs
     *     
     * @return void
     */
    function delete_cli_pids()
    {
        @unlink($this->generate_filename(W3TC_CLI_PIDS));
    }

    /**
     * Gets sitemap urls (originally stored via set_cli_urls) for WP-CLI prime caching
     *     
     * @return array
     */
    function get_cli_urls()
    {
        return w3_lock_read($this->generate_filename(W3TC_CLI_URLS));
    }

    /**
     * Stores sitemap urls for WP-CLI prime caching
     *
     * @param array 
     * @return void
     */
    function set_cli_urls($data)
    {
        w3_lock_write($this->generate_filename(W3TC_CLI_URLS),$data);
    }

    /**
     * Deletes the WP-CLI prime caching support file for sitemap URLs
     *     
     * @return void
     */
    function delete_cli_urls()
    {
        @unlink($this->generate_filename(W3TC_CLI_URLS));
    }
    
    /**
     * Gets the temporary CLI file name to use
     *     
     * @return string - full file path
     */
    function generate_filename($file,$dir=W3TC_CACHE_TMP_DIR)
    {
        if (!is_dir($dir) || !is_writable($dir)) {
            w3_mkdir_from($dir,W3TC_CACHE_DIR);
            
            if (!is_dir($dir) || !is_writable($dir)) {
                $dir="";
            }
            
            $dir = rtrim($dir,"/");
        }
        
        return $dir . (empty($dir)?"":"/") . $file;
    }

    /**
     * Parses sitemap
     *
     * @param string $url
     * @return array
     */
    function parse_sitemap($url) {
        w3_require_once(W3TC_INC_DIR . '/functions/http.php');

        if (!w3_is_url($url))
            $url = home_url($url);

        $urls = array();
        $response = w3_http_get($url);

        if (!is_wp_error($response) && $response['response']['code'] == 200) {
            $url_matches = null;
            $sitemap_matches = null;

            if (preg_match_all('~<sitemap>(.*?)</sitemap>~is', $response['body'], $sitemap_matches)) {
                $loc_matches = null;

                foreach ($sitemap_matches[1] as $sitemap_match) {
                    if (preg_match('~<loc>(.*?)</loc>~is', $sitemap_match, $loc_matches)) {
                        $loc = trim($loc_matches[1]);

                        if ($loc) {
                            $urls = array_merge($urls, $this->parse_sitemap($loc));
                        }
                    }
                }
            } elseif (preg_match_all('~<url>(.*?)</url>~is', $response['body'], $url_matches)) {
                $locs = array();
                $loc_matches = null;
                $priority_matches = null;

                foreach ($url_matches[1] as $url_match) {
                    $loc = '';
                    $priority = 0.5;

                    if (preg_match('~<loc>(.*?)</loc>~is', $url_match, $loc_matches)) {
                        $loc = trim($loc_matches[1]);
                    }

                    if (preg_match('~<priority>(.*?)</priority>~is', $url_match, $priority_matches)) {
                        $priority = (double) trim($priority_matches[1]);
                    }

                    if ($loc && $priority) {
                        $locs[$loc] = $priority;
                    }
                }

                arsort($locs);

                $urls = array_keys($locs);
            }
        }

        return $urls;
    }

    /**
     * Returns required rules for module
     * @return array
     */
    function get_required_rules() {
        $e = w3_instance('W3_Plugin_PgCacheAdminEnvironment');
        return $e->get_required_rules();
    }


    /**
     * Makes get requests to url specific to a post, its permalink
     * @param $post_id
     * @return boolean returns true on success
     */
    public function prime_post($post_id) {
        /** @var $purges W3_SharedPageUrls */
        $purges = w3_instance('W3_SharedPageUrls');
        $post_urls = $purges->get_post_urls($post_id);
        /**
         * Make HTTP requests and prime cache
         */
        w3_require_once(W3TC_INC_DIR . '/functions/http.php');
        w3_require_once(W3TC_INC_DIR . '/functions/url.php');

        foreach ($post_urls as $url) {
            $result = w3_http_get($url, array('user-agent' => ''));
            if (is_wp_error($result))
                return false;
        }
        return true;
    }

    /**
     * Remove .old files if changing settings.
     *
     * @param W3_Config $new_config
     * @param W3_Config $old_config
     * @param W3_ConfigAdmin $config_admin
     * @return W3_Config
     */
    public function remove_old_files($new_config, $old_config, $config_admin = null) {
        if ((!$new_config->get_boolean('pgcache.cache.home') && $old_config->get_boolean('pgcache.cache.home')) ||
              $new_config->get_boolean('pgcache.reject.front_page') && !$old_config->get_boolean('pgcache.reject.front_page') ||
              !$new_config->get_boolean('pgcache.cache.feed') && $old_config->get_boolean('pgcache.cache.feed') ||
              !$new_config->get_boolean('pgcache.cache.query') && $old_config->get_boolean('pgcache.cache.query') ||
              !$new_config->get_boolean('pgcache.cache.ssl') && $old_config->get_boolean('pgcache.cache.ssl')) {
            $new_config->set('notes.need_empty_pgcache', true);
        }
        return $new_config;
    }
}
