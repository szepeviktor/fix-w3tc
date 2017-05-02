<?php
namespace W3TC;

/**
 * W3 Object Cache object
 */
class ObjectCache_WpObjectCache_Regular {
	/**
	 * Internal cache array
	 *
	 * @var array
	 */
	var $cache = array();

	/**
	 * Array of global groups
	 *
	 * @var array
	 */
	var $global_groups = array();

	/**
	 * List of non-persistent groups
	 *
	 * @var array
	 */
	var $nonpersistent_groups = array();

	/**
	 * Total count of calls
	 *
	 * @var integer
	 */
	var $cache_total = 0;

	/**
	 * Cache hits count
	 *
	 * @var integer
	 */
	var $cache_hits = 0;

	/**
	 * Cache misses count
	 *
	 * @var integer
	 */
	var $cache_misses = 0;

	/**
	 * Total time
	 *
	 * @var integer
	 */
	var $time_total = 0;

	/**
	 * Store debug information of w3tc using
	 *
	 * @var array
	 */
	var $debug_info = array();

	/**
	 * Blog id of cache
	 *
	 * @var integer
	 */
	private $_blog_id;

	/**
	 * Key cache
	 *
	 * @var array
	 */
	var $_key_cache = array();

	/**
	 * Config
	 */
	var $_config = null;

	/**
	 * Caching flag
	 *
	 * @var boolean
	 */
	var $_caching = false;

	/**
	 * Dynamic Caching flag
	 *
	 * @var boolean
	 */
	var $_can_cache_dynamic = null;
	/**
	 * Cache reject reason
	 *
	 * @var string
	 */
	private $cache_reject_reason = '';

	/**
	 * Lifetime
	 *
	 * @var integer
	 */
	var $_lifetime = null;

	/**
	 * Debug flag
	 *
	 * @var boolean
	 */
	var $_debug = false;

	/**
	 * PHP5 style constructor
	 */
	function __construct() {
		global $_wp_using_ext_object_cache;

		$this->_config = Dispatcher::config();
		$this->_lifetime = $this->_config->get_integer( 'objectcache.lifetime' );
		$this->_debug = $this->_config->get_boolean( 'objectcache.debug' );
		$this->_caching = $_wp_using_ext_object_cache = $this->_can_cache();
		$this->global_groups = $this->_config->get_array( 'objectcache.groups.global' );
		$this->nonpersistent_groups = $this->_config->get_array( 'objectcache.groups.nonpersistent' );

		$this->_blog_id = Util_Environment::blog_id();
	}

	/**
	 * Get from the cache
	 *
	 * @param string  $id
	 * @param string  $group
	 * @return mixed
	 */
	function get( $id, $group = 'default', $force = false, &$found = null ) {
		if ( $this->_debug ) {
			$time_start = Util_Debug::microtime();
		}

		$key = $this->_get_cache_key( $id, $group );
		$internal = isset( $this->cache[$key] );
		$fallback_used = false;

		if ( $internal && !$force ) {
			$found = true;
			$value = $this->cache[$key];
		} elseif ( $this->_caching &&
			!in_array( $group, $this->nonpersistent_groups ) &&
			$this->_check_can_cache_runtime( $group ) ) {
			$cache = $this->_get_cache( null, $group );
			$v = $cache->get( $key );

			/* for debugging
				$a = $cache->_get_with_old_raw( $key );
				$path = $cache->get_full_path( $key);
				$returned = 'x ' . $path . ' ' .
					(is_readable( $path ) ? ' readable ' : ' not-readable ') .
					json_encode($a);
			*/

			if ( is_array( $v ) && $v['content'] != null ) {
				$found = true;
				$value = $v['content'];
			} else {
				$found = false;
				$value = false;
			}
		} else {
			$found = false;
			$value = false;
		}

		if ( $value === null ) {
			$value = false;
		}

		if ( is_object( $value ) ) {
			$value = clone $value;
		}

		if ( !$found &&
			$this->_is_transient_group( $group ) &&
			$this->_config->get_boolean( 'objectcache.fallback_transients' ) ) {
			$fallback_used = true;
			$value = $this->_transient_fallback_get( $id, $group );
			$found = ( $value !== false );
		}

		$this->cache_total++;

		if ( $value !== false ) {
			$this->cache[$key] = $value;
			$this->cache_hits++;
		} else {
			$this->cache_misses++;
		}

		/**
		 * Add debug info
		 */
		if ( $this->_debug ) {
			$time = Util_Debug::microtime() - $time_start;
			$this->time_total += $time;

			if ( !$group ) {
				$group = 'default';
			}

			if ( $fallback_used ) {
				if ( !$found )
					$returned = 'not in db';
				else
					$returned = 'from db fallback';
			} else {
				if ( !$found )
					$returned = 'not in cache';
				else {
					if ( $internal )
						$returned = 'from in-call cache';
					else
						$returned = 'from persistent cache';
				}
			}

			$this->debug_info[] = array(
				'id' => $id,
				'group' => $group,
				'operation' => 'get',
				'returned' => $returned,
				'data_size' => ( $value ? strlen( serialize( $value ) ) : '' ),
				'time' => $time
			);
		}

		return $value;
	}

