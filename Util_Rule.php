<?php
namespace W3TC;

class Util_Rule {
	/**
	 * Check if WP permalink directives exists
	 *
	 * @return boolean
	 */
	static public function is_permalink_rules() {
		if ( ( Util_Environment::is_apache() || Util_Environment::is_litespeed() ) && !Util_Environment::is_wpmu() ) {
			$path = Util_Rule::get_pgcache_rules_core_path();

			return ( $data = @file_get_contents( $path ) ) &&
				strstr( $data, W3TC_MARKER_BEGIN_WORDPRESS ) !== false;
		}

		return true;
	}

	/**
	 * Removes empty elements
	 */
	static public function array_trim( &$a ) {
        $a = array_map( 'trim', $a );
        $a = array_filter( $a, function( $val ) { return $val != ""; } );
	}

	/**
	 * Returns nginx rules path
	 *
	 * @return string
	 */
	static public function get_nginx_rules_path() {
		$config = Dispatcher::config();

		$path = $config->get_string( 'config.path' );

		if ( !$path ) {
			$path = Util_Environment::site_path() . 'nginx.conf';
		}

		return $path;
	}

	/**
	 * Returns path of pagecache core rules file
	 *
	 * @return string
	 */
	static public function get_pgcache_rules_core_path() {
		switch ( true ) {
		case Util_Environment::is_apache():
		case Util_Environment::is_litespeed():
			return Util_Environment::site_path() . '.htaccess';

		case Util_Environment::is_nginx():
			return Util_Rule::get_nginx_rules_path();
		}

		return false;
	}

	/**
	 * Returns path of browsercache cache rules file
	 *
	 * @return string
	 */
	static public function get_browsercache_rules_cache_path() {
		return Util_Rule::get_pgcache_rules_core_path();
	}

	/**
	 * Returns path of browsercache no404wp rules file
	 *
	 * @return string
	 */
	static public function get_browsercache_rules_no404wp_path() {
		return Util_Rule::get_pgcache_rules_core_path();
	}

	/**
	 * Returns path of minify rules file
	 *
	 * @return string
	 */
	static public function get_minify_rules_core_path() {
		switch ( true ) {
		case Util_Environment::is_apache():
		case Util_Environment::is_litespeed():
			return W3TC_CACHE_MINIFY_DIR . DIRECTORY_SEPARATOR . '.htaccess';

		case Util_Environment::is_nginx():
			return Util_Rule::get_nginx_rules_path();
		}

		return false;
	}

	/**
	 * Returns path of minify rules file
	 *
	 * @return string
	 */
	static public function get_minify_rules_cache_path() {
		switch ( true ) {
		case Util_Environment::is_apache():
		case Util_Environment::is_litespeed():
			return W3TC_CACHE_MINIFY_DIR . DIRECTORY_SEPARATOR . '.htaccess';

		case Util_Environment::is_nginx():
			return Util_Rule::get_nginx_rules_path();
		}

		return false;
	}

	/**
	 * Returns path of CDN rules file
	 *
	 * @return string
	 */
	static public function get_cdn_rules_path() {
		switch ( true ) {
		case Util_Environment::is_apache():
		case Util_Environment::is_litespeed():
			return '.htaccess';

		case Util_Environment::is_nginx():
			return 'nginx.conf';
		}

		return false;
	}

	static public function get_new_relic_rules_core_path() {
		return Util_Rule::get_pgcache_rules_core_path();
	}

	/**
	 * Returns true if we can modify rules
	 *
	 * @param string  $path
	 * @return boolean
	 */
	static public function can_modify_rules( $path ) {
		if ( Util_Environment::is_wpmu() ) {
			if ( Util_Environment::is_apache() || Util_Environment::is_litespeed() || Util_Environment::is_nginx() ) {
				switch ( $path ) {
				case Util_Rule::get_pgcache_rules_cache_path():
				case Util_Rule::get_minify_rules_core_path():
				case Util_Rule::get_minify_rules_cache_path():
					return true;
				}
			}

			return false;
		}

		return true;
	}

