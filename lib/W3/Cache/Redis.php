<?php
/**
 * PECL Memcached class
 */
if (!defined('W3TC')) {
    die();
}

w3_require_once(W3TC_LIB_W3_DIR.'/Cache/Base.php');

/**
 * Class W3_Cache_Redis
 */
class W3_Cache_Redis extends W3_Cache_Base
{
    /**
     * Redis object
     *
     * @var Redis
     */
    private $_redis = null;

    /*
     * Used for faster flushing
     *
     * @var integer $_key_version
     */
    private $_key_version = array();

    /**
     * constructor
     *
     * @param array $config
     */
    function __construct($config)
    {
        parent::__construct($config);

        $this->_redis = new Redis();
        
        if (!empty($config['server']) && !empty($config['db'])) {
            $persistent = isset($config['persistant']) ? true : false;
            $server   = explode(':', $config['server']);
            $location = $server[0];
            $port     = (isset($server[1]) ? (int) $server[1] : 6379 );
            if ($persistent) {
                if (filter_var($location, FILTER_VALIDATE_IP)) {
                    $this->_redis->pconnect($location, $port);
                } elseif (file_exists($location)) { // Probably a unix socket
                    $this->_redis->pconnect($location);
                }
            } else {
                if (filter_var($location, FILTER_VALIDATE_IP)) {
                    $this->_redis->connect($location, $port);
                } elseif (file_exists($location)) { // Probably a unix socket
                    $this->_redis->connect($location);
                }
            }

            try {
                $this->_redis->select($config['db']);
                $this->_redis->ping();
            } catch (Exception $ex) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Adds data
     *
     * @param string $key
     * @param mixed $var
     * @param integer $expire
     * @param string $group Used to differentiate between groups of cache values
     * @return boolean
     */
    function add($key, &$var, $expire = 0, $group = '')
    {
        return $this->set($key, $var, $expire, $group);
    }

    /**
     * Sets data
     *
     * @param string $key
     * @param mixed $var
     * @param integer $expire
     * @param string $group Used to differentiate between groups of cache values
     * @return boolean
     */
    function set($key, $var, $expire = 0, $group = '')
    {
        $key = $this->get_item_key($key);

        $var['key_version'] = $this->_get_key_version($group);


        try {
            if ($expire === 0) {
                return $this->_redis->set($key.'_'.$this->_blog_id,
                        serialize($var));
            }
            return $this->_redis->set($key.'_'.$this->_blog_id, serialize($var),$expire);
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Returns data
     *
     * @param string $key
     * @param string $group Used to differentiate between groups of cache values
     * @return mixed
     */
    function get_with_old($key, $group = '')
    {
        try {
            $has_old_data = false;

            $key = $this->get_item_key($key);

            $v = @unserialize(@$this->_redis->get($key.'_'.$this->_blog_id));
            if (!is_array($v) || !isset($v['key_version']))
                    return array(null, $has_old_data);

            $key_version = $this->_get_key_version($group);
            if ($v['key_version'] == $key_version)
                    return array($v, $has_old_data);

            if ($v['key_version'] > $key_version) {
                $this->_set_key_version($v['key_version'], $group);
                return array($v, $has_old_data);
            }

            // key version is old
            if (!$this->_use_expired_data) return array(null, $has_old_data);

            // if we have expired data - update it for future use and let
            // current process recalculate it
            $expires_at = isset($v['expires_at']) ? $v['expires_at'] : null;
            if ($expires_at == null || time() > $expires_at) {
                $v['expires_at'] = time() + 30;
                @$this->_redis->set($key.'_'.$this->_blog_id, serialize($v));
                $has_old_data    = true;

                return array(null, $has_old_data);
            }

            // return old version
            return array($v, $has_old_data);
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Replaces data
     *
     * @param string $key
     * @param mixed $var
     * @param integer $expire
     * @param string $group Used to differentiate between groups of cache values
     * @return boolean
     */
    function replace($key, &$var, $expire = 0, $group = '')
    {
        return $this->set($key, $var, $expire, $group);
    }

    /**
     * Deletes data
     *
     * @param string $key
     * @param string $group
     * @return boolean
     */
    function delete($key, $group = '')
    {
        try {
            $key = $this->get_item_key($key);

            if ($this->_use_expired_data) {
                $v = @unserialize(@$this->_redis->get($key.'_'.$this->_blog_id));
                if (is_array($v)) {
                    $v['key_version'] = 0;
                    @$this->_redis->set($key.'_'.$this->_blog_id, serialize($v));
                    return true;
                }
            }
            return @$this->_redis->delete($key.'_'.$this->_blog_id, 0);
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Key to delete, deletes .old and primary if exists.
     * @param $key
     * @return bool
     */
    function hard_delete($key)
    {
        try {
            $key = $this->get_item_key($key);
            return @$this->_redis->delete($key.'_'.$this->_blog_id, 0);
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Flushes all data
     *
     * @param string $group Used to differentiate between groups of cache values
     * @return boolean
     */
    function flush($group = '')
    {
        $this->_get_key_version($group);   // initialize $this->_key_version
        $this->_key_version[$group] ++;
        $this->_set_key_version($this->_key_version[$group], $group);
        return true;
    }

    /**
     * Checks if engine can function properly in this environment
     * @return bool
     */
    public function available()
    {
        return class_exists('Redis');
    }

    /**
     * Returns key version
     *
     * @param string $group Used to differentiate between groups of cache values
     * @return integer
     */
    private function _get_key_version($group = '')
    {
        try {
            if (!isset($this->_key_version[$group]) || $this->_key_version[$group]
                <= 0) {
                $v = @$this->_redis->get($this->_get_key_version_key($group));
                $v = intval($v);

                $this->_key_version[$group] = ($v > 0 ? $v : 1);
            }

            return $this->_key_version[$group];
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Sets new key version
     *
     * @param $v
     * @param string $group Used to differentiate between groups of cache values
     * @return boolean
     */
    private function _set_key_version($v, $group = '')
    {
        try {
            if (is_array($v)) {
                $v = serialize($v);
            }
            @$this->_redis->set($this->_get_key_version_key($group), $v);
        } catch (Exception $ex) {
            return false;
        }
    }
}