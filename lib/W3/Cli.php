<?php
/**
 * The W3 Total Cache plugin
 *
 * @package wp-cli
 * @subpackage commands/third-party
 * @maintainer Anthony Somerset
 */

if (defined('WP_CLI') && WP_CLI)
{
    class W3TotalCache_Command extends WP_CLI_Command
    {
        /**
         * Flushes the whole cache or specific items based on provided arguments
         *
         * ## OPTIONS
         *
         * [<post|database|minify|object>]
         * : post     = Flush a specific post using --postid or --permalink
         * database = Flush the database cache
         * object   = Flush the object cache
         * minify   = Flush the minify cache
         *
         * [--postid=<id>]
         * : Flush a specific post ID
         *
         * [--permalink=<url>]
         * : Flush a specific permalink
         */
        function flush($args = array() , $vars = array())
        {
            $args = array_unique($args);
            
            do
            {
                $cache_type = array_shift($args);
                
                switch ($cache_type)
                {
                case 'db':
                case 'database':
                    try
                    {
                        $w3_db = w3_instance('W3_CacheFlush');
                        $w3_db->dbcache_flush();
                    }
                    catch(Exception $e)
                    {
                        WP_CLI::error(__('Flushing the DB cache failed.', 'w3-total-cache'));
                    }

                    WP_CLI::success(__('The DB cache has flushed successfully.', 'w3-total-cache'));
                    break;

                case 'minify':
                    try
                    {
                        $w3_minify = w3_instance('W3_CacheFlush');
                        $w3_minify->minifycache_flush();
                    }
                    catch(Exception $e)
                    {
                        WP_CLI::error("Flushing the minify cache failed: ". $e->getMessage());
                    }

                    WP_CLI::success(__('The minify cache has flushed successfully.', 'w3-total-cache'));
                    break;

                case 'object':
                    try
                    {
                        $w3_objectcache = w3_instance('W3_CacheFlush');
                        $w3_objectcache->objectcache_flush();
                    }
                    catch(Exception $e)
                    {
                        WP_CLI::error("Flushing the object cache failed: ". $e->getMessage());
                    }

                    WP_CLI::success(__('The object cache has flushed successfully.', 'w3-total-cache'));
                    break;

                case 'post':
                default:
                    if (isset($vars['postid']))
                    {
                        if (is_numeric($vars['postid']))
                        {
                            try
                            {
                                $w3_cacheflush = w3_instance('W3_CacheFlush');
                                $w3_cacheflush->pgcache_flush_post($vars['postid']);
                                $w3_cacheflush->varnish_flush_post($vars['postid']);
                            }
                            catch(Exception $e)
                            {
                                WP_CLI::error("Flushing the page from cache failed: ". $e->getMessage());
                            }

                            WP_CLI::success(__('The page has been flushed from cache successfully.', 'w3-total-cache'));
                        }
                        else
                        {
                            WP_CLI::error(__('This is not a valid post id.', 'w3-total-cache'));
                        }

                        w3tc_pgcache_flush_post($vars['postid']);
                    }
                    else if (isset($vars['permalink']))
                    {
                        $id = url_to_postid($vars['permalink']);
                        
                        if (is_numeric($id))
                        {
                            try
                            {
                                $w3_cacheflush = w3_instance('W3_CacheFlush');
                                $w3_cacheflush->pgcache_flush_post($id);
                                $w3_cacheflush->varnish_flush_post($id);
                            }
                            catch(Exception $e)
                            {
                                WP_CLI::error("Flushing the page from cache failed: ". $e->getMessage());
                            }

                            WP_CLI::success(__('The page has been flushed from cache successfully.', 'w3-total-cache'));
                        }
                        else
                        {
                            WP_CLI::error(__('There is no post with that permalink.', 'w3-total-cache'));
                        }
                    }
                    else
                    {
                        if (isset($flushed_page_cache) && $flushed_page_cache) break;
                        $flushed_page_cache = true;
                        
                        try
                        {
                            $w3_cacheflush = w3_instance('W3_CacheFlush');
                            $w3_cacheflush->pgcache_flush();
                            $w3_cacheflush->varnish_flush();
                        }
                        catch(Exception $e)
                        {
                            WP_CLI::error("Flushing the page cache failed: ". $e->getMessage());
                        }

                        WP_CLI::success(__('The page cache has been flushed successfully.', 'w3-total-cache'));
                    }
                }
            } while (!empty($args));
        }

        /**
         * Prime the page cache (cache preloader)
         *
         * ## OPTIONS
         *
         * [<stop>]
         * : Stop the active page cache priming session.
         *
         * [--batch=<size>]
         * : Max number of pages to create per batch. If not set, the value given in
         * W3TC's Page Cache > Pages per Interval field is used. If size is 0 then
         * all pages within the sitemap will be created/cached without the use of a
         * batch and without waiting.
         *
         * [--interval=<seconds>]
         * : Number of seconds to wait before creating another batch. If not set, the
         * value given in W3TC's Page Cache > Update Interval field is used.
         *
         * [--sitemap=<url>]
         * : The sitemap url specifying the pages to prime cache. If not set, the value
         * given in W3TC's Page Cache > Sitemap URL field is used.
         *
         * ## EXAMPLES
         *
         *     # Prime the page cache using settings held within the W3TC config.
         *     $ wp w3-total-cache prime
         *
         *     # Stop the currently active prime process.
         *     $ wp w3-total-cache prime stop
         *
         *     # Prime the page cache (2 pages every 30 seconds).
         *     $ wp w3-total-cache prime --batch=2 --interval=30
         *
         *     # Prime the page cache every 30 seconds using the given sitemap.
         *     $ wp w3-total-cache prime --interval=30 --sitemap=http://site.com/sitemap.xml
         */
        function prime($args = array() , $vars = array())
        {
            try
            {
                $action = array_shift($args);                
                $w3_prime = w3_instance('W3_Plugin_PgCacheAdmin');
                
                if ($action == 'stop')
                {
                    if (wpcli_stop_prime($result) == false)
                    {
                        WP_CLI::warning($result);
                    }
                    else
                    {
                        WP_CLI::success('Page cache priming stopped.');
                    }
                }
                else if (strlen($action) > 0)
                {
                    $val = WP_CLI::colorize("%Y$action%n");
                    WP_CLI::error("Unrecognized argument - $val.");
                }
                else
                {
                    $config = w3_instance('W3_Config');
                    $user_limit = - 1;
                    $user_interval = - 1;
                    $user_sitemap = "";
                    
                    if (isset($vars['interval']) && is_numeric($vars['interval']))
                    {
                        $user_interval = intval($vars['interval']);
                    }

                    if (isset($vars['batch']) && is_numeric($vars['batch']))
                    {
                        $user_limit = intval($vars['batch']);
                    }

                    if (isset($vars['sitemap']) && !empty($vars['sitemap']))
                    {
                        $user_sitemap = trim($vars['sitemap']);
                    }

                    $limit = $user_limit == - 1 ? $config->get_integer('pgcache.prime.limit') : $user_limit;
                    $interval = $user_interval == - 1 ? $config->get_integer('pgcache.prime.interval') : $user_interval;
                    $sitemap = empty($user_sitemap) ? $config->get_string('pgcache.prime.sitemap') : $user_sitemap;
                    
                    if ( empty( $sitemap ) )
                    {
                        WP_CLI::error( __( "Prime page cache halted - Unable to load sitemap. A sitemap is needed to prime the page cache.", 'w3-total-cache' ) );
                    }
                    elseif (($res = $w3_prime->prime_cli($limit, $interval, $sitemap, 0, true)) === false)
                    {
                        WP_CLI::warning('Page cache priming is already active.');
                    }
                    else
                    {
                        if (extension_loaded('sysvmsg')) msg_send(msg_get_queue(99909) , 99, "prime_started");
                        WP_CLI::success("Page cache priming started $res.");
                    }
                }
            }
            catch(Exception $e)
            {
                WP_CLI::error($e->getMessage());
            }
        }

        /**
         * Update query string function
         */
        function querystring()
        {
            try
            {
                $w3_querystring = w3_instance('W3_CacheFlush');
                $w3_querystring->browsercache_flush();
            }
            catch(Exception $e)
            {
                WP_CLI::error('updating the query string failed. with error '. $e->getMessage());
            }

            WP_CLI::success(__('The query string was updated successfully.', 'w3-total-cache'));
        }

        /**
         * Purge URL's from cdn and varnish if enabled
         * @param array $args
         */
        function cdn_purge($args = array())
        {
            $purgeitems = array();
            
            foreach($args as $file)
            {
                $cdncommon = w3_instance('W3_Plugin_CdnCommon');
                $local_path = WP_ROOT . $file;
                $remote_path = $file;
                $purgeitems[] = $cdncommon->build_file_descriptor($local_path, $remote_path);
            }

            try
            {
                $w3_cdn_purge = w3_instance('W3_CacheFlush');
                $w3_cdn_purge->cdn_purge_files($purgeitems);
            }
            catch(Exception $e)
            {
                WP_CLI::error('Files did not successfully purge with error '. $e->getMessage());
            }

            WP_CLI::success(__('Files purged successfully.', 'w3-total-cache'));
        }

        /**
         * Tell APC to reload PHP files
         * @param array $args
         */
        function apc_reload_files($args = array())
        {
            try
            {
                $method = array_shift($args);

                if (!in_array($method, array('SNS','local')))
                {
                    WP_CLI::error($method . __(' is not supported. Change to SNS or local to reload APC files', 'w3-total-cache'));
                }

                if ($method == 'SNS')
                {
                    $w3_cache = w3_instance('W3_CacheFlush');
                    $w3_cache->apc_reload_files($args);
                }
                else
                {
                    $url = WP_PLUGIN_URL . '/' . dirname(W3TC_FILE) . '/pub/apc.php';
                    $path = parse_url($url, PHP_URL_PATH);
                    $post = array(
                        'method' => 'POST',
                        'timeout' => 45,
                        'redirection' => 5,
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'body' => array(
                            'nonce' => wp_hash($path) ,
                            'command' => 'reload_files',
                            'files' => $args
                        ) ,
                    );
                    
                    $result = wp_remote_post($url, $post);
                    
                    if (is_wp_error($result))
                    {
                        WP_CLI::error('Files did not successfully reload with error '.$result->get_error_message());
                    }
                    else if ($result['response']['code'] != '200')
                    {
                        WP_CLI::error(__('Files did not successfully reload with message: ', 'w3-total-cache') . $result['body']);
                    }
                }
            }
            catch(Exception $e)
            {
                WP_CLI::error('Files did not successfully reload with error '.$e->getMessage());
            }

            WP_CLI::success(__('Files reloaded successfully.', 'w3-total-cache'));
        }

        /**
         * Tell opcache to reload PHP files
         * @param array $args
         */
        function opcache_reload_files($args = array())
        {
            try
            {
                $method = array_shift($args);
                
                if (!in_array($method, array('SNS','local'))) 
                {
                    WP_CLI::error($method . __(' is not supported. Change to SNS or local to reload opcache files', 'w3-total-cache'));
                }

                if ($method == 'SNS')
                {
                    $w3_cache = w3_instance('W3_CacheFlush');
                    $w3_cache->opcache_reload_files($args);
                }
                else
                {
                    $url = WP_PLUGIN_URL . '/' . dirname(W3TC_FILE) . '/pub/opcache.php';
                    $path = parse_url($url, PHP_URL_PATH);
                    $post = array(
                        'method' => 'POST',
                        'timeout' => 45,
                        'redirection' => 5,
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'body' => array(
                            'nonce' => wp_hash($path) ,
                            'command' => 'reload_files',
                            'files' => $args
                        ) ,
                    );

                    $result = wp_remote_post($url, $post);
                    
                    if (is_wp_error($result))
                    {
                        WP_CLI::error('Files did not successfully reload with error '.$result->get_error_message());
                    }
                    else if ($result['response']['code'] != '200')
                    {
                        WP_CLI::error(__('Files did not successfully reload with message: ', 'w3-total-cache') . $result['body']);
                    }
                }
            }
            catch(Exception $e)
            {
                WP_CLI::error('Files did not successfully reload with error ' .$e->getMessage());
            }

            WP_CLI::success(__('Files reloaded successfully.', 'w3-total-cache'));
        }

        /**
         * Tell APC to reload PHP files
         * @param array $args
         */
        function apc_delete_based_on_regex($args = array())
        {
            try
            {
                $method = array_shift($args);
                
                if (!in_array($method, array('SNS','local')))
                {
                    WP_CLI::error($method . __(' is not supported. Change to SNS or local to delete APC files', 'w3-total-cache'));
                }

                if ($method == 'SNS')
                {
                    $w3_cache = w3_instance('W3_CacheFlush');
                    $w3_cache->apc_delete_files_based_on_regex($args[0]);
                }
                else
                {
                    $url = WP_PLUGIN_URL . '/' . dirname(W3TC_FILE) . '/pub/apc.php';
                    $path = parse_url($url, PHP_URL_PATH);
                    $post = array(
                        'method' => 'POST',
                        'timeout' => 45,
                        'redirection' => 5,
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'body' => array(
                            'nonce' => wp_hash($path) ,
                            'command' => 'delete_files',
                            'regex' => $args[0]
                        ) ,
                    );
                    
                    $result = wp_remote_post($url, $post);
                    
                    if (is_wp_error($result))
                    {
                        WP_CLI::error(__('Files did not successfully delete with error '.$result->get_error_message(), 'w3-total-cache'));
                    }
                    else if ($result['response']['code'] != '200')
                    {
                        WP_CLI::error(__('Files did not successfully delete with message: ', 'w3-total-cache') . $result['body']);
                    }
                }
            }
            catch(Exception $e)
            {
                WP_CLI::error(__('Files did not successfully delete with error '.$e->getMessage(), 'w3-total-cache'));
            }

            WP_CLI::success(__('Files deleted successfully.', 'w3-total-cache'));
        }

        /**
         * Tell opcache to reload PHP files
         * @param array $args
         */
        function opcache_delete_based_on_regex($args = array())
        {
            try
            {
                $method = array_shift($args);
                if (!in_array($method, array('SNS','local')))
                {
                    WP_CLI::error($method . __(' is not supported. Change to SNS or local to delete opcache files', 'w3-total-cache'));
                }

                if ($method == 'SNS')
                {
                    $w3_cache = w3_instance('W3_CacheFlush');
                    $w3_cache->apc_delete_files_based_on_regex($args[0]);
                }
                else
                {
                    $url = WP_PLUGIN_URL . '/' . dirname(W3TC_FILE) . '/pub/opcache.php';
                    $path = parse_url($url, PHP_URL_PATH);
                    $post = array(
                        'method' => 'POST',
                        'timeout' => 45,
                        'redirection' => 5,
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'body' => array(
                            'nonce' => wp_hash($path) ,
                            'command' => 'delete_files',
                            'regex' => $args[0]
                        ) ,
                    );
                    
                    $result = wp_remote_post($url, $post);
                    
                    if (is_wp_error($result))
                    {
                        WP_CLI::error(__('Files did not successfully delete with error '.$result->get_error_message(), 'w3-total-cache'));
                    }
                    else if ($result['response']['code'] != '200')
                    {
                        WP_CLI::error(__('Files did not successfully delete with message: ', 'w3-total-cache') . $result['body']);
                    }
                }
            }
            catch(Exception $e)
            {
                WP_CLI::error(__('Files did not successfully delete with error '.$e->getMessage(), 'w3-total-cache'));
            }

            WP_CLI::success(__('Files deleted successfully.', 'w3-total-cache'));
        }

        /**
         * triggers PgCache Garbage Cleanup
         */
        function pgcache_cleanup()
        {
            try
            {
                $pgcache_cleanup = w3_instance('W3_Plugin_PgCacheAdmin');
                $pgcache_cleanup->cleanup();
            }
            catch(Exception $e)
            {
                WP_CLI::error(__('PageCache Garbage cleanup did not start with error '.$e->getMessage(), 'w3-total-cache'));
            }

            WP_CLI::success(__('PageCache Garbage cleanup triggered successfully.', 'w3-total-cache'));
        }

        /**
         * Help function for this command
         */
        function help()
        {
            WP_CLI::line( <<<EOB
            
usage: wp w3-total-cache flush [post|database|minify|object] [--postid=<id>] [--permalink=<url>] 
       wp w3-total-cache querystring
       wp w3-total-cache cdn_purge <file> [<file>...]
       wp w3-total-cache pgcache_cleanup
       wp w3-total-cache prime [stop] [--batch=<size>] [--interval=<seconds>] [--sitemap=<url>]
       wp w3-total-cache apc_reload_files (SNS|local) <file.php> [<file.php>...]
       wp w3-total-cache apc_delete_based_on_regex (SNS|local) <expression>
       wp w3-total-cache opcache_reload_files
       wp w3-total-cache opcache_delete_based_on_regex (SNS|local) <expression>

Sub-Commands:

  flush                         Flushes the whole cache or specific items based on provided arguments

                                post       Flush a post via ... 
                                           --postid=<id>             Flush a specific post ID
                                           --permalink=<url>         Flush a specific permalink

                                database   Flush the database cache
                                object     Flush the object cache
                                minify     Flush the minify cache

  querystring                   Update query string for all static files
  cdn_purge                     Purges command line provided files from Varnish and the CDN
  pgcache_cleanup               Generally triggered from a cronjob, allows for manual Garbage collection
                                of page cache to be triggered

  prime                         Prime the page cache

                                stop                   Stop an active priming session
                                --batch=<size>         Max number of pages to create per batch
                                                       (this defaults to w3tc prefs when missing)
                                --interval=<seconds>   Number of seconds to wait before creating another batch
                                                       (this defaults to w3tc prefs when missing)
                                --sitemap=<url>        The sitemap url specifying the pages to prime cache
                                                       (this defaults to w3tc prefs when missing)                                

  apc_reload_files              Tells apc to compile files
  apc_delete_based_on_regex     Tells apc to delete files that match a RegEx mask
  opcache_reload_files          Tells opcache to compile files
  opcache_delete_based_on_regex Tells opcache to delete files that match a RegEx mask
            
EOB
            );
        }
    }

    if (method_exists('WP_CLI', 'add_command'))
    {
        WP_CLI::add_command('w3-total-cache', 'W3TotalCache_Command');
        WP_CLI::add_command('total-cache', 'W3TotalCache_Command');
    }
    else
    {
        // backward compatibility

        WP_CLI::addCommand('w3-total-cache', 'W3TotalCache_Command');
        WP_CLI::addCommand('total-cache', 'W3TotalCache_Command');
    }
}

