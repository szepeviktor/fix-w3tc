<?php
namespace W3TC;

/**
 * The W3 Total Cache plugin integration
 *
 * @package wp-cli
 * @subpackage commands/third-party
 */
class W3TotalCache_Command extends \WP_CLI_Command {
	/**
	 * Creates missing files, writes apache/nginx rules.
	 *
	 * ## OPTIONS
	 * [<server>]
	 * : Subcommand defines server type:
	 *   apache   create rules for apache server
	 *   nginx    create rules for nginx server
	 */
	function fix_environment( $args = array(), $vars = array() ) {
		$server_type = array_shift( $args );
		switch ( $server_type ) {
		case 'apache':
			$_SERVER['SERVER_SOFTWARE'] = 'Apache';
			break;
		case 'nginx':
			$_SERVER['SERVER_SOFTWARE'] = 'nginx';
			break;
		}

		try {
			$config = Dispatcher::config();
			$environment = Dispatcher::component( 'Root_Environment' );
			$environment->fix_in_wpadmin( $config, true );
		} catch ( Util_Environment_Exceptions $e ) {
			\WP_CLI::error( __( 'Environment adjustment failed with error:' . $e->getCombinedMessage(), 'w3-total-cache' ) );
		}

		\WP_CLI::success( __( 'Environment adjusted.', 'w3-total-cache' ) );
	}



	/**
	 * Clear something from the cache.
	 *
	 * ## OPTIONS
	 * <cache>
	 * : Cache to flush
	 * all         flush all caches
	 * posts       flush posts (pagecache and further)
	 * post        flush the page cache
	 * database    flush the database cache
	 * object      flush the object cache
	 * minify      flush the minify cache
	 *
	 * [--post_id=<id>]
	 * : flush a specific post ID
	 *
	 * [--permalink=<post-permalink>]
	 * : flush a specific permalink
	 *
	 * ## EXAMPLES
	 *     # Flush all
	 *     $ wp w3-total-cache flush all
	 *
	 *     # Flush pagecache and reverse proxies
	 *     $ wp w3-total-cache flush posts
	 */
	function flush( $args = array(), $vars = array() ) {
		$args = array_unique( $args );

		do {
			$cache_type = array_shift( $args );

			switch ( $cache_type ) {
			case 'all':
				try {
					w3tc_flush_all();
				}
				catch ( \Exception $e ) {
					\WP_CLI::error( __( 'Flushing all failed.', 'w3-total-cache' ) );
				}
				\WP_CLI::success( __( 'Everything flushed successfully.', 'w3-total-cache' ) );
				break;

			case 'posts':
				try {
					w3tc_flush_posts();
				}
				catch ( \Exception $e ) {
					\WP_CLI::error( __( 'Flushing posts/pages failed.', 'w3-total-cache' ) );
				}
				\WP_CLI::success( __( 'Posts/pages flushed successfully.', 'w3-total-cache' ) );
				break;

			case 'db':
			case 'database':
				try {
					$w3_db = Dispatcher::component( 'CacheFlush' );
					$w3_db->dbcache_flush();
				}
				catch ( \Exception $e ) {
					\WP_CLI::error( __( 'Flushing the DB cache failed.', 'w3-total-cache' ) );
				}
				\WP_CLI::success( __( 'The DB cache is flushed successfully.', 'w3-total-cache' ) );
				break;

			case 'minify':
				try {
					$w3_minify = Dispatcher::component( 'CacheFlush' );
					$w3_minify->minifycache_flush();
				}
				catch ( \Exception $e ) {
					\WP_CLI::error( __( 'Flushing the minify cache failed.', 'w3-total-cache' ) );
				}
				\WP_CLI::success( __( 'The minify cache is flushed successfully.', 'w3-total-cache' ) );
				break;

			case 'object':
				try {
					$w3_objectcache = Dispatcher::component( 'CacheFlush' );
					$w3_objectcache->objectcache_flush();
				}
				catch ( \Exception $e ) {
					\WP_CLI::error( __( 'Flushing the object cache failed.', 'w3-total-cache' ) );
				}
				\WP_CLI::success( __( 'The object cache is flushed successfully.', 'w3-total-cache' ) );
				break;

			case 'post':
				if ( isset( $vars['post_id'] ) ) {
					if ( is_numeric( $vars['post_id'] ) ) {
						try {
							w3tc_flush_post( $vars['post_id'] );
						}
						catch ( \Exception $e ) {
							\WP_CLI::error( __( 'Flushing the page from cache failed.', 'w3-total-cache' ) );
						}
						\WP_CLI::success( __( 'The page is flushed from cache successfully.', 'w3-total-cache' ) );
					} else {
						\WP_CLI::error( __( 'This is not a valid post id.', 'w3-total-cache' ) );
					}
				}
				elseif ( isset( $vars['permalink'] ) ) {
					try {
						w3tc_flush_url( $vars['permalink'] );
					}
					catch ( \Exception $e ) {
						\WP_CLI::error( __( 'Flushing the page from cache failed.', 'w3-total-cache' ) );
					}
					\WP_CLI::success( __( 'The page is flushed from cache successfully.', 'w3-total-cache' ) );
				} else {
					if ( isset( $flushed_page_cache ) && $flushed_page_cache )
						break;

					try {
						w3tc_flush_posts();
					}
					catch ( \Exception $e ) {
						\WP_CLI::error( __( 'Flushing the page cache failed.', 'w3-total-cache' ) );
					}
					\WP_CLI::success( __( 'The page cache is flushed successfully.', 'w3-total-cache' ) );
				}
				break;

			default:
				\WP_CLI::error( __( 'Not specified what to flush', 'w3-total-cache' ) );
			}
		} while ( !empty( $args ) );
	}

