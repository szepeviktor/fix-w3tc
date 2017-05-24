<?php
namespace W3TC;

/**
 * Generic file cache
 */



/**
 * class Cache_File_Generic
 */
class Cache_File_Generic extends Cache_File {
	/**
	 * Expire
	 *
	 * @var integer
	 */
	var $_expire = 0;

	/**
	 * PHP5-style constructor
	 *
	 * @param array   $config
	 */
	function __construct( $config = array() ) {
		parent::__construct( $config );

		$this->_expire = ( isset( $config['expire'] ) ? (int) $config['expire'] : 0 );

		if ( !$this->_expire || $this->_expire > W3TC_CACHE_FILE_EXPIRE_MAX ) {
			$this->_expire = W3TC_CACHE_FILE_EXPIRE_MAX;
		}
	}

	/**
	 * Sets data
	 *
	 * @param string  $key
	 * @param string  $var
	 * @param int     $expire
	 * @param string  $group  Used to differentiate between groups of cache values
	 * @return boolean
	 */
	function set( $key, $var, $expire = 0, $group = '' ) {
		$key = $this->get_item_key( $key );
		$sub_path = $this->_get_path( $key );
		$path = $this->_cache_dir . '/' . $sub_path;

		$dir = dirname( $path );

		if ( !@is_dir( $dir ) ) {
			if ( !Util_File::mkdir_from_safe( $dir, W3TC_CACHE_DIR ) )
				return false;
		}

		$tmppath = $path . '.' . getmypid();

		$fp = @fopen( $tmppath, 'w' );
		if ( !$fp )
			return false;

		if ( $this->_locking )
			@flock( $fp, LOCK_EX );

		@fputs( $fp, $var['content'] );
		@fclose( $fp );

		if ( $this->_locking )
			@flock( $fp, LOCK_UN );

		// some hostings create files with restrictive permissions
		// not allowing apache to read it later
		@chmod( $path, 0644 );

		if ( @filesize( $tmppath ) > 0 ) {
			@unlink( $path );
			@rename( $tmppath, $path );
		}

		@unlink( $tmppath );

		$old_entry_path = $path . '_old';
		@unlink( $old_entry_path );

		if ( Util_Environment::is_apache() && isset( $var['headers'] ) ) {
			
			$rules = '';
			
			if( isset( $var['headers']['Content-Type'] ) && substr( $var['headers']['Content-Type'], 0, 8 ) == 'text/xml' ){
			
				$rules .= "<IfModule mod_mime.c>\n";
				$rules .= "    RemoveType .html_gzip\n";
				$rules .= "    AddType text/xml .html_gzip\n";
				$rules .= "    RemoveType .html\n";
				$rules .= "    AddType text/xml .html\n";
				$rules .= "</IfModule>\n";
			}
			
			if( isset($var['headers'][0]) ){
				
				$links = '';
				$i = 0;
				
				while( isset($var['headers'][$i]) ){
					
					$name  = isset($var['headers'][$i]['n']) ? $var['headers'][$i]['n'] : '';
					$value = isset($var['headers'][$i]['v']) ? $var['headers'][$i]['v'] : '';
					
					if( ($name == 'Link') && (false !== strpos($value, 'rel=preload')) ){
						$links .= "    Header add Link '".trim($value)."'\n";
					}
					
					$i++;
				}
				
				if( !empty($links) ){
					$rules .= "<IfModule mod_headers.c>\n";
					$rules .= "    Header unset Link\n";
					$rules .= $links;
					$rules .= "</IfModule>\n";
				}
			}
			
			if( !empty($rules) ){
				$uri_cache_path  = dirname($path);
				$base_cache_path = W3TC_CACHE_PAGE_ENHANCED_DIR . '/' . Util_Environment::host();
				
				if( $uri_cache_path != $base_cache_path ){
				    @file_put_contents($uri_cache_path . '/.htaccess', $rules);
				}
			}
		}

		return true;
	}

	/**
	 * Returns data
	 *
	 * @param string  $key
	 * @param string  $group Used to differentiate between groups of cache values
	 * @return array
	 */
	function get_with_old( $key, $group = '' ) {
		$has_old_data = false;
		$key = $this->get_item_key( $key );
		$path = $this->_cache_dir . '/' . $this->_get_path( $key );

		$data = $this->_read( $path );
		if ( $data != null )
			return array( $data, $has_old_data );


		$path_old = $path . '_old';
		$too_old_time = time() - 30;

		if ( $exists = file_exists( $path_old ) ) {
			$file_time = @filemtime( $path_old );
			if ( $file_time ) {
				if ( $file_time > $too_old_time ) {
					// return old data
					$has_old_data = true;
					return array( $this->_read( $path_old ), $has_old_data );

				}

				// use old enough time to cause recalculation on next call
				@touch( $path_old, 1479904835 );
			}
		}
		$has_old_data = $exists;

		return array( null, $has_old_data );
	}

