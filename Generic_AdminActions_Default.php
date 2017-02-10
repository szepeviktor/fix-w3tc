<?php
namespace W3TC;



define( 'W3TC_PLUGIN_TOTALCACHE_REGEXP_COOKIEDOMAIN', '~define\s*\(\s*[\'"]COOKIE_DOMAIN[\'"]\s*,.*?\)~is' );

class Generic_AdminActions_Default {

	private $_config = null;
	private $_config_master = null;

	/**
	 * Current page
	 *
	 * @var null|string
	 */
	private $_page = null;

	function __construct() {
		$this->_config = Dispatcher::config();
		$this->_config_master = Dispatcher::config_master();

		$this->_page = Util_Admin::get_current_page();
	}

	/**
	 * Start previewing
	 */
	function w3tc_default_previewing() {
		Util_Environment::set_preview( true );
		Util_Environment::redirect( get_home_url() );
	}

	/**
	 * Stop previewing the site
	 */
	function w3tc_default_stop_previewing() {
		Util_Environment::set_preview( false );
		Util_Admin::redirect( array(), true );
	}

	/**
	 * Hide note action
	 *
	 * @return void
	 */
	function w3tc_default_save_licence_key() {
		$license = Util_Request::get_string( 'license_key' );
		try {
			$old_config = new Config();

			$this->_config->set( 'plugin.license_key', $license );
			$this->_config->save();

			Dispatcher::component( 'Licensing_Plugin_Admin' )->possible_state_change(
				$this->_config, $old_config );
		} catch ( \Exception $ex ) {
			echo json_encode( array( 'result' => 'failed' ) );
			exit();
		}
		echo json_encode( array( 'result' => 'success' ) );
		exit();
	}

	/**
	 * Hide note action
	 *
	 * @return void
	 */
	function w3tc_default_hide_note() {
		$note = Util_Request::get_string( 'note' );
		$setting = sprintf( 'notes.%s', $note );
		$this->_config->set( $setting, false );
		$this->_config->save();

		do_action( "w3tc_hide_button-{$note}" );
		Util_Admin::redirect( array(), true );
	}

	function w3tc_default_config_state() {
		$key = Util_Request::get_string( 'key' );
		$value = Util_Request::get_string( 'value' );

		$config_state = Dispatcher::config_state_master();
		$config_state->set( $key, $value );
		$config_state->save();
		Util_Admin::redirect( array(), true );
	}

	function w3tc_default_config_state_master() {
		$key = Util_Request::get_string( 'key' );
		$value = Util_Request::get_string( 'value' );

		$config_state = Dispatcher::config_state_master();
		$config_state->set( $key, $value );
		$config_state->save();

		Util_Admin::redirect( array(), true );
	}

	function w3tc_default_config_state_note() {
		$key = Util_Request::get_string( 'key' );
		$value = Util_Request::get_string( 'value' );

		$s = Dispatcher::config_state_note();
		$s->set( $key, $value );

		Util_Admin::redirect( array(), true );
	}

	/**
	 * Hide note custom action
	 */
	function w3tc_default_hide_note_custom() {
		$note = Util_Request::get_string( 'note' );
		do_action( "w3tc_hide_button_custom-{$note}" );
		Util_Admin::redirect( array(), true );
	}

	/**
	 *
	 */
	function w3tc_default_remove_add_in() {

		$module = Util_Request::get_string( 'w3tc_default_remove_add_in' );

		// in the case of missing permissions to delete
		// environment will use that to try to override addin via ftp
		set_transient( 'w3tc_remove_add_in_' . $module, 'yes', 600 );

		switch ( $module ) {
		case 'pgcache':
			Util_WpFile::delete_file( W3TC_ADDIN_FILE_ADVANCED_CACHE );
			$src = W3TC_INSTALL_FILE_ADVANCED_CACHE;
			$dst = W3TC_ADDIN_FILE_ADVANCED_CACHE;
			try {
				Util_WpFile::copy_file( $src, $dst );
			} catch ( Util_WpFile_FilesystemOperationException $ex ) {}
			break;
		case 'dbcache':
			Util_WpFile::delete_file( W3TC_ADDIN_FILE_DB );
			break;
		case 'objectcache':
			Util_WpFile::delete_file( W3TC_ADDIN_FILE_OBJECT_CACHE );
			break;
		}
		Util_Admin::redirect( array(
				'w3tc_note' => 'add_in_removed'
			), true );
	}

