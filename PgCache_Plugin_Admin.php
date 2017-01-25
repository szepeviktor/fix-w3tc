<?php
namespace W3TC;

/**
 * W3 PgCache plugin - administrative interface
 */




/**
 * class PgCache_Plugin_Admin
 */
class PgCache_Plugin_Admin {
	/**
	 * Config
	 */
	private $_config = null;

	function __construct() {
		$this->_config = Dispatcher::config();
	}

	/**
	 * Runs plugin
	 */
	function run() {
		add_filter( 'w3tc_save_options', array( $this, 'w3tc_save_options' ) );

		$config_labels = new PgCache_ConfigLabels();
		add_filter( 'w3tc_config_labels', array( $config_labels, 'config_labels' ) );

		if ( $this->_config->get_boolean( 'pgcache.enabled' ) ) {
			add_filter( 'w3tc_errors', array( $this, 'w3tc_errors' ) );
			add_filter( 'w3tc_usage_statistics_summary_from_history', array(
					$this, 'w3tc_usage_statistics_summary_from_history' ), 10, 2 );
		}
	}

	function cleanup() {
		// We check to see if we're dealing with a cluster
		$config = Dispatcher::config();
		$is_cluster = $config->get_boolean( 'cluster.messagebus.enabled' );

		// If we are, we notify the subscribers. If not, we just cleanup in here
		if ( $is_cluster ) {
			$this->cleanup_cluster();
		} else {
			$this->cleanup_local();
		}

	}

	/**
	 * Will trigger notifications to be sent to the cluster to 'order' them to clean their page cache.
	 */
	function cleanup_cluster() {
		$sns_client = Dispatcher::component( 'Enterprise_CacheFlush_MakeSnsEvent' );
		$sns_client->pgcache_cleanup();
	}

