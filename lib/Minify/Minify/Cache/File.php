<?php

/**
 * Class Minify_Cache_File
 * @package Minify
 */

class Minify_Cache_File {

	public function __construct($path = '', $exclude = array(), $locking = false, $flushTimeLimit = 0, $flush_path = null) {
		if (! $path) {
			$path = self::tmp();
		}

		$this->_path = $path;
		$this->_exclude = $exclude;
		$this->_locking = $locking;
		$this->_flushTimeLimit = $flushTimeLimit;

		$this->_flush_path = (is_null($flush_path) ? $path : $flush_path);

		if (!file_exists($this->_path .'/index.html')) {
			if (!is_dir($this->_path))
				\W3TC\Util_File::mkdir_from_safe($this->_path, W3TC_CACHE_DIR);
			@file_put_contents($this->_path .'/index.html', '');
		}
	}

	/**
	 * Write data to cache.
	 *
	 * @param string $id cache id (e.g. a filename)
	 *
	 * @param string $data
	 *
	 * @return bool success
	 */
	public function store($id, $data)
	{
		$path = $this->_path . '/' . $id;
		$flag = $this->_locking ? LOCK_EX : null;

		if (is_file($path)) {
			@unlink($path);
		}

		if (!@file_put_contents($path, $data['content'], $flag)) {
			// retry with make dir
			\W3TC\Util_File::mkdir_from_safe(dirname($path), W3TC_CACHE_DIR);

			if (!@file_put_contents($path, $data, $flag))
				return false;
		}

		@unlink($path . '_old');
		@unlink($path . '_meta_old');

		$content = $data['content'];
		unset($data['content']);
		if (count($data) > 0)
			@file_put_contents($path . '_meta', serialize($data), $flag);

		// write control
		$read = $this->fetch($id);
		if (!isset($read['content']) || $content != $read['content']) {
			@unlink($path);

			return false;
		}
		return true;
	}

	/**
	 * Get the size of a cache entry
	 *
	 * @param string $id cache id (e.g. a filename)
	 *
	 * @return int size in bytes
	 */
	public function getSize($id)
	{
		return filesize($this->_path . '/' . $id);
	}

	/**
	 * Does a valid cache entry exist?
	 *
	 * @param string $id cache id (e.g. a filename)
	 *
	 * @param int $srcMtime mtime of the original source file(s)
	 *
	 * @return bool exists
	 */
	public function isValid($id, $srcMtime)
	{
		$file = $this->_path . '/' . $id;
		return (is_file($file) && (filemtime($file) >= $srcMtime));
	}

	/**
	 * Send the cached content to output
	 *
	 * @param string $id cache id (e.g. a filename)
	 */
	public function display($id)
	{
		$path = $this->_path . '/' . $id;

		$fp = @fopen($path, 'rb');

		if ($fp) {
			if ($this->_locking)
				@flock($fp, LOCK_SH);
			@fpassthru($fp);
			if ($this->_locking)
				@flock($fp, LOCK_UN);
			@fclose($fp);

			return true;
		}

		return false;
	}

	/**
	 * Fetch the cached content
	 *
	 * @param string $id cache id (e.g. a filename)
	 *
	 * @return string
	 */
	public function fetch($id)
	{
		$path = $this->_path . '/' . $id;

		$data = @file_get_contents($path . '_meta');
		if ($data) {
			$data = @unserialize($data);
			if (!is_array($data))
				$data = array();
		}

		if (is_readable($path)) {
			if ($this->_locking) {
				$fp = @fopen($path, 'rb');

				if ($fp) {
					@flock($fp, LOCK_SH);

					$ret = @stream_get_contents($fp);

					@flock($fp, LOCK_UN);
					@fclose($fp);

					return $ret;
				}
			} else {
				$data['content'] = @file_get_contents($path);
				return $data;
			}
		} else {
			$path_old = $path . '_old';
			$too_old_time = time() - 30;

			$file_time = @filemtime($path_old);
			if ($file_time) {
				if ($file_time > $too_old_time) {
					// return old data
					$data['content'] = @file_get_contents($path_old);
					return $data;
				}

				@touch($path_old);
			}
		}

		return false;
	}

		/**
	 * Returns the OS-specific directory for temporary files
	 *
	 * @author Paul M. Jones <pmjones@solarphp.com>
	 * @license http://opensource.org/licenses/bsd-license.php BSD
	 * @link http://solarphp.com/trac/core/browser/trunk/Solar/Dir.php
	 *
	 * @return string
	 */
	protected static function _tmp()
	{
		// non-Windows system?
		if (strtolower(substr(PHP_OS, 0, 3)) != 'win') {
			$tmp = empty($_ENV['TMPDIR']) ? getenv('TMPDIR') : $_ENV['TMPDIR'];
			if ($tmp) {
				return $tmp;
			} else {
				return '/tmp';
			}
		}
		// Windows 'TEMP'
		$tmp = empty($_ENV['TEMP']) ? getenv('TEMP') : $_ENV['TEMP'];
		if ($tmp) {
			return $tmp;
		}
		// Windows 'TMP'
		$tmp = empty($_ENV['TMP']) ? getenv('TMP') : $_ENV['TMP'];
		if ($tmp) {
			return $tmp;
		}
		// Windows 'windir'
		$tmp = empty($_ENV['windir']) ? getenv('windir') : $_ENV['windir'];
		if ($tmp) {
			return $tmp;
		}
		// final fallback for Windows
		return getenv('SystemRoot') . '\\temp';
	}

	/**
	 * Flush cache
	 *
	 * @return bool
	 */
	public function flush() {
		@set_time_limit($this->_flushTimeLimit);

		return \W3TC\Util_File::emptydir($this->_flush_path, $this->_exclude);
	}

	/**
	 * Fetch the cache path used
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->_path;
	}

	private $_path = null;
	private $_exclude = null;
	private $_locking = null;
	private $_flushTimeLimit = null;

	/**
	 * Returns size statistics about cache files
	 */
	public function get_stats_size($timeout_time)
	{
		$dir = @opendir($this->_path);

		$stats = array(
			'css' => array(
				'items' => 0,
				'original_length' => 0,
				'output_length' => 0
			),
			'js' => array(
				'items' => 0,
				'original_length' => 0,
				'output_length' => 0
			),
			'timeout_occurred' => false
		);

		if (!$dir)
			return $stats;

		$n = 0;
		while (!$stats['timeout_occurred'] &&
				($entry = @readdir($dir)) !== false) {
			$n++;
			if ($n % 1000 == 0)
				$stats['timeout_occurred'] |= (time() > $timeout_time);

			if (substr($entry, -8) == '.js_meta' || substr($entry, -13) == '.js_gzip_meta')
				$type = 'js';
			else if (substr($entry, -9) == '.css_meta' || substr($entry, -14) == '.css_gzip_meta')
				$type = 'css';
			else
				continue;

			$full_path = $this->_path . DIRECTORY_SEPARATOR . $entry;
			$data = @file_get_contents($full_path);
			if (!$data)
				continue;

			$data = @unserialize($data);
			if (!is_array($data) || !isset($data['originalLength']))
				continue;

			$stats[$type]['items']++;
			$stats[$type]['original_length'] += (int)$data['originalLength'];
			$stats[$type]['output_length'] +=
				@filesize(substr($full_path, 0, strlen($full_path) - 5));
		}

		@closedir($dir);
		return $stats;
	}
}