	/**
	 * Options save action
	 *
	 * @return void
	 */
	function w3tc_save_options() {
		$redirect_data = $this->_w3tc_save_options_process();
		Util_Admin::redirect_with_custom_messages2( $redirect_data );
	}

	/**
	 * save&flush all action
	 *
	 * @return void
	 */
	public function w3tc_default_save_and_flush() {
		$redirect_data = $this->_w3tc_save_options_process();

		$f = Dispatcher::component( 'CacheFlush' );
		$f->flush_all();

		$state_note = Dispatcher::config_state_note();
		$state_note->set( 'common.show_note.flush_statics_needed', false );
		$state_note->set( 'common.show_note.flush_posts_needed', false );
		$state_note->set( 'common.show_note.plugins_updated', false );
		$state_note->set( 'minify.show_note.need_flush', false );
		$state_note->set( 'objectcache.show_note.flush_needed', false );

		Util_Admin::redirect_with_custom_messages2( $redirect_data );
	}

	private function _w3tc_save_options_process() {
		$data = array(
			'old_config' => $this->_config,
			'response_query_string' => array(),
			'response_actions' => array(),
			'response_errors' => array(),
			'response_notes' => array( 'config_save' )
		);

		// if we are on extension settings page - stay on the same page
		if ( Util_Request::get_string( 'page' ) == 'w3tc_extensions' ) {
			$data['response_query_string']['page'] =
				Util_Request::get_string( 'page' );
			$data['response_query_string']['extension'] =
				Util_Request::get_string( 'extension' );
			$data['response_query_string']['action'] =
				Util_Request::get_string( 'action' );
		}


		$capability = apply_filters( 'w3tc_capability_config_save',
			'manage_options' );
		if ( !current_user_can( $capability ) )
			wp_die( __( 'You do not have the rights to perform this action.',
					'w3-total-cache' ) );

		/**
		 * Read config
		 * We should use new instance of WP_Config object here
		 */
		$config = new Config();
		$this->read_request( $config );

		if ( $this->_page == 'w3tc_dashboard' ) {
			if ( Util_Request::get_boolean( 'maxcdn' ) ) {
				$config->set( 'cdn.enabled', true );
				$config->set( 'cdn.engine', 'maxcdn' );
			}
		}

		/**
		 * General tab
		 */
		if ( $this->_page == 'w3tc_general' ) {
			$file_nfs = Util_Request::get_boolean( 'file_nfs' );
			$file_locking = Util_Request::get_boolean( 'file_locking' );

			$config->set( 'pgcache.file.nfs', $file_nfs );
			$config->set( 'minify.file.nfs', $file_nfs );

			$config->set( 'dbcache.file.locking', $file_locking );
			$config->set( 'objectcache.file.locking', $file_locking );
			$config->set( 'pgcache.file.locking', $file_locking );
			$config->set( 'minify.file.locking', $file_locking );

			if ( is_network_admin() ) {
				if ( ( $this->_config->get_boolean( 'common.force_master' ) !==
						$config->get_boolean( 'common.force_master' ) ) ) {
					// blogmap is wrong so empty it
					@unlink( W3TC_CACHE_BLOGMAP_FILENAME );
					$blogmap_dir = dirname( W3TC_CACHE_BLOGMAP_FILENAME ) . '/' .
						basename( W3TC_CACHE_BLOGMAP_FILENAME, '.php' ) . '/';
					if ( @is_dir( $blogmap_dir ) )
						Util_File::rmdir( $blogmap_dir );
				}
			}

			/**
			 * Check permalinks for page cache
			 */
			if ( $config->get_boolean( 'pgcache.enabled' ) && $config->get_string( 'pgcache.engine' ) == 'file_generic'
				&& !get_option( 'permalink_structure' ) ) {
				$config->set( 'pgcache.enabled', false );
				$data['response_errors'][] = 'fancy_permalinks_disabled_pgcache';
			}

			if ( !Util_Environment::is_w3tc_pro( $this->_config ) )
				delete_transient( 'w3tc_license_status' );
		}

		/**
		 * Minify tab
		 */
		if ( $this->_page == 'w3tc_minify' && !$this->_config->get_boolean( 'minify.auto' ) ) {
			$js_groups = array();
			$css_groups = array();

			$js_files = Util_Request::get_array( 'js_files' );
			$css_files = Util_Request::get_array( 'css_files' );

			foreach ( $js_files as $theme => $templates ) {
				foreach ( $templates as $template => $locations ) {
					foreach ( (array) $locations as $location => $types ) {
						foreach ( (array) $types as $files ) {
							foreach ( (array) $files as $file ) {
								if ( !empty( $file ) ) {
									$js_groups[$theme][$template][$location]['files'][] = Util_Environment::normalize_file_minify( $file );
								}
							}
						}
					}
				}
			}

			foreach ( $css_files as $theme => $templates ) {
				foreach ( $templates as $template => $locations ) {
					foreach ( (array) $locations as $location => $files ) {
						foreach ( (array) $files as $file ) {
							if ( !empty( $file ) ) {
								$css_groups[$theme][$template][$location]['files'][] = Util_Environment::normalize_file_minify( $file );
							}
						}
					}
				}
			}

			$config->set( 'minify.js.groups', $js_groups );
			$config->set( 'minify.css.groups', $css_groups );

			$js_theme = Util_Request::get_string( 'js_theme' );
			$css_theme = Util_Request::get_string( 'css_theme' );

			$data['response_query_string']['js_theme'] = $js_theme;
			$data['response_query_string']['css_theme'] = $css_theme;
		}

		/**
		 * Browser Cache tab
		 */
		if ( $this->_page == 'w3tc_browsercache' ) {
			if ( $config->get_boolean( 'browsercache.enabled' ) && $config->get_boolean( 'browsercache.no404wp' ) && !get_option( 'permalink_structure' ) ) {
				$config->set( 'browsercache.no404wp', false );
				$data['response_errors'][] = 'fancy_permalinks_disabled_browsercache';
			}

			// todo: move to cdn module
			if ( in_array( $engine = $this->_config->get_string( 'cdn.engine' ), array( 'netdna', 'maxcdn' ) ) ) {
				require_once W3TC_LIB_NETDNA_DIR . '/NetDNA.php';
				$keys = explode( '+', $this->_config->get_string( 'cdn.'.$engine.'.authorization_key' ) );
				if ( sizeof( $keys ) == 3 ) {
					list( $alias, $consumerkey, $consumersecret ) =  $keys;
					try {
						$api = new \NetDNA( $alias, $consumerkey, $consumersecret );
						$disable_cooker_header = $config->get_boolean( 'browsercache.other.nocookies' ) ||
							$config->get_boolean( 'browsercache.cssjs.nocookies' );
						$api->update_pull_zone( $this->_config->get_string( 'cdn.' . $engine .'.zone_id' ), array( 'ignore_setcookie_header' => $disable_cooker_header ) );
					} catch ( \Exception $ex ) {}
				}
			}
		}

		/**
		 * Mobile tab
		 */
		if ( $this->_page == 'w3tc_mobile' ) {
			$groups = Util_Request::get_array( 'mobile_groups' );

			$mobile_groups = array();
			$cached_mobile_groups = array();

			foreach ( $groups as $group => $group_config ) {
				$group = strtolower( $group );
				$group = preg_replace( '~[^0-9a-z_]+~', '_', $group );
				$group = trim( $group, '_' );

				if ( $group ) {
					$theme = ( isset( $group_config['theme'] ) ? trim( $group_config['theme'] ) : 'default' );
					$enabled = ( isset( $group_config['enabled'] ) ? (boolean) $group_config['enabled'] : true );
					$redirect = ( isset( $group_config['redirect'] ) ? trim( $group_config['redirect'] ) : '' );
					$agents = ( isset( $group_config['agents'] ) ? explode( "\r\n", trim( $group_config['agents'] ) ) : array() );

					$mobile_groups[$group] = array(
						'theme' => $theme,
						'enabled' => $enabled,
						'redirect' => $redirect,
						'agents' => $agents
					);

					$cached_mobile_groups[$group] = $agents;
				}
			}

			/**
			 * Allow plugins modify WPSC mobile groups
			 */
			$cached_mobile_groups = apply_filters( 'cached_mobile_groups', $cached_mobile_groups );

			/**
			 * Merge existent and delete removed groups
			 */
			foreach ( $mobile_groups as $group => $group_config ) {
				if ( isset( $cached_mobile_groups[$group] ) ) {
					$mobile_groups[$group]['agents'] = (array) $cached_mobile_groups[$group];
				} else {
					unset( $mobile_groups[$group] );
				}
			}

			/**
			 * Add new groups
			 */
			foreach ( $cached_mobile_groups as $group => $agents ) {
				if ( !isset( $mobile_groups[$group] ) ) {
					$mobile_groups[$group] = array(
						'theme' => '',
						'enabled' => true,
						'redirect' => '',
						'agents' => $agents
					);
				}
			}

			/**
			 * Allow plugins modify W3TC mobile groups
			 */
			$mobile_groups = apply_filters( 'w3tc_mobile_groups', $mobile_groups );

			/**
			 * Sanitize mobile groups
			 */
			foreach ( $mobile_groups as $group => $group_config ) {
				$mobile_groups[$group] = array_merge( array(
						'theme' => '',
						'enabled' => true,
						'redirect' => '',
						'agents' => array()
					), $group_config );

				$mobile_groups[$group]['agents'] = array_unique( $mobile_groups[$group]['agents'] );
				$mobile_groups[$group]['agents'] = array_map( 'strtolower', $mobile_groups[$group]['agents'] );
				sort( $mobile_groups[$group]['agents'] );
			}
			$enable_mobile = false;
			foreach ( $mobile_groups as $group_config ) {
				if ( $group_config['enabled'] ) {
					$enable_mobile = true;
					break;
				}
			}
			$config->set( 'mobile.enabled', $enable_mobile );
			$config->set( 'mobile.rgroups', $mobile_groups );
		}

		/**
		 * Referrer tab
		 */
		if ( $this->_page == 'w3tc_referrer' ) {
			$groups = Util_Request::get_array( 'referrer_groups' );

			$referrer_groups = array();

			foreach ( $groups as $group => $group_config ) {
				$group = strtolower( $group );
				$group = preg_replace( '~[^0-9a-z_]+~', '_', $group );
				$group = trim( $group, '_' );

				if ( $group ) {
					$theme = ( isset( $group_config['theme'] ) ? trim( $group_config['theme'] ) : 'default' );
					$enabled = ( isset( $group_config['enabled'] ) ? (boolean) $group_config['enabled'] : true );
					$redirect = ( isset( $group_config['redirect'] ) ? trim( $group_config['redirect'] ) : '' );
					$referrers = ( isset( $group_config['referrers'] ) ? explode( "\r\n", trim( $group_config['referrers'] ) ) : array() );

					$referrer_groups[$group] = array(
						'theme' => $theme,
						'enabled' => $enabled,
						'redirect' => $redirect,
						'referrers' => $referrers
					);
				}
			}

			/**
			 * Allow plugins modify W3TC referrer groups
			 */
			$referrer_groups = apply_filters( 'w3tc_referrer_groups', $referrer_groups );

			/**
			 * Sanitize mobile groups
			 */
			foreach ( $referrer_groups as $group => $group_config ) {
				$referrer_groups[$group] = array_merge( array(
						'theme' => '',
						'enabled' => true,
						'redirect' => '',
						'referrers' => array()
					), $group_config );

				$referrer_groups[$group]['referrers'] = array_unique( $referrer_groups[$group]['referrers'] );
				$referrer_groups[$group]['referrers'] = array_map( 'strtolower', $referrer_groups[$group]['referrers'] );
				sort( $referrer_groups[$group]['referrers'] );
			}

			$enable_referrer = false;
			foreach ( $referrer_groups as $group_config ) {
				if ( $group_config['enabled'] ) {
					$enable_referrer = true;
					break;
				}
			}
			$config->set( 'referrer.enabled', $enable_referrer );
			$config->set( 'referrer.rgroups', $referrer_groups );
		}

		/**
		 * CDN tab
		 */
		if ( $this->_page == 'w3tc_cdn' ) {
			$cdn_cnames = Util_Request::get_array( 'cdn_cnames' );
			$cdn_domains = array();

			foreach ( $cdn_cnames as $cdn_cname ) {
				$cdn_cname = trim( $cdn_cname );

				/**
				 * Auto expand wildcard domain to 10 subdomains
				 */
				$matches = null;

				if ( preg_match( '~^\*\.(.*)$~', $cdn_cname, $matches ) ) {
					$cdn_domains = array();

					for ( $i = 1; $i <= 10; $i++ ) {
						$cdn_domains[] = sprintf( 'cdn%d.%s', $i, $matches[1] );
					}

					break;
				}

				if ( $cdn_cname ) {
					$cdn_domains[] = $cdn_cname;
				}
			}

			switch ( $this->_config->get_string( 'cdn.engine' ) ) {
			case 'ftp':
				$config->set( 'cdn.ftp.domain', $cdn_domains );
				break;

			case 's3':
			case 's3_compatible':
				$config->set( 'cdn.s3.cname', $cdn_domains );
				break;

			case 'cf':
				$config->set( 'cdn.cf.cname', $cdn_domains );
				break;

			case 'cf2':
				$config->set( 'cdn.cf2.cname', $cdn_domains );
				break;

			case 'rackspace_cdn':
				$config->set( 'cdn.rackspace_cdn.domains', $cdn_domains );
				break;

			case 'rscf':
				$config->set( 'cdn.rscf.cname', $cdn_domains );
				break;

			case 'azure':
				$config->set( 'cdn.azure.cname', $cdn_domains );
				break;
			case 'mirror':
				$config->set( 'cdn.mirror.domain', $cdn_domains );
				break;

			case 'maxcdn':
				$config->set( 'cdn.maxcdn.domain', $cdn_domains );
				break;

			case 'netdna':
				$config->set( 'cdn.netdna.domain', $cdn_domains );
				break;

			case 'cotendo':
				$config->set( 'cdn.cotendo.domain', $cdn_domains );
				break;

			case 'edgecast':
				$config->set( 'cdn.edgecast.domain', $cdn_domains );
				break;

			case 'att':
				$config->set( 'cdn.att.domain', $cdn_domains );
				break;

			case 'akamai':
				$config->set( 'cdn.akamai.domain', $cdn_domains );
				break;

			case 'highwinds':
				$config->set( 'cdn.highwinds.host.domains', $cdn_domains );
				break;
			}
		}

		$old_ext_settings = $this->_config->get_array( 'extensions.settings', array() );
		$new_ext_settings = $old_ext_settings;
		$modified = false;

		$extensions = Extensions_Util::get_extensions( $config );
		foreach ( $extensions as $extension => $descriptor ) {
			$request = Util_Request::get_as_array(
				'extensions.settings.' . $extension . '.' );
			if ( count( $request ) > 0 ) {
				if ( !isset( $new_ext_settings[$extension] ) )
					$new_ext_settings[$extension] = array();

				foreach ( $request as $key => $value ) {
					if ( !isset( $old_ext_settings[$extension] ) ||
						!isset( $old_ext_settings[$extension][$key] ) ||
						$old_ext_settings[$extension][$key] != $value ) {
						$new_ext_settings[$extension][$key] = $value;
						$modified = true;
					}
				}
			}
		}

		if ( $modified )
			$config->set( "extensions.settings", $new_ext_settings );

		$data['new_config'] = $config;
		$data = apply_filters( 'w3tc_save_options', $data );
		$config = $data['new_config'];

		do_action( 'w3tc_config_ui_save', $config, $this->_config );
		do_action( "w3tc_config_ui_save-{$this->_page}", $config, $this->_config );

		Util_Admin::config_save( $this->_config, $config );

		if ( $this->_page == 'w3tc_cdn' ) {
			/**
			 * Handle Set Cookie Domain
			 */
			$set_cookie_domain_old = Util_Request::get_boolean( 'set_cookie_domain_old' );
			$set_cookie_domain_new = Util_Request::get_boolean( 'set_cookie_domain_new' );

			if ( $set_cookie_domain_old != $set_cookie_domain_new ) {
				if ( $set_cookie_domain_new ) {
					if ( !$this->enable_cookie_domain() ) {
						Util_Admin::redirect( array_merge(
								$data['response_query_string'], array(
									'w3tc_error' => 'enable_cookie_domain'
								)
							) );
					}
				} else {
					if ( !$this->disable_cookie_domain() ) {
						Util_Admin::redirect( array_merge(
								$data['response_query_string'], array(
									'w3tc_error' => 'disable_cookie_domain'
								)
							) );
					}
				}
			}
		}

		return array(
			'query_string' => $data['response_query_string'],
			'actions' => $data['response_actions'],
			'errors' => $data['response_errors'],
			'notes' => $data['response_notes']
		);
	}