	/**
	 * Get or set option.
	 *
	 * Options modifications don't update your .htaccess automatically.
	 * Use fix_environment command afterwards to do it.
	 *
	 * ## OPTIONS
	 * <operation>
	 * : operation to do
	 * get  get option value
	 * set  set option value
	 * <name>
	 * : option name
	 *
	 * [<value>]
	 * : (for set operation) Value to set
	 *
	 * [--state]
	 * : use state, not config
	 * state is used for backend notifications
	 *
	 * [--master]
	 * : use master config/state
     *
	 * [--type=<type>]
	 * : type of data used boolean/string/integer/array. Default string
	 *
	 * [--delimiter=<delimiter>]
	 * : delimiter to use for array type values
	 *
	 * ## EXAMPLES
	 *     # get if pagecache enabled
	 *     $ wp w3-total-cache option get pgcache.enabled --type=boolean
	 *
	 *     # enable pagecache
	 *     $ wp w3-total-cache option set pgcache.enabled true --type=boolean
	 *
	 *     # don't show wp-content permissions notification
	 *     $ wp w3-total-cache option set common.hide_note_wp_content_permissions true --state --type=boolean
	 */
	function option( $args = array(), $vars = array() ) {
		$op = array_shift( $args );
		$name = array_shift( $args );

		if ( empty( $name ) ) {
			\WP_CLI::error( __( '<name> parameter is not specified', 'w3-total-cache' ) );
		}
		if ( strpos( $name, '::' ) !== FALSE ) {
			$name = explode('::', $name);
		}

		$c = null;
		if ( isset( $vars['state'] ) ) {
			if ( isset( $vars['master'] ) )
				$c = Dispatcher::config_state_master();
			else
				$c = Dispatcher::config_state();
		} else {
			if ( isset( $vars['master'] ) )
				$c = Dispatcher::config_master();
			else
				$c = Dispatcher::config();
		}

		if ( $op == 'get') {
			$type =( isset( $vars['type'] ) ? $vars['type'] : 'string' );

			if ( $type == 'boolean' )
				$v = $c->get_boolean( $name ) ? 'true' : 'false';
			elseif ( $type == 'integer' )
				$v = $c->get_integer( $name );
			elseif ( $type == 'string' )
				$v = $c->get_string( $name );
			elseif ( $type == 'array' )
				$v = json_encode( $c->get_array( $name ), JSON_PRETTY_PRINT );
			else {
				\WP_CLI::error( __( 'Unknown type ' . $type, 'w3-total-cache' ) );
			}

			echo $v . "\n";
		} elseif ( $op == 'set' ) {
			$type =( isset( $vars['type'] ) ? $vars['type'] : 'string' );

			if ( count( $args ) <= 0 ) {
				\WP_CLI::error( __( '<value> parameter is not specified', 'w3-total-cache' ) );
			}
			$value = array_shift( $args );

			if ( $type == 'boolean' ) {
				if ( $value == 'true' || $value == '1' || $value == 'on' )
					$v = true;
				elseif ( $value == 'false' || $value == '0' || $value == 'off' )
					$v = false;
				else {
					\WP_CLI::error( __( '<value> parameter ' . $value . ' is not boolean', 'w3-total-cache' ) );
				}
			} elseif ( $type == 'integer' )
				$v = (integer)$value;
			elseif ( $type == 'string' )
				$v = $value;
			elseif ( $type == 'array' ) {
				$delimiter =( isset( $vars['delimiter'] ) ? $vars['delimiter'] : ',' );
				$v = explode($delimiter, $value );
			} else {
				\WP_CLI::error( __( 'Unknown type ' . $type, 'w3-total-cache' ) );
			}

			try {
				$c->set( $name, $v );
				$c->save();
				\WP_CLI::success( __( 'Option updated successfully.', 'w3-total-cache' ) );
			} catch ( \Exception $e ) {
				\WP_CLI::error( __( 'Option value update failed.', 'w3-total-cache' ) );
			}

		} else {
			\WP_CLI::error( __( '<operation> parameter is not specified', 'w3-total-cache' ) );
		}
	}