	/**
	 * Set to the cache
	 *
	 * @param string  $id
	 * @param mixed   $data
	 * @param string  $group
	 * @param integer $expire
	 * @return boolean
	 */
	function set( $id, $data, $group = 'default', $expire = 0 ) {
		$key = $this->_get_cache_key( $id, $group );

		if ( is_object( $data ) ) {
			$data = clone $data;
		}

		$this->cache[$key] = $data;
		$return = true;
		$ext_return = false;

		if ( $this->_caching &&
			!in_array( $group, $this->nonpersistent_groups ) &&
			$this->_check_can_cache_runtime( $group ) ) {
			$cache = $this->_get_cache( null, $group );

			if ( $id == 'alloptions' && $group == 'options' ) {
				// alloptions are deserialized on the start when some classes are not loaded yet
				// so postpone it until requested
				foreach ( $data as $k => $v ) {
					if ( is_object( $v ) ) {
						$data[$k] = serialize( $v );
					}
				}
			}

			$v = array( 'content' => $data );
			$ext_return = $cache->set( $key, $v,
				( $expire ? $expire : $this->_lifetime ) );
			$return = $ext_return;
		}

		if ( $this->_is_transient_group( $group ) &&
			$this->_config->get_boolean( 'objectcache.fallback_transients' ) ) {
			$this->_transient_fallback_set( $id, $data, $group, $expire );
		}

		if ( $this->_debug ) {
			$this->debug_info[] = array(
				'id' => $id,
				'group' => $group,
				'operation' => 'set',
				'returned' => ( $ext_return ? 'put in cache' : 'discarded' ),
				'data_size' => ( $data ? strlen( serialize( $data ) ) : '' ),
				'time' => 0
			);
		}

		return $return;
	}

	/**
	 * Delete from the cache
	 *
	 * @param string  $id
	 * @param string  $group
	 * @param bool    $force
	 * @return boolean
	 */
	function delete( $id, $group = 'default', $force = false ) {
		if ( !$force && $this->get( $id, $group ) === false ) {
			return false;
		}

		$key = $this->_get_cache_key( $id, $group );
		$return = true;
		unset( $this->cache[$key] );

		if ( $this->_caching && !in_array( $group, $this->nonpersistent_groups ) ) {
			$cache = $this->_get_cache( null, $group );
			$return = $cache->delete( $key );
		}

		if ( $this->_is_transient_group( $group ) &&
			$this->_config->get_boolean( 'objectcache.fallback_transients' ) ) {
			$this->_transient_fallback_delete( $id, $group );
		}

		if ( $this->_debug ) {
			$this->debug_info[] = array(
				'id' => $id,
				'group' => $group,
				'operation' => 'delete',
				'returned' => ( $return ? 'deleted' : 'discarded' ),
				'data_size' => 0,
				'time' => 0
			);
		}

		return $return;
	}