	/**
	 * Enables COOKIE_DOMAIN
	 *
	 * @return bool
	 */
	function enable_cookie_domain() {
		$config_path = Util_Environment::wp_config_path();
		$config_data = @file_get_contents( $config_path );

		if ( $config_data === false ) {
			return false;
		}

		$cookie_domain = Util_Admin::get_cookie_domain();

		if ( $this->is_cookie_domain_define( $config_data ) ) {
			$new_config_data = preg_replace( W3TC_PLUGIN_TOTALCACHE_REGEXP_COOKIEDOMAIN, "define('COOKIE_DOMAIN', '" . addslashes( $cookie_domain ) . "')", $config_data, 1 );
		} else {
			$new_config_data = preg_replace( '~<\?(php)?~', "\\0\r\ndefine('COOKIE_DOMAIN', '" . addslashes( $cookie_domain ) . "'); // " . __( 'Added by W3 Total Cache', 'w3-total-cache' ) . "\r\n", $config_data, 1 );
		}

		if ( $new_config_data != $config_data ) {
			if ( !@file_put_contents( $config_path, $new_config_data ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Disables COOKIE_DOMAIN
	 *
	 * @return bool
	 */
	function disable_cookie_domain() {
		$config_path = Util_Environment::wp_config_path();
		$config_data = @file_get_contents( $config_path );

		if ( $config_data === false ) {
			return false;
		}

		if ( $this->is_cookie_domain_define( $config_data ) ) {
			$new_config_data = preg_replace( W3TC_PLUGIN_TOTALCACHE_REGEXP_COOKIEDOMAIN, "define('COOKIE_DOMAIN', false)", $config_data, 1 );

			if ( $new_config_data != $config_data ) {
				if ( !@file_put_contents( $config_path, $new_config_data ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Checks COOKIE_DOMAIN definition existence
	 *
	 * @param string  $content
	 * @return int
	 */
	function is_cookie_domain_define( $content ) {
		return preg_match( W3TC_PLUGIN_TOTALCACHE_REGEXP_COOKIEDOMAIN, $content );
	}


	/**
	 * Returns true if config section is sealed
	 *
	 * @param string  $section
	 * @return boolean
	 */
	protected function is_sealed( $section ) {
		return true;
	}

	/**
	 * Reads config from request
	 *
	 * @param Config  $config
	 */
	function read_request( $config ) {
		$request = Util_Request::get_request();

		include W3TC_DIR . '/ConfigKeys.php';   // define $keys

		foreach ( $request as $request_key => $request_value ) {
			if  ( is_array( $request_value ) )
				array_map( 'stripslashes_deep', $request_value );
			else
				$request_value = stripslashes( $request_value );
			if ( strpos( $request_key, 'memcached__servers' ) || strpos( $request_key, 'redis__servers' ) )
				$request_value = explode( ',', $request_value );

			$key = Util_Ui::config_key_from_http_name( $request_key );
			if ( is_array( $key ) ) {
				$config->set( $key, $request_value );
			} elseif ( array_key_exists( $key, $keys ) ) {
				$descriptor = $keys[$key];
				if ( isset( $descriptor['type'] ) &&
					$descriptor['type'] == 'array' ) {
					if ( is_array( $request_value ) ) {
						$request_value = implode( "\n", $request_value );
					}
					$request_value = explode( "\n",
						str_replace( "\r\n", "\n", $request_value ) );
				}

				$config->set( $key, $request_value );
			}
		}
	}
}