	/**
	 * Update query string for all static files
	 */
	function querystring() {
		try {
			$w3_querystring = Dispatcher::component( 'CacheFlush' );
			$w3_querystring->browsercache_flush();
		}
		catch ( \Exception $e ) {
			\WP_CLI::error( __( 'updating the query string failed. with error: ' . $e->getMessage(), 'w3-total-cache' ) );
		}

		\WP_CLI::success( __( 'The query string was updated successfully.', 'w3-total-cache' ) );

	}

	/**
	 * Purges URL's from cdn and varnish if enabled
	 */
	function cdn_purge( $args = array() ) {
		$purgeitems = array();
		foreach ( $args as $file ) {
			$cdncommon = Dispatcher::component( 'Cdn_Core' );
			$local_path = WP_ROOT . $file;
			$remote_path = $file;
			$purgeitems[] = $cdncommon->build_file_descriptor( $local_path, $remote_path );
		}

		try {
			$w3_cdn_purge = Dispatcher::component( 'CacheFlush' );
			$w3_cdn_purge->cdn_purge_files( $purgeitems );
		}
		catch ( \Exception $e ) {
			\WP_CLI::error( __( 'Files did not successfully purge with error: ' . $e->getMessage(), 'w3-total-cache' ) );
		}
		\WP_CLI::success( __( 'Files purged successfully.', 'w3-total-cache' ) );

	}

	/**
	 * Tell opcache to reload PHP files
	 *
	 * @param array   $args
	 */
	function opcache_flush_file( $args = array() ) {
		try {
			$method = array_shift( $args );
			if ( !in_array( $method, array( 'SNS', 'local' ) ) )
				\WP_CLI::error( $method . __( ' is not supported. Change to SNS or local to reload opcache files', 'w3-total-cache' ) );
			if ( $method == 'SNS' ) {
				$w3_cache = Dispatcher::component( 'CacheFlush' );
				$w3_cache->opcache_flush_file( $args[0] );
			} else {
				$url = WP_PLUGIN_URL . '/' . dirname( W3TC_FILE ) . '/pub/opcache.php';
				$path = parse_url( $url, PHP_URL_PATH );
				$post = array(
					'method' => 'POST',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'body' => array(
						'nonce' => wp_hash( $path ),
						'command' => 'flush_file',
						'file' => $args[0]
					),
				);
				$result = wp_remote_post( $url, $post );
				if ( is_wp_error( $result ) ) {
					\WP_CLI::error( __( 'Files did not successfully reload with error: ' . $result, 'w3-total-cache' ) );
				} elseif ( $result['response']['code'] != '200' ) {
					\WP_CLI::error( __( 'Files did not successfully reload with message: ', 'w3-total-cache' ) . $result['body'] );
				}
			}
		}
		catch ( \Exception $e ) {
			\WP_CLI::error( __( 'Files did not successfully reload with error: ' . $e->getMessage(), 'w3-total-cache' ) );
		}
		\WP_CLI::success( __( 'Files reloaded successfully.', 'w3-total-cache' ) );

	}

	/**
	 * SNS/local file.php Tells opcache to compile files
	 *
	 * @param array   $args
	 */
	function opcache_flush( $args = array() ) {
		try {
			$method = array_shift( $args );
			if ( !in_array( $method, array( 'SNS', 'local' ) ) )
				\WP_CLI::error( $method . __( ' is not supported. Change to SNS or local to delete opcache files', 'w3-total-cache' ) );

			if ( $method == 'SNS' ) {
				$w3_cache = Dispatcher::component( 'CacheFlush' );
				$w3_cache->opcache_flush();
			} else {
				$url = WP_PLUGIN_URL . '/' . dirname( W3TC_FILE ) . '/pub/opcache.php';
				$path = parse_url( $url, PHP_URL_PATH );
				$post = array(
					'method' => 'POST',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'body' => array(
						'nonce' => wp_hash( $path ),
						'command' => 'flush'
					),
				);
				$result = wp_remote_post( $url, $post );
				if ( is_wp_error( $result ) ) {
					\WP_CLI::error( __( 'Files did not successfully delete with error ' . $result, 'w3-total-cache' ) );
				} elseif ( $result['response']['code'] != '200' ) {
					\WP_CLI::error( __( 'Files did not successfully delete with message: ', 'w3-total-cache' ). $result['body'] );
				}
			}
		}
		catch ( \Exception $e ) {
			\WP_CLI::error( __( 'Files did not successfully delete with error: ' . $e->getMessage(), 'w3-total-cache' ) );
		}
		\WP_CLI::success( __( 'Files deleted successfully.', 'w3-total-cache' ) );

	}