	/**
	 * Add to the cache
	 *
	 * @param string  $id
	 * @param mixed   $data
	 * @param string  $group
	 * @param integer $expire
	 * @return boolean
	 */
	function add( $id, $data, $group = 'default', $expire = 0 ) {
		if ( $this->get( $id, $group ) !== false ) {
			return false;
		}

		return $this->set( $id, $data, $group, $expire );
	}

	/**
	 * Replace in the cache
	 *
	 * @param string  $id
	 * @param mixed   $data
	 * @param string  $group
	 * @param integer $expire
	 * @return boolean
	 */
	function replace( $id, $data, $group = 'default', $expire = 0 ) {
		if ( $this->get( $id, $group ) === false ) {
			return false;
		}

		return $this->set( $id, $data, $group, $expire );
	}

	/**
	 * Reset keys
	 *
	 * @return boolean
	 */
	function reset() {
		global $_wp_using_ext_object_cache;

		$_wp_using_ext_object_cache = $this->_caching;

		return true;
	}

	/**
	 * Flush cache
	 *
	 * @return boolean
	 */
	function flush( $reason = '' ) {
		$this->cache = array();

		global $w3_multisite_blogs;
		if ( isset( $w3_multisite_blogs ) ) {
			foreach ( $w3_multisite_blogs as $blog ) {
				$cache = $this->_get_cache( $blog->userblog_id );
				$cache->flush();
			}
		} else {
			$cache = $this->_get_cache( 0 );
			$cache->flush();

			$cache = $this->_get_cache();
			$cache->flush();
		}

		if ( $this->_debug ) {
			$this->debug_info[] = array(
				'id' => '',
				'group' => '',
				'operation' => 'flush',
				'returned' => $reason,
				'data_size' => 0,
				'time' => 0
			);
		}

		return true;
	}

	/**
	 * Add global groups
	 *
	 * @param array   $groups
	 * @return void
	 */
	function add_global_groups( $groups ) {
		if ( !is_array( $groups ) ) {
			$groups = (array) $groups;
		}

		$this->global_groups = array_merge( $this->global_groups, $groups );
		$this->global_groups = array_unique( $this->global_groups );
	}

	/**
	 * Add non-persistent groups
	 *
	 * @param array   $groups
	 * @return void
	 */
	function add_nonpersistent_groups( $groups ) {
		if ( !is_array( $groups ) ) {
			$groups = (array) $groups;
		}

		$this->nonpersistent_groups = array_merge( $this->nonpersistent_groups, $groups );
		$this->nonpersistent_groups = array_unique( $this->nonpersistent_groups );
	}

	/**
	 * Increment numeric cache item's value
	 *
	 * @param int|string $key    The cache key to increment
	 * @param int     $offset The amount by which to increment the item's value. Default is 1.
	 * @param string  $group  The group the key is in.
	 * @return bool|int False on failure, the item's new value on success.
	 */
	function incr( $key, $offset = 1, $group = 'default' ) {
		$value = $this->get( $key, $group );
		if ( $value === false )
			return false;

		if ( !is_numeric( $value ) )
			$value = 0;

		$offset = (int) $offset;
		$value += $offset;

		if ( $value < 0 )
			$value = 0;
		$this->replace( $key, $value, $group );
		return $value;
	}

	/**
	 * Decrement numeric cache item's value
	 *
	 * @param int|string $key    The cache key to increment
	 * @param int     $offset The amount by which to decrement the item's value. Default is 1.
	 * @param string  $group  The group the key is in.
	 * @return bool|int False on failure, the item's new value on success.
	 */
	function decr( $key, $offset = 1, $group = 'default' ) {
		$value = $this->get( $key, $group );
		if ( $value === false )
			return false;

		if ( !is_numeric( $value ) )
			$value = 0;

		$offset = (int) $offset;
		$value -= $offset;

		if ( $value < 0 )
			$value = 0;
		$this->replace( $key, $value, $group );
		return $value;
	}