	/**
	 * Reads file
	 *
	 * @param string  $path
	 * @return array
	 */
	private function _read( $path ) {
		if ( !is_readable( $path ) )
			return null;

		$fp = @fopen( $path, 'r' );
		if ( !$fp )
			return null;

		if ( $this->_locking )
			@flock( $fp, LOCK_SH );

		$var = '';

		while ( !@feof( $fp ) )
			$var .= @fread( $fp, 4096 );

		@fclose( $fp );

		if ( $this->_locking )
			@flock( $fp, LOCK_UN );

		return array(
			'404' => false,
			'headers' => array(),
			'time' => null,
			'content' => $var
		);
	}

	/**
	 * Deletes data
	 *
	 * @param string  $key
	 * @param string  $group Used to differentiate between groups of cache values
	 * @return boolean
	 */
	function delete( $key, $group = '' ) {
		$key = $this->get_item_key( $key );
		$path = $this->_cache_dir . DIRECTORY_SEPARATOR . $this->_get_path( $key );

		if ( !file_exists( $path ) )
			return true;

		$old_entry_path = $path . '_old';
		if ( ! @rename( $path, $old_entry_path ) ) {
			// if we can delete old entry - do second attempt to store in old-entry file
			if ( ! @unlink( $old_entry_path ) || ! @rename( $path, $old_entry_path ) ) {
				return @unlink( $path );
			}
		}

		@touch( $old_entry_path, 1479904835 );
		return true;
	}

	/**
	 * Key to delete, deletes _old and primary if exists.
	 *
	 * @param unknown $key
	 * @return bool
	 */
	function hard_delete( $key ) {
		$key = $this->get_item_key( $key );
		$path = $this->_cache_dir . DIRECTORY_SEPARATOR . $this->_get_path( $key );
		$old_entry_path = $path . '_old';
		@unlink( $old_entry_path );

		if ( !file_exists( $path ) )
			return true;
		@unlink( $path );
		return true;
	}

	/**
	 * Flushes all data
	 *
	 * @param string  $group Used to differentiate between groups of cache values
	 * @return boolean
	 */
	function flush( $group = '' ) {
		if ( $group == 'sitemaps' ) {
			$config = Dispatcher::config();
			$sitemap_regex = $config->get_string( 'pgcache.purge.sitemap_regex' );
			$this->_flush_based_on_regex( $sitemap_regex );
		} else {
			$c = new Cache_File_Cleaner_Generic( array(
					'cache_dir' => $this->_flush_dir,
					'exclude' => $this->_exclude,
					'clean_timelimit' => $this->_flush_timelimit
				) );

			$c->clean();
		}
	}

	/**
	 * Returns cache file path by key
	 *
	 * @param string  $key
	 * @return string
	 */
	function _get_path( $key ) {
		return $key;
	}

	function get_item_key( $key ) {
		return $key;
	}


	/**
	 * Flush cache based on regex
	 *
	 * @param string  $regex
	 */
	private function _flush_based_on_regex( $regex ) {
		if ( Util_Environment::is_wpmu() && !Util_Environment::is_wpmu_subdomain() ) {
			$domain = get_home_url();
			$parsed = parse_url( $domain );
			$host = $parsed['host'];
			$path = isset( $parsed['path'] ) ? '/' . trim( $parsed['path'], '/' ) : '';
			$flush_dir = W3TC_CACHE_PAGE_ENHANCED_DIR . '/' . $host . $path;
		} else
			$flush_dir = W3TC_CACHE_PAGE_ENHANCED_DIR . '/' . Util_Environment::host();

		$dir = @opendir( $flush_dir );
		if ( $dir ) {
			while ( ( $entry = @readdir( $dir ) ) !== false ) {
				if ( $entry == '.' || $entry == '..' ) {
					continue;
				}
				if ( preg_match( '~' . $regex . '~', basename( $entry ) ) ) {
					Util_File::rmdir( $flush_dir . DIRECTORY_SEPARATOR . $entry );
				}
			}

			@closedir( $dir );
		}
	}
}