	/**
	 * Generally triggered from a cronjob, allows for manual Garbage collection of page cache to be triggered
	 */
	function pgcache_cleanup() {
		try {
			$pgcache_cleanup = Dispatcher::component( 'PgCache_Plugin_Admin' );
			$pgcache_cleanup->cleanup();
		} catch ( \Exception $e ) {
			\WP_CLI::error( __( 'PageCache Garbage cleanup did not start with error: ' . $e->getMessage(), 'w3-total-cache' ) );
		}

		\WP_CLI::success( __( 'PageCache Garbage cleanup triggered successfully.', 'w3-total-cache' ) );
	}

    /**
     * Prime the page cache (cache preloader)
     *
     * ## OPTIONS
     *
     * [<stop>]
     * : Stop the active page cache prime session.
     *
     * [--batch=<size>]
     * : Max number of pages to create per batch. If not set, the value given in
     * W3TC's Page Cache > Pages per Interval field is used. If size is 0 then
     * all pages within the sitemap will be created/cached without the use of a
     * batch and without waiting.
     *
     * [--interval=<seconds>]
     * : Number of seconds to wait before creating another batch. If not set,
     * the value given in W3TC's Page Cache > Update Interval field is used.
     *
     * [--sitemap=<url>]
     * : The sitemap url specifying the pages to prime cache. If not set, the
     * value given in W3TC's Page Cache > Sitemap URL field is used.
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
    function prime( $args = array() , $vars = array() ) {
        try {
            $action = array_shift( $args ) ;                
            $w3_prime = Dispatcher::component( 'PgCache_Plugin_Admin' );

            if ( $action == 'stop' ) {
                if ( w3tc_wpcli_stop_prime( $result ) == false ) {
                    \WP_CLI::warning( __( $result, 'w3-total-cache' ) );
                } else {
                    \WP_CLI::success( __( 'Page cache priming stopped.', 'w3-total-cache' ) );
                }
            } elseif ( strlen( $action ) > 0 ) {
                $val = \WP_CLI::colorize( __( "%Y$action%n", 'w3-total-cache' ) );
                \WP_CLI::error( __( "Unrecognized argument - $val.", 'w3-total-cache' ) );
            } else {
                $config = Dispatcher::config();
                $user_limit = - 1;
                $user_interval = - 1;
                $user_sitemap = "";

                if ( isset( $vars['interval'] ) && is_numeric( $vars['interval'] ) ) {
                    $user_interval = intval( $vars['interval'] );
                }

                if ( isset( $vars['batch'] ) && is_numeric( $vars['batch'] ) ) {
                    $user_limit = intval( $vars['batch'] );
                }

                if ( isset( $vars['sitemap'] ) && !empty( $vars['sitemap'] ) ) {
                    $user_sitemap = trim( $vars['sitemap'] );
                }

                $limit = $user_limit == - 1 ? $config->get_integer( 'pgcache.prime.limit' ) : $user_limit;
                $interval = $user_interval == - 1 ? $config->get_integer( 'pgcache.prime.interval' ) : $user_interval;
                $sitemap = empty( $user_sitemap ) ? $config->get_string( 'pgcache.prime.sitemap' ) : $user_sitemap;
                
                if ( empty( $sitemap ) ) {
                    \WP_CLI::error( __( "Prime page cache halted - Unable to load sitemap. A sitemap is needed to prime the page cache.", 'w3-total-cache' ) );
                } elseif ( ( $res = $w3_prime->prime_cli( $limit, $interval, $sitemap, 0, true ) ) === false ) {
                    \WP_CLI::warning( __( 'Page cache priming is already active.', 'w3-total-cache' ) );
                } else {
                    /**
                     * Use inter-process messaging, if available, to help manage the prime
                     */                
                    if ( extension_loaded( 'sysvmsg' ) ) {
                        msg_send( msg_get_queue( 99909 ) , 99, "prime_started" );
                    }

                    \WP_CLI::success( __( "Page cache priming started $res.", 'w3-total-cache' ) );
                }
            }
        } catch( \Exception $e ) {
            \WP_CLI::error( __( 'Error: ' . $e->getMessage(), 'w3-total-cache' ) );
        }
    }        
}

if ( method_exists( '\WP_CLI', 'add_command' ) ) {
	\WP_CLI::add_command( 'w3-total-cache', '\W3TC\W3TotalCache_Command' );
	\WP_CLI::add_command( 'total-cache', '\W3TC\W3TotalCache_Command' );
} else {
	// backward compatibility
	\WP_CLI::addCommand( 'w3-total-cache', '\W3TC\W3TotalCache_Command' );
	\WP_CLI::addCommand( 'total-cache', '\W3TC\W3TotalCache_Command' );
}