	private function _transient_fallback_get( $transient, $group ) {
		if ( $group == 'transient' ) {
			$transient_option = '_transient_' . $transient;
			if ( function_exists( 'wp_installing') && ! wp_installing() ) {
				// If option is not in alloptions, it is not autoloaded and thus has a timeout
				$alloptions = wp_load_alloptions();
				if ( !isset( $alloptions[$transient_option] ) ) {
					$transient_timeout = '_transient_timeout_' . $transient;
					$timeout = get_option( $transient_timeout );
					if ( false !== $timeout && $timeout < time() ) {
						delete_option( $transient_option  );
						delete_option( $transient_timeout );
						$value = false;
					}
				}
			}

			if ( ! isset( $value ) )
				$value = get_option( $transient_option );
		} elseif ( $group == 'site-transient' ) {
			// Core transients that do not have a timeout. Listed here so querying timeouts can be avoided.
			$no_timeout = array('update_core', 'update_plugins', 'update_themes');
			$transient_option = '_site_transient_' . $transient;
			if ( ! in_array( $transient, $no_timeout ) ) {
				$transient_timeout = '_site_transient_timeout_' . $transient;
				$timeout = get_site_option( $transient_timeout );
				if ( false !== $timeout && $timeout < time() ) {
					delete_site_option( $transient_option  );
					delete_site_option( $transient_timeout );
					$value = false;
				}
			}

			if ( ! isset( $value ) )
				$value = get_site_option( $transient_option );
		} else {
			$value = false;
		}

		return $value;
	}

	private function _transient_fallback_delete( $transient, $group ) {
		if ( $group == 'transient' ) {
			$option_timeout = '_transient_timeout_' . $transient;
			$option = '_transient_' . $transient;
			$result = delete_option( $option );
			if ( $result )
				delete_option( $option_timeout );
		} elseif ( $group == 'site-transient' ) {
			$option_timeout = '_site_transient_timeout_' . $transient;
			$option = '_site_transient_' . $transient;
			$result = delete_site_option( $option );
			if ( $result )
				delete_site_option( $option_timeout );
		}
	}

	private function _transient_fallback_set( $transient, $value, $group, $expiration ) {
		if ( $group == 'transient' ) {
			$transient_timeout = '_transient_timeout_' . $transient;
			$transient_option = '_transient_' . $transient;
			if ( false === get_option( $transient_option ) ) {
				$autoload = 'yes';
				if ( $expiration ) {
					$autoload = 'no';
					add_option( $transient_timeout, time() + $expiration, '', 'no' );
				}
				$result = add_option( $transient_option, $value, '', $autoload );
			} else {
				// If expiration is requested, but the transient has no timeout option,
				// delete, then re-create transient rather than update.
				$update = true;
				if ( $expiration ) {
					if ( false === get_option( $transient_timeout ) ) {
						delete_option( $transient_option );
						add_option( $transient_timeout, time() + $expiration, '', 'no' );
						$result = add_option( $transient_option, $value, '', 'no' );
						$update = false;
					} else {
						update_option( $transient_timeout, time() + $expiration );
					}
				}
				if ( $update ) {
					$result = update_option( $transient_option, $value );
				}
			}
		} elseif ( $group == 'site-transient' ) {
			$transient_timeout = '_site_transient_timeout_' . $transient;
			$option = '_site_transient_' . $transient;
			if ( false === get_site_option( $option ) ) {
				if ( $expiration )
					add_site_option( $transient_timeout, time() + $expiration );
				$result = add_site_option( $option, $value );
			} else {
				if ( $expiration )
					update_site_option( $transient_timeout, time() + $expiration );
				$result = update_site_option( $option, $value );
			}
		}
	}