function wpcli_stop_prime(&$result = "")
{
    $w3_prime = w3_instance('W3_Plugin_PgCacheAdmin');
    
    if (extension_loaded('sysvmsg'))
    {
        $pids = true;
        $que = msg_stat_queue(msg_get_queue(99909));
        
        if ($que['msg_qnum'] > 0)
        {
            msg_remove_queue(msg_get_queue(99909));
        }
        else
        {
            $pids = false;
        }
    }
    else if (false !== ($pids = $w3_prime->get_cli_pids()))
    {
        foreach($pids as $pid)
        {
            if (extension_loaded('posix') && w3_cmd_enabled("posix_kill"))
            {
                @posix_kill($pid, SIGTERM);
            }
            else if (w3_cmd_enabled("exec"))
            {
                @exec("kill -9 $pid >/dev/null 2>&1");
            }
            else
            {
                $result = "Can't issue the command to stop running process(es). Need either: exec, posix, or sysvmsg.";
                return false;
            }
        }

        $w3_prime->delete_cli_pids();
    }

    $w3_prime->delete_cli_urls();
    
    if (w3_clear_hook_crons('w3_pgcache_prime_cli') === false && $pids === false)
    {
        $result = "No page cache priming to stop. Either the priming has completed or was already stopped.";
        return false;
    }

    return true;
}