	/**
	 * Trim rules
	 *
	 * @param string  $rules
	 * @return string
	 */
	static public function trim_rules( $rules ) {
		$rules = trim( $rules );

		if ( $rules != '' ) {
			$rules .= "\n";
		}

		return $rules;
	}

	/**
	 * Cleanup rewrite rules
	 *
	 * @param string  $rules
	 * @return string
	 */
	static public function clean_rules( $rules ) {
		$rules = preg_replace( '~[\r\n]+~', "\n", $rules );
		$rules = preg_replace( '~^\s+~m', '', $rules );
		$rules = Util_Rule::trim_rules( $rules );

		return $rules;
	}

	/**
	 * Erases text from start to end
	 *
	 * @param string  $rules
	 * @param string  $start
	 * @param string  $end
	 * @return string
	 */
	static public function erase_rules( $rules, $start, $end ) {
		$r = '~' . Util_Environment::preg_quote( $start ) . "\n.*?" . Util_Environment::preg_quote( $end ) . "\n*~s";

		$rules = preg_replace( $r, '', $rules );
		$rules = Util_Rule::trim_rules( $rules );

		return $rules;
	}

	/**
	 * Check if rules exist
	 *
	 * @param string  $rules
	 * @param string  $start
	 * @param string  $end
	 * @return int
	 */
	static public function has_rules( $rules, $start, $end ) {
		return preg_match( '~' . Util_Environment::preg_quote( $start ) . "\n.*?" . Util_Environment::preg_quote( $end ) . "\n*~s", $rules );
	}