	/**
	 * Print Object Cache stats
	 *
	 * @return void
	 */
	function stats() {
		echo '<h2>Summary</h2>';
		echo '<p>';
		echo '<strong>Engine</strong>: ' . Cache::engine_name( $this->_config->get_string( 'objectcache.engine' ) ) . '<br />';
		echo '<strong>Caching</strong>: ' . ( $this->_caching ? 'enabled' : 'disabled' ) . '<br />';

		if ( !$this->_caching ) {
			echo '<strong>Reject reason</strong>: ' . $this->get_reject_reason() . '<br />';
		}

		echo '<strong>Total calls</strong>: ' . $this->cache_total . '<br />';
		echo '<strong>Cache hits</strong>: ' . $this->cache_hits . '<br />';
		echo '<strong>Cache misses</strong>: ' . $this->cache_misses . '<br />';
		echo '<strong>Total time</strong>: '. round( $this->time_total, 4 ) . 's';
		echo '</p>';

		echo '<h2>Cache info</h2>';

		if ( $this->_debug ) {
			echo '<table cellpadding="0" cellspacing="3" border="1">';
			echo '<tr><td>#</td><td>Status</td><td>Source</td><td>Data size (b)</td><td>Query time (s)</td><td>ID:Group</td></tr>';

			foreach ( $this->debug_info as $index => $debug ) {
				echo '<tr>';
				echo '<td>' . ( $index + 1 ) . '</td>';
				echo '<td>' . ( $debug['cached'] ? 'cached' : 'not cached' ) . '</td>';
				echo '<td>' . ( $debug['internal'] ? 'internal' : 'persistent' ) . '</td>';
				echo '<td>' . $debug['data_size'] . '</td>';
				echo '<td>' . round( $debug['time'], 4 ) . '</td>';
				echo '<td>' . sprintf( '%s:%s', $debug['id'], $debug['group'] ) . '</td>';
				echo '</tr>';
			}

			echo '</table>';
		} else {
			echo '<p>Enable debug mode.</p>';
		}
	}

	/**
	 * Switches context to another blog
	 *
	 * @param integer $blog_id
	 */
	function switch_blog( $blog_id ) {
		$this->reset();
		$this->_blog_id = $blog_id;
	}

	/**
	 * Returns cache key
	 *
	 * @param string  $id
	 * @param string  $group
	 * @return string
	 */
	function _get_cache_key( $id, $group = 'default' ) {
		if ( !$group ) {
			$group = 'default';
		}

		$blog_id = $this->_blog_id;
		if ( in_array( $group, $this->global_groups ) )
			$blog_id = 0;

		return $blog_id . $group . $id;
	}

	public function get_usage_statistics_cache_config() {
		$engine = $this->_config->get_string( 'objectcache.engine' );

		switch ( $engine ) {
		case 'memcached':
			$engineConfig = array(
				'servers' => $this->_config->get_array( 'objectcache.memcached.servers' ),
				'persistent' => $this->_config->get_boolean( 'objectcache.memcached.persistent' ),
				'aws_autodiscovery' => $this->_config->get_boolean( 'objectcache.memcached.aws_autodiscovery' ),
				'username' => $this->_config->get_string( 'objectcache.memcached.username' ),
				'password' => $this->_config->get_string( 'objectcache.memcached.password' )
			);
			break;

		case 'redis':
			$engineConfig = array(
				'servers' => $this->_config->get_array( 'objectcache.redis.servers' ),
				'persistent' => $this->_config->get_boolean( 'objectcache.redis.persistent' ),
				'dbid' => $this->_config->get_integer( 'objectcache.redis.dbid' ),
				'password' => $this->_config->get_string( 'objectcache.redis.password' )
			);
			break;

		default:
			$engineConfig = array();
		}

		$engineConfig['engine'] = $engine;
		return $engineConfig;
	}

