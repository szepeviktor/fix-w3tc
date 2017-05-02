<?php
namespace W3TC;

/**
 * Redis cache engine
 */
class Cache_Redis extends Cache_Base {
	private $_accessors = array();
	private $_key_version = array();

	private $_persistent;
	private $_password;
	private $_servers;
	private $_dbid;

	/**
	 * constructor
	 *
	 * @param array   $config
	 */
	function __construct( $config ) {
		parent::__construct( $config );

		$this->_persistent = ( isset( $config['persistent'] ) && $config['persistent'] );
		$this->_servers = (array)$config['servers'];
		$this->_password = $config['password'];
		$this->_dbid = $config['dbid'];
	}

	/**
	 * Adds data
	 *
	 * @param string  $key
	 * @param mixed   $var
	 * @param integer $expire
	 * @param string  $group  Used to differentiate between groups of cache values
	 * @return boolean
	 */
	function add( $key, &$var, $expire = 0, $group = '' ) {
		return $this->set( $key, $var, $expire, $group );
	}

	/**
	 * Sets data
	 *
	 * @param string  $key
	 * @param mixed   $var
	 * @param integer $expire
	 * @param string  $group  Used to differentiate between groups of cache values
	 * @return boolean
	 */
	function set( $key, $value, $expire = 0, $group = '' ) {
		$value['key_version'] = $this->_get_key_version( $group );

		$storage_key = $this->get_item_key( $key );
		$accessor = $this->_get_accessor( $storage_key );
		if ( is_null( $accessor ) )
			return false;

		return $accessor->setex( $storage_key, $expire, serialize( $value ) );
	}

	/**
	 * Returns data
	 *
	 * @param string  $key
	 * @param string  $group Used to differentiate between groups of cache values
	 * @return mixed
	 */
	function get_with_old( $key, $group = '' ) {
		$has_old_data = false;

		$storage_key = $this->get_item_key( $key );
		$accessor = $this->_get_accessor( $storage_key );
		if ( is_null( $accessor ) )
			return array( null, false );

		$v = $accessor->get( $storage_key );
		$v = @unserialize( $v );

		if ( !is_array( $v ) || !isset( $v['key_version'] ) )
			return array( null, $has_old_data );

		$key_version = $this->_get_key_version( $group );
		if ( $v['key_version'] == $key_version )
			return array( $v, $has_old_data );

		if ( $v['key_version'] > $key_version ) {
			$this->_set_key_version( $v['key_version'], $group );
			return array( $v, $has_old_data );
		}

		// key version is old
		if ( !$this->_use_expired_data )
			return array( null, $has_old_data );

		// if we have expired data - update it for future use and let
		// current process recalculate it
		$expires_at = isset( $v['expires_at'] ) ? $v['expires_at'] : null;
		if ( $expires_at == null || time() > $expires_at ) {
			$v['expires_at'] = time() + 30;
			$accessor->setex( $storage_key, 60, serialize( $v ) );
			$has_old_data = true;

			return array( null, $has_old_data );
		}

		// return old version
		return array( $v, $has_old_data );
	}

	/**
	 * Replaces data
	 *
	 * @param string  $key
	 * @param mixed   $var
	 * @param integer $expire
	 * @param string  $group  Used to differentiate between groups of cache values
	 * @return boolean
	 */
	function replace( $key, &$var, $expire = 0, $group = '' ) {
		return $this->set( $key, $var, $expire, $group );
	}

	/**
	 * Deletes data
	 *
	 * @param string  $key
	 * @param string  $group
	 * @return boolean
	 */
	function delete( $key, $group = '' ) {
		$storage_key = $this->get_item_key( $key );
		$accessor = $this->_get_accessor( $storage_key );
		if ( is_null( $accessor ) )
			return false;

		if ( $this->_use_expired_data ) {
			$v = $accessor->get( $storage_key );
			$ttl = $accessor->ttl( $storage_key );
			if ( is_array( $v ) ) {
				$v['key_version'] = 0;
				$accessor->setex( $storage_key, $ttl, $v );
				return true;
			}
		}
		return $accessor->delete( $storage_key );
	}

	/**
	 * Key to delete, deletes _old and primary if exists.
	 *
	 * @param unknown $key
	 * @return bool
	 */
	function hard_delete( $key ) {
		$storage_key = $this->get_item_key( $key );
		$accessor = $this->_get_accessor( $storage_key );
		if ( is_null( $accessor ) )
			return false;

		return $accessor->delete( $storage_key );
	}

	/**
	 * Flushes all data
	 *
	 * @param string  $group Used to differentiate between groups of cache values
	 * @return boolean
	 */
	function flush( $group = '' ) {
		$this->_get_key_version( $group );   // initialize $this->_key_version
		$this->_key_version[$group]++;
		$this->_set_key_version( $this->_key_version[$group], $group );

		return true;
	}