	function cleanup_local() {
		$engine = $this->_config->get_string( 'pgcache.engine' );

		switch ( $engine ) {
		case 'file':
			$w3_cache_file_cleaner = new Cache_File_Cleaner( array(
					'cache_dir' => Util_Environment::cache_blog_dir( 'page' ),
					'clean_timelimit' => $this->_config->get_integer( 'timelimit.cache_gc' )
				) );

			$w3_cache_file_cleaner->clean();
			break;

		case 'file_generic':
			if ( Util_Environment::blog_id() == 0 )
				$flush_dir = W3TC_CACHE_PAGE_ENHANCED_DIR;
			else
				$flush_dir = W3TC_CACHE_PAGE_ENHANCED_DIR . '/' . Util_Environment::host();

			$w3_cache_file_cleaner_generic = new Cache_File_Cleaner_Generic( array(
					'exclude' => array(
						'.htaccess'
					),
					'cache_dir' => $flush_dir,
					'expire' => $this->_config->get_integer( 'browsercache.html.lifetime' ),
					'clean_timelimit' => $this->_config->get_integer( 'timelimit.cache_gc' )
				) );

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
	function prime( $start = 0 ) {
		$start = (int) $start;

		/**
		 * Don't start cache prime if queues are still scheduled
		 */
		if ( $start == 0 ) {
			$crons = _get_cron_array();

			if ( is_array( $crons ) ) {
				foreach ( $crons as $timestamp => $hooks ) {
					foreach ( $hooks as $hook => $keys ) {
						foreach ( $keys as $key => $data ) {
							if ( $hook == 'w3_pgcache_prime' && count( $data['args'] ) ) {
								return;
							}
						}
					}
				}
			}
		}

        /**
         * If priming is actively running and the user stops the priming
         * via unchecking "Automatically prime the page cache" this will
         * forceably stop the priming process in its track.
         */
        if ( !$this->_config->get_boolean( 'pgcache.prime.enabled' ) ) {
            return;
        }

		$interval = $this->_config->get_integer( 'pgcache.prime.interval' );
		$limit = $this->_config->get_integer( 'pgcache.prime.limit' );
		$sitemap = $this->_config->get_string( 'pgcache.prime.sitemap' );

		/**
		 * Parse XML sitemap
		 */
		$urls = $this->parse_sitemap( $sitemap );

        /**
         * The user can set the "Pages per interval" value to 0 which 
         * indicates there is no batch size limit.
         */        
        if ( $limit <= 0 ) {
            $limit = count( $urls );
        }

		/**
		 * Queue URLs
		 */
		$queue = array_slice( $urls, $start, $limit );

		if ( count( $urls ) > ( $start + $limit ) ) {
			wp_schedule_single_event( time() + $interval, 'w3_pgcache_prime', array(
					$start + $limit
				) );
		}

		/**
		 * Make HTTP requests and prime cache
		 */



		// use 'WordPress' since by default we use W3TC-powered by
		// which blocks caching
		foreach ( $queue as $url )
			Util_Http::get( $url, array( 'user-agent' => 'WordPress' ) );
	}

    /**
     * Prime cache (WP-CLI)
     *
     * Since we are dealing with CLI and the priming could take a long time, an
     * asynchronous approach is used so no shell blocking occurs while the priming
     * process is done in the background. Upon starting a new prime task it generates
     * a bootstrap scheduled event to start the async priming process -- unblocking the CLI.
     *
     * If you'd like more info on how this all works -- happy to help: core905atGmail
     *
     * @param   integer $limit      Pages per batch size
     * @param   integer $interval   Number of seconds to wait before creating another batch
     * @param   string  $sitemap    The sitemap url to use
     * @param   integer $start      Index position within the sitemap to prime cache
     * @param   boolean $boot       Indicates if the prime cache is about to start for the first time
     * @return  boolean
     */
    function prime_cli( $limit = 0, $interval = 0, $sitemap = "", $start, $boot = false ) {
        if ( $boot ) {
            /**
             * Don't start cache prime if queues are still scheduled
             */
            if ( extension_loaded( 'sysvmsg' ) ) {
                $que = msg_stat_queue( msg_get_queue( 99909 ) );
                if ( $que['msg_qnum'] > 0 ) {
                    return false;
                }
            }
            
            if ( $this->get_cli_pids() !== false ) {
                return false;
            }

            $crons = _get_cron_array();

            foreach ( $crons as $timestamp => $hooks ) {
                foreach ( $hooks as $hook => $keys ) {
                    foreach ( $keys as $key => $data ) {
                        if ( $hook == 'w3_pgcache_prime_cli' && count( $data['args'] ) ) {
                            return false;
                        }
                    }
                }
            }
            
            /**
             * Bootstrap the page cache prime process by scheduling it as an event.
             * This is needed to make this CLI action asychronous
             */
            $this->delete_cli_urls();

            if ( $limit < 0 ) {
                $limit = 0;
            }
            
            if ( $interval < 0 ) {
                $interval = 0;
            }

            wp_schedule_single_event( time(), 'w3_pgcache_prime_cli', array(
                            $limit,
                            $interval,
                            $sitemap,
                            $start
            ) );

            return "(" . ( $limit == 0 ? "" : "batch: $limit pages every $interval secs - " ) . "sitemap: $sitemap)";
        } else {
            /**
             * Get and parse the XML sitemap
             */
            $write = true;
            
            if ( ( $urls = $this->get_cli_urls() ) === false ) {
                $urls = $this->parse_sitemap( $sitemap );
            } else {
                $write = false;
            }

            if ( $limit == 0 ) {
                $limit = count( $urls );
            }

            if ( count( $urls ) == 0 ) {
                error_log( 'WP-CLI: Prime page cache halted - Unable to load sitemap. A sitemap is needed to prime the page cache.' );
                return;
            } elseif ( $write && ( $start + $limit ) < count( $urls ) ) {
                $this->set_cli_urls( $urls );
            }

            /**
             * Extract a slice of URLs for priming
             */
            $queue = array_slice( $urls, $start, $limit );

            if ( count( $queue ) > 0 ) {
                $usefile=true;
                $msgidmain = null;
                $msgidproc = null;

                if ( extension_loaded( 'sysvmsg' ) ) {
                    $usefile = false;
                    $msgidmain = msg_get_queue( 99909 );
                    $msgidproc = msg_get_queue( 99910 );

                    msg_send( $msgidproc,99,"prime_proc" );
                }
            
                /**
                 * Get and store this process id in case the user decides to stop the prime process
                 */
                if ( $usefile ) {
                    $pid = getmypid();

                    if ( ( $pids = $this->get_cli_pids() ) === false ) {
                        $pids = array();
                    }

                    $pids[] = $pid;
                    $this->set_cli_pids( $pids );
                }

                /**
                 * Schedule the next batch of URLs to be primed
                 */
                if ( count( $urls ) > ( $start + $limit ) ) {
                    wp_schedule_single_event( time() + $interval, 'w3_pgcache_prime_cli', array(
                        $limit,
                        $interval,
                        $sitemap,
                        $start + $limit
                    ) );
                }
                else {
                    $done = true;
                }

                /**
                 * Make HTTP requests -- prime cache
                 */
                foreach ( $queue as $url ) {
                    Util_Http::get( $url, array( 'user-agent' => 'WordPress' ) );
                    if ( $msgidmain != null ) {
                        $que = msg_stat_queue( $msgidmain );
                        if ( $que['msg_qnum'] == 0 ) {
                            break;
                        }
                    }
                }

                /**
                 * This process slice has completed its portion to prime URLs.
                 * Let's do some clean up
                 */
                if ( $usefile ) {
                    if ( ( $pids = $this->get_cli_pids() ) !== false ) {
                        unset( $pids[array_search( $pid, $pids )] );
                        $this->set_cli_pids( $pids );
                    }

                    if ( isset( $done ) && ( $pids === false || count( $pids ) == 0) ) {
                        $this->delete_cli_pids();
                        $this->delete_cli_urls();
                        
                        exit( "Page cache priming via WP-CLI has successfully completed.\n" );
                    }
                }
                else {
                    msg_receive( $msgidproc, 99, $t, 1024, $data, true, MSG_IPC_NOWAIT );
                    $que = msg_stat_queue( $msgidproc );
                    
                    if ( $que['msg_qnum'] == 0 ) {
                        msg_remove_queue( $msgidproc );
                        $que = msg_stat_queue( $msgidmain );
                        
                        if ( $que['msg_qnum'] == 0 ) {
                            msg_remove_queue( $msgidmain );
                            
                            if ( isset( $done ) ) {
                                $this->delete_cli_urls();
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Gets all the active process ids handling the WP-CLI prime caching
     *
     * @return array
     */
    function get_cli_pids()
    {
        return w3tc_lock_read( $this->generate_filename( W3TC_CLI_PIDS ) );
    }

    /**
     * Store the list of process ids that are handling the WP-CLI prime caching
     *
     * @param   array   $data   A collecton of process ids
     * @return  void
     */
    function set_cli_pids( $data )
    {
        w3tc_lock_write( $this->generate_filename( W3TC_CLI_PIDS ), $data );
    }

    /**
     * Deletes the WP-CLI prime caching support file
     *
     * @return void
     */
    function delete_cli_pids()
    {
        @unlink( $this->generate_filename( W3TC_CLI_PIDS ) );
    }

    /**
     * This helper function reduces the load of needing to parse the sitemap
     * repeatedly during the prime cache process. It returns the list of urls
     * needed to be primed.
     *
     * @return array
     */
    function get_cli_urls()
    {
        return w3tc_lock_read( $this->generate_filename( W3TC_CLI_URLS ) );
    }

    /**
     * Stores the list of urls to be used for prime caching. This helper
     * function reduces the load of needing to parse the sitemap repeatedly.
     *
     * @param   array   $data   A collection of urls to store
     * @return  void
     */
    function set_cli_urls( $data )
    {
        w3tc_lock_write( $this->generate_filename( W3TC_CLI_URLS ), $data );
    }

    /**
     * Deletes the WP-CLI prime caching support file
     *     
     * @return void
     */
    function delete_cli_urls()
    {
        @unlink( $this->generate_filename( W3TC_CLI_URLS ) );
    }
    
    /**
     * Constructs a writable temporary CLI file path to use for holding pids or urls
     *
     * @param   string  $file   Filename to use
     * @param   string  $dir    Optional directory location
     * @return  string
     */
    function generate_filename( $file, $dir = W3TC_CACHE_TMP_DIR )
    {
        if ( !is_dir( $dir ) || !is_writable( $dir ) ) {
            Util_File::mkdir_from( $dir, W3TC_CACHE_DIR );
            
            if ( !is_dir( $dir ) || !is_writable( $dir ) ) {
                $dir="";
            }
            
            $dir = rtrim( $dir, "/" );
        }
        
        return $dir . ( empty( $dir ) ? "" : "/" ) . $file;
    }

	/**
	 * Parses sitemap
	 *
	 * @param string  $url
	 * @return array
	 */
	function parse_sitemap( $url ) {
		if ( !Util_Environment::is_url( $url ) )
			$url = home_url( $url );

		$urls = array();
		$response = Util_Http::get( $url );

		if ( !is_wp_error( $response ) && $response['response']['code'] == 200 ) {
			$url_matches = null;
			$sitemap_matches = null;

			if ( preg_match_all( '~<sitemap>(.*?)</sitemap>~is', $response['body'], $sitemap_matches ) ) {
				$loc_matches = null;

				foreach ( $sitemap_matches[1] as $sitemap_match ) {
					if ( preg_match( '~<loc>(.*?)</loc>~is', $sitemap_match, $loc_matches ) ) {
						$loc = trim( $loc_matches[1] );

						if ( $loc ) {
							$urls = array_merge( $urls, $this->parse_sitemap( $loc ) );
						}
					}
				}
			} elseif ( preg_match_all( '~<url>(.*?)</url>~is', $response['body'], $url_matches ) ) {
				$locs = array();
				$loc_matches = null;
				$priority_matches = null;

				foreach ( $url_matches[1] as $url_match ) {
					$loc = '';
					$priority = 0.5;

					if ( preg_match( '~<loc>(.*?)</loc>~is', $url_match, $loc_matches ) ) {
						$loc = trim( $loc_matches[1] );
					}

					if ( preg_match( '~<priority>(.*?)</priority>~is', $url_match, $priority_matches ) ) {
						$priority = (double) trim( $priority_matches[1] );
					}

					if ( $loc && $priority ) {
						$locs[$loc] = $priority;
					}
				}

				arsort( $locs );

				$urls = array_keys( $locs );
			}
		}

		return $urls;
	}

	/**
	 * Makes get requests to url specific to a post, its permalink
	 *
	 * @param unknown $post_id
	 * @return boolean returns true on success
	 */
	public function prime_post( $post_id ) {
		$post_urls = Util_PageUrls::get_post_urls( $post_id );

		// Make HTTP requests and prime cache
		foreach ( $post_urls as $url ) {
			$result = Util_Http::get( $url, array( 'user-agent' => 'WordPress' ) );
			if ( is_wp_error( $result ) )
				return false;
		}
		return true;
	}



	public function w3tc_save_options( $data ) {
		$new_config = $data['new_config'];
		$old_config = $data['old_config'];

		if ( ( !$new_config->get_boolean( 'pgcache.cache.home' ) && $old_config->get_boolean( 'pgcache.cache.home' ) ) ||
			$new_config->get_boolean( 'pgcache.reject.front_page' ) && !$old_config->get_boolean( 'pgcache.reject.front_page' ) ||
			!$new_config->get_boolean( 'pgcache.cache.feed' ) && $old_config->get_boolean( 'pgcache.cache.feed' ) ||
			!$new_config->get_boolean( 'pgcache.cache.query' ) && $old_config->get_boolean( 'pgcache.cache.query' ) ||
			!$new_config->get_boolean( 'pgcache.cache.ssl' ) && $old_config->get_boolean( 'pgcache.cache.ssl' ) ) {
			$state = Dispatcher::config_state();
			$state->set( 'common.show_note.flush_posts_needed', true );
			$state->save();
		}

		return $data;
	}

	public function w3tc_errors( $errors ) {
		$c = Dispatcher::config();

		if ( $c->get_string( 'pgcache.engine' ) == 'memcached' ) {
			$memcached_servers = $c->get_array( 'pgcache.memcached.servers' );

			if ( !Util_Installed::is_memcache_available( $memcached_servers ) ) {
				if ( !isset( $errors['memcache_not_responding.details'] ) )
					$errors['memcache_not_responding.details'] = array();

				$errors['memcache_not_responding.details'][] = sprintf(
					__( 'Page Cache: %s.', 'w3-total-cache' ),
					implode( ', ', $memcached_servers ) );
			}
		}

		return $errors;
	}

	public function w3tc_usage_statistics_summary_from_history( $summary, $history ) {
		// memcached servers
		if ( $this->_config->get_string( 'pgcache.engine' ) == 'memcached' ) {
			$summary['memcached_servers']['pgcache'] = array(
				'servers' => $this->_config->get_array( 'pgcache.memcached.servers' ),
				'username' => $this->_config->get_string( 'pgcache.memcached.username' ),
				'password' => $this->_config->get_string( 'pgcache.memcached.password' ),
				'name' => __( 'Page Cache', 'w3-total-cache' )
			);
		} elseif ( $this->_config->get_string( 'pgcache.engine' ) == 'redis' ) {
			$summary['redis_servers']['pgcache'] = array(
				'servers' => $this->_config->get_array( 'pgcache.redis.servers' ),
				'dbid' => $this->_config->get_integer( 'pgcache.redis.dbid' ),
				'password' => $this->_config->get_string( 'pgcache.redis.password' ),
				'name' => __( 'Page Cache', 'w3-total-cache' )
			);
		}

		// total size
		$g = Dispatcher::component( 'PgCache_ContentGrabber' );
		$pagecache = array();

		$e = $this->_config->get_string( 'pgcache.engine' );
		$pagecache['size_visible'] = ( $e == 'file_generic' );
		$pagecache['requests_visible'] = ( $e != 'file_generic' );


		if ( isset( $summary['period']['timestamp_end'] ) ) {
			// need to return cache size
			if ( $pagecache['size_visible'] ) {
				list( $v, $should_count ) =
					Util_UsageStatistics::get_or_init_size_transient(
					'w3tc_ustats_pagecache_size', $summary );
				if ( $should_count ) {
					$size = $g->get_cache_stats_size( $summary['timeout_time'] );
					$v['size_used'] = Util_UsageStatistics::bytes_to_size2(
						$size, 'bytes' );
					$v['items'] = Util_UsageStatistics::integer2(
						$size, 'items' );

					set_transient( 'w3tc_ustats_pagecache_size', $v, 120 );
				}

				if ( isset( $v['size_used'] ) ) {
					$pagecache['size_used'] = $v['size_used'];
					$pagecache['items'] = $v['items'];
				}
			}

			// counters
			$requests_total = Util_UsageStatistics::sum( $history,
				'pagecache_requests_total' );
			$requests_time_ms = Util_UsageStatistics::sum( $history,
				'pagecache_requests_time_10ms' ) * 10;
			$requests_hits = Util_UsageStatistics::sum( $history,
				'pagecache_requests_hits' );

			$pagecache['requests_total'] = Util_UsageStatistics::integer(
				$requests_total );
			$pagecache['request_time_ms'] =
				Util_UsageStatistics::value_per_period_seconds(
				$requests_time_ms, $summary );
			$pagecache['requests_per_second'] =
				Util_UsageStatistics::value_per_period_seconds(
				$requests_total, $summary );
			$pagecache['hit_rate'] = Util_UsageStatistics::percent(
				$requests_hits, $requests_total );

		}

		$summary['pagecache'] = $pagecache;
		return $summary;
	}
}