	/**
	 * Returns cache object
	 *
	 * @param int|null $blog_id
	 * @param string  $group
	 * @return W3_Cache_Base
	 */
	function _get_cache( $blog_id = null, $group = '' ) {
		static $cache = array();

		if ( is_null( $blog_id ) && !in_array( $group, $this->global_groups ) )
			$blog_id = $this->_blog_id;
		elseif ( is_null( $blog_id ) )
			$blog_id = 0;

		if ( !isset( $cache[$blog_id] ) ) {
			$engine = $this->_config->get_string( 'objectcache.engine' );

			switch ( $engine ) {
			case 'memcached':
				$engineConfig = array(
					'servers' => $this->_config->get_array( 'objectcache.memcached.servers' ),
					'persistent' => $this->_config->get_boolean( 'objectcache.memcached.persistent' ),
					'aws_autodiscovery' => $this->_config->get_boolean( 'objectcache.memcached.aws_autodiscovery' ),
					'username' => $this->_config->get_string( 'objectcache.memcached.username' ),
					'password' => $this->_config->get_string( 'objectcache.memcached.password' )
				);
				break;

			case 'redis':
				$engineConfig = array(
					'servers' => $this->_config->get_array( 'objectcache.redis.servers' ),
					'persistent' => $this->_config->get_boolean( 'objectcache.redis.persistent' ),
					'dbid' => $this->_config->get_integer( 'objectcache.redis.dbid' ),
					'password' => $this->_config->get_string( 'objectcache.redis.password' )
				);
				break;

			case 'file':
				$engineConfig = array(
					'section' => 'object',
					'locking' => $this->_config->get_boolean( 'objectcache.file.locking' ),
					'flush_timelimit' => $this->_config->get_integer( 'timelimit.cache_flush' )
				);
				break;

			default:
				$engineConfig = array();
			}
			$engineConfig['blog_id'] = $blog_id;
			$engineConfig['module'] = 'object';
			$engineConfig['host'] = Util_Environment::host();
			$engineConfig['instance_id'] = Util_Environment::instance_id();

			$cache[$blog_id] = Cache::instance( $engine, $engineConfig );
		}

		return $cache[$blog_id];
	}

	/**
	 * Check if caching allowed on init
	 *
	 * @return boolean
	 */
	function _can_cache() {
		/**
		 * Don't cache in console mode
		 */
		if ( PHP_SAPI === 'cli' ) {
			$this->cache_reject_reason = 'Console mode';

			return false;
		}

		/**
		 * Skip if disabled
		 */
		if ( !$this->_config->get_boolean( 'objectcache.enabled' ) ) {
			$this->cache_reject_reason = 'objectcache.disabled';

			return false;
		}

		/**
		 * Check for DONOTCACHEOBJECT constant
		 */
		if ( defined( 'DONOTCACHEOBJECT' ) && DONOTCACHEOBJECT ) {
			$this->cache_reject_reason = 'DONOTCACHEOBJECT';

			return false;
		}

		return true;
	}

	/**
	 * Returns if we can cache, that condition can change in runtime
	 *
	 * @param unknown $group
	 * @return boolean
	 */
	function _check_can_cache_runtime( $group ) {
		//Need to be handled in wp admin as well as frontend
		if ( $this->_is_transient_group( $group ) )
			return true;

		if ( $this->_can_cache_dynamic != null )
			return $this->_can_cache_dynamic;

		if ( $this->_config->get_boolean( 'objectcache.enabled_for_wp_admin' ) ) {
			$this->_can_cache_dynamic = true;
		} else {
			if ( $this->_caching ) {
				if ( defined( 'WP_ADMIN' ) ) {
					$this->_can_cache_dynamic = false;
					$this->cache_reject_reason = 'WP_ADMIN defined';
					return $this->_can_cache_dynamic;
				}
			}
		}

		return $this->_caching;
	}

	private function _is_transient_group( $group ) {
		return in_array( $group, array( 'transient', 'site-transient' ) ) ;
	}