	/**
	 * Checks if engine can function properly in this environment
	 *
	 * @return bool
	 */
	public function available() {
		return class_exists( 'Redis' );
	}

	public function get_statistics() {
		$accessor = $this->_get_accessor( '' );   // single-server mode used for stats
		if ( is_null( $accessor ) )
			return array();

		$a = $accessor->info();

		return $a;
	}

	/**
	 * Returns key version
	 *
	 * @param string  $group Used to differentiate between groups of cache values
	 * @return integer
	 */
	private function _get_key_version( $group = '' ) {
		if ( !isset( $this->_key_version[$group] ) || $this->_key_version[$group] <= 0 ) {
			$storage_key = $this->_get_key_version_key( $group );
			$accessor = $this->_get_accessor( $storage_key );
			if ( is_null( $accessor ) )
				return 0;

			$v = $accessor->get( $storage_key );
			$v = intval( $v );
			$this->_key_version[$group] = ( $v > 0 ? $v : 1 );
		}

		return $this->_key_version[$group];
	}

	/**
	 * Sets new key version
	 *
	 * @param unknown $v
	 * @param string  $group Used to differentiate between groups of cache values
	 * @return boolean
	 */
	private function _set_key_version( $v, $group = '' ) {
		$storage_key = $this->_get_key_version_key( $group );
		$accessor = $this->_get_accessor( $storage_key );
		if ( is_null( $accessor ) )
			return false;

		$accessor->set( $storage_key, $v );
		return true;
	}

	/**
	 * Used to replace as atomically as possible known value to new one
	 */
	public function set_if_maybe_equals( $key, $old_value, $new_value ) {
		$storage_key = $this->get_item_key( $key );
		$accessor = $this->_get_accessor( $storage_key );
		if ( is_null( $accessor ) )
			return false;

		$accessor->watch( $storage_key );

		$value = $accessor->get( $storage_key );
		if ( !is_array( $value ) ) {
			$accessor->unwatch();
			return false;
		}

		if ( isset( $old_value['content'] ) &&
			$value['content'] != $old_value['content'] ) {
			$accessor->unwatch();
			return false;
		}

		return $ret = $accessor->multi()
		->set( $storage_key, $new_value )
		->exec();
	}

	/**
	 * Use key as a counter and add integet value to it
	 */
	public function counter_add( $key, $value ) {
		if ( $value == 0 )
			return true;

		$storage_key = $this->get_item_key( $key );
		$accessor = $this->_get_accessor( $storage_key );
		if ( is_null( $accessor ) )
			return false;

		$r = $accessor->incrBy( $storage_key, $value );
		if ( !$r )   // it doesnt initialize counter by itself
			$this->counter_set( $key, 0 );

		return $r;
	}

	/**
	 * Use key as a counter and add integet value to it
	 */
	public function counter_set( $key, $value ) {
		$storage_key = $this->get_item_key( $key );
		$accessor = $this->_get_accessor( $storage_key );
		if ( is_null( $accessor ) )
			return false;

		return $accessor->set( $storage_key, $value );
	}

	/**
	 * Get counter's value
	 */
	public function counter_get( $key ) {
		$storage_key = $this->get_item_key( $key );
		$accessor = $this->_get_accessor( $storage_key );
		if ( is_null( $accessor ) )
			return 0;

		$v = (int)$accessor->get( $storage_key );

		return $v;
	}

	private function _get_accessor( $key ) {
		if ( count( $this->_servers ) <= 1 )
			$index = 0;
		else {
			$index = crc32( $key ) % count( $this->_servers );
		}

		if ( isset( $this->_accessors[$index] ) )
			return $this->_accessors[$index];

		if ( !isset( $this->_servers[$index] ) )
			$this->_accessors[$index] = null;
		else {
			try {
				$server = $this->_servers[$index];
				$accessor = new \Redis();

				if ( substr( $server, 0, 5 ) == 'unix:' ) {
					if ( $this->_persistent )
						$accessor->pconnect( trim( substr( $server, 5 ) ) );
					else
						$accessor->connect( trim( substr( $server, 5 ) ) );
				} else {
					list( $ip, $port ) = explode( ':', $server );

					if ( $this->_persistent )
						$accessor->pconnect( trim( $ip ), (integer) trim( $port ) );
					else
						$accessor->connect( trim( $ip ), (integer) trim( $port ) );
				}

				if ( !empty( $this->_password ) )
					$accessor->auth( $this->_password );
				$accessor->select( $this->_dbid );
			} catch ( \Exception $e ) {
				error_log( $e->getMessage() );
				$accessor = null;
			}

			$this->_accessors[$index] = $accessor;
		}

		return $this->_accessors[$index];
	}
}