	/**
	 *
	 *
	 * @param Util_Environment_Exceptions $exs
	 * @param string  $path
	 * @param string  $rules
	 * @param string  $start
	 * @param string  $end
	 * @param array   $order
	 */
	static public function add_rules( $exs, $path, $rules, $start, $end, $order ) {
		if ( empty( $path ) )
			return;

		$data = @file_get_contents( $path );

		if ( $data === false )
			$data = '';

		$rules_missing = !empty( $rules ) && ( strstr( Util_Rule::clean_rules( $data ), Util_Rule::clean_rules( $rules ) ) === false );
		if ( !$rules_missing )
			return;

		$replace_start = strpos( $data, $start );
		$replace_end = strpos( $data, $end );

		if ( $replace_start !== false && $replace_end !== false && $replace_start < $replace_end ) {
			$replace_length = $replace_end - $replace_start + strlen( $end ) + 1;
		} else {
			$replace_start = false;
			$replace_length = 0;

			$search = $order;

			foreach ( $search as $string => $length ) {
				$replace_start = strpos( $data, $string );

				if ( $replace_start !== false ) {
					$replace_start += $length;
					break;
				}
			}
		}

		if ( $replace_start !== false ) {
			$data = Util_Rule::trim_rules( substr_replace( $data, $rules, $replace_start, $replace_length ) );
		} else {
			$data = Util_Rule::trim_rules( $data . $rules );
		}

		if ( strpos( $path, W3TC_CACHE_DIR ) === false || Util_Environment::is_nginx() ) {
			try {
				Util_WpFile::write_to_file( $path, $data );
			} catch ( Util_WpFile_FilesystemOperationException $ex ) {
				if ( $replace_start !== false )
					$exs->push( new Util_WpFile_FilesystemModifyException(
							$ex->getMessage(), $ex->credentials_form(),
							sprintf( __( 'Edit file <strong>%s
                            </strong> and replace all lines between and including <strong>%s</strong> and
                            <strong>%s</strong> markers with:', 'w3-total-caceh' ), $path, $start, $end ), $path, $rules ) );
				else
					$exs->push( new Util_WpFile_FilesystemModifyException(
							$ex->getMessage(), $ex->credentials_form(),
							sprintf( __( 'Edit file <strong>%s</strong> and add the following rules
                                    above the WordPress directives:', 'w3-total-cache' ),
								$path ), $path, $rules ) );
				return;
			}
		} else {
			if ( !@file_exists( dirname( $path ) ) ) {
				Util_File::mkdir_from( dirname( $path ), W3TC_CACHE_DIR );
			}

			if ( !@file_put_contents( $path, $data ) ) {
				try {
					Util_WpFile::delete_folder( dirname( $path ), '',
						$_SERVER['REQUEST_URI'] );
				} catch ( Util_WpFile_FilesystemOperationException $ex ) {
					$exs->push( $ex );
					return;
				}
			}
		}

		Util_Rule::after_rules_modified();
	}



	/**
	 * Called when rules are modified, sets notification
	 */
	static public function after_rules_modified() {
		if ( Util_Environment::is_nginx() ) {
			$state = Dispatcher::config_state_master();
			$state->set( 'common.show_note.nginx_restart_required', true );
			$state->save();
		}
	}



	/**
	 * Remove rules
	 */
	static public function remove_rules( $exs, $path, $start, $end ) {
		if ( !file_exists( $path ) )
			return;

		$data = @file_get_contents( $path );
		if ( $data === false )
			return;
		if ( strstr( $data, $start ) === false )
			return;

		$data = Util_Rule::erase_rules( $data, $start,
			$end );

		try {
			Util_WpFile::write_to_file( $path, $data );
		} catch ( Util_WpFile_FilesystemOperationException $ex ) {
			$exs->push( new Util_WpFile_FilesystemModifyException(
					$ex->getMessage(), $ex->credentials_form(),
					sprintf( __( 'Edit file <strong>%s</strong> and remove all lines between and including <strong>%s</strong>
                and <strong>%s</strong> markers.', 'w3-total-cache' ), $path, $start, $end ), $path ) );
		}
	}

	/**
	 * Returns path of pgcache cache rules file
	 * Moved to separate file to not load rule.php for each disk enhanced request
	 *
	 * @return string
	 */
	static public function get_pgcache_rules_cache_path() {
		switch ( true ) {
		case Util_Environment::is_apache():
		case Util_Environment::is_litespeed():
			if ( Util_Environment::is_wpmu() ) {
				$url = get_home_url();
				$match = null;
				if ( preg_match( '~http(s)?://(.+?)(/)?$~', $url, $match ) ) {
					$home_path = $match[2];

					return W3TC_CACHE_PAGE_ENHANCED_DIR . DIRECTORY_SEPARATOR .
						$home_path . DIRECTORY_SEPARATOR . '.htaccess';
				}
			}

			return W3TC_CACHE_PAGE_ENHANCED_DIR . DIRECTORY_SEPARATOR . '.htaccess';

		case Util_Environment::is_nginx():
			return Util_Rule::get_nginx_rules_path();
		}

		return false;
	}

	/**
	 * Returns true if we can check rules
	 *
	 * @return bool
	 */
	static public function can_check_rules() {
		return Util_Environment::is_apache() ||
			Util_Environment::is_litespeed() ||
			Util_Environment::is_nginx() ||
			Util_Environment::is_iis();
	}

	/**
	 * Support for GoDaddy servers configuration which uses
	 * SUBDOMAIN_DOCUMENT_ROOT variable
	 */
	static public function apache_docroot_variable() {
		if ( isset( $_SERVER['SUBDOMAIN_DOCUMENT_ROOT'] ) &&
			$_SERVER['SUBDOMAIN_DOCUMENT_ROOT'] != $_SERVER['DOCUMENT_ROOT'] )
			return '%{ENV:SUBDOMAIN_DOCUMENT_ROOT}';
		elseif ( isset( $_SERVER['PHP_DOCUMENT_ROOT'] ) &&
			$_SERVER['PHP_DOCUMENT_ROOT'] != $_SERVER['DOCUMENT_ROOT'] )
			return '%{ENV:PHP_DOCUMENT_ROOT}';
		else
			return '%{DOCUMENT_ROOT}';
	}
}