	public function w3tc_footer_comment( $strings ) {
		if ( $this->_config->get_boolean( 'objectcache.debug' ) ) {
			$strings[] = "~~~~~~~~~~~~~~~~~~~~~~~~~~";
			$strings[] = "Object Cache debug info:";
			$strings[] = "~~~~~~~~~~~~~~~~~~~~~~~~~~";
			$strings[] = sprintf( "%s%s", str_pad( 'Engine: ', 20 ), Cache::engine_name( $this->_config->get_string( 'objectcache.engine' ) ) );
			$strings[] = sprintf( "%s%s", str_pad( 'Caching: ', 20 ), ( $this->_caching ? 'enabled' : 'disabled' ) );

			if ( !$this->_caching ) {
				$strings[] = sprintf( "%s%s", str_pad( 'Reject reason: ', 20 ), $this->cache_reject_reason );
			}

			$strings[] = sprintf( "%s%d", str_pad( 'Total calls: ', 20 ), $this->cache_total );
			$strings[] = sprintf( "%s%d", str_pad( 'Cache hits: ', 20 ), $this->cache_hits );
			$strings[] = sprintf( "%s%d", str_pad( 'Cache misses: ', 20 ), $this->cache_misses );
			$strings[] = sprintf( "%s%.4f", str_pad( 'Total time: ', 20 ), $this->time_total );

			$strings[] = "W3TC Object Cache info:";
			$strings[] = sprintf( "%s | %s | %s | %s | %s | %s | %s",
				str_pad( '#', 5, ' ', STR_PAD_LEFT ),
				str_pad( 'Op', 5, ' ', STR_PAD_BOTH ),
				str_pad( 'Returned', 25, ' ', STR_PAD_BOTH ),
				str_pad( 'Data size (b)', 13, ' ', STR_PAD_LEFT ),
				str_pad( 'Query time (s)', 14, ' ', STR_PAD_LEFT ),
				str_pad( 'Group', 15, ' ', STR_PAD_LEFT ),
				'ID' );

			foreach ( $this->debug_info as $index => $debug ) {
				$strings[] = sprintf( "%s | %s | %s | %s | %s | %s | %s",
					str_pad( $index + 1, 5, ' ', STR_PAD_LEFT ),
					str_pad( $debug['operation'], 5, ' ', STR_PAD_BOTH ),
					str_pad( $debug['returned'], 25, ' ', STR_PAD_BOTH ),
					str_pad( $debug['data_size'], 13, ' ', STR_PAD_LEFT ),
					str_pad( round( $debug['time'], 4 ), 14, ' ', STR_PAD_LEFT ),
					str_pad( $debug['group'], 15, ' ', STR_PAD_LEFT ),
					$debug['id'] );
			}
		} else if ( $this->_config->get_string( 'common.support' ) == '' &&
					!$this->_config->get_boolean( 'common.tweeted' ) ){
			$reason = $this->get_reject_reason();
			$append = ( $reason != '' ? sprintf( ' (%s)', $reason ) : '' );

			$strings[] = sprintf(
				__( 'Object Caching %d/%d objects using %s%s', 'w3-total-cache' ),
				$this->cache_hits, $this->cache_total,
				Cache::engine_name( $this->_config->get_string( 'objectcache.engine' ) ),
				$append );
		}

		return $strings;
	}

	public function w3tc_usage_statistics_of_request( $storage ) {
		$storage->counter_add( 'objectcache_calls_total', $this->cache_total );
		$storage->counter_add( 'objectcache_calls_hits', $this->cache_hits );
	}

	public function get_reject_reason() {
		if ( is_null( $this->cache_reject_reason ) )
			return '';
		return $this->_get_reject_reason_message( $this->cache_reject_reason );
	}

	/**
	 *
	 *
	 * @param unknown $key
	 * @return string|void
	 */
	private function _get_reject_reason_message( $key ) {
		if ( !function_exists( '__' ) )
			return $key;

		switch ( $key ) {
		case 'objectcache.disabled':
			return __( 'Object caching is disabled', 'w3-total-cache' );
		case 'DONOTCACHEOBJECT':
			return __( 'DONOTCACHEOBJECT constant is defined', 'w3-total-cache' );
		default:
			return '';
		}
	}
}
