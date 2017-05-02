<?php

if ( !defined( 'ABSPATH' ) ) {
	die();
}

define( 'W3TC', true );
define( 'W3TC_VERSION', '0.9.5.4' );
define( 'W3TC_POWERED_BY', 'W3 Total Cache' );
define( 'W3TC_EMAIL', 'w3tc@w3-edge.com' );
define( 'W3TC_TEXT_DOMAIN', 'w3-total-cache' );
define( 'W3TC_LINK_URL', 'https://www.w3-edge.com/wordpress-plugins/' );
define( 'W3TC_LINK_NAME', 'W3 EDGE, Optimization Products for WordPress' );
define( 'W3TC_FEED_URL', 'https://wordpress.org/support/plugin/w3-total-cache/feed' );
define( 'W3TC_NEWS_FEED_URL', 'http://feeds.feedburner.com/W3EDGE' );
define( 'W3TC_README_URL', 'http://plugins.svn.wordpress.org/w3-total-cache/trunk/readme.txt' );
define( 'W3TC_SUPPORT_US_PRODUCT_URL', 'https://www.w3-edge.com/products/w3-total-cache/' );
define( 'W3TC_SUPPORT_US_RATE_URL', 'http://wordpress.org/support/view/plugin-reviews/w3-total-cache?rate=5#postform' );
define( 'W3TC_SUPPORT_US_TIMEOUT', 2592000 );   // 30 days
define( 'W3TC_SUPPORT_US_TWEET', 'YES! I optimized the user experience of my website with the W3 Total Cache #WordPress #plugin by @w3edge! http://bit.ly/TeSBL3' );
define( 'W3TC_EDGE_TIMEOUT', 7 * 24 * 60 * 60 );
define( 'W3TC_SUPPORT_REQUEST_URL', 'https://www.w3-edge.com/w3tc-support/extra' );
define( 'W3TC_SUPPORT_SERVICES_URL', 'https://www.w3-edge.com/w3tc/premium-widget.json' );
define( 'W3TC_TRACK_URL', 'https://www.w3-edge.com/w3tc/track/' );
define( 'W3TC_MAILLINGLIST_SIGNUP_URL', 'https://www.w3-edge.com/w3tc/emailsignup/' );
define( 'W3TC_CLI_PIDS', '.w3tc_cli_pids');
define( 'W3TC_CLI_URLS', '.w3tc_cli_urls');
define( 'NEWRELIC_SIGNUP_URL', 'http://bit.ly/w3tc-partner-newrelic-signup' );
define( 'MAXCDN_SIGNUP_URL', 'http://bit.ly/w3tc-cdn-maxcdn-create-account' );
define( 'MAXCDN_AUTHORIZE_URL', 'http://bit.ly/w3tc-cdn-maxcdn-authorize' );
define( 'NETDNA_AUTHORIZE_URL', 'https://cp.netdna.com/i/w3tc' );
define( 'GOOGLE_DRIVE_AUTHORIZE_URL', 'https://www.w3-edge.com/w3tcoa/google-drive/' );

// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
if ( !defined( 'EDD_W3EDGE_STORE_URL' ) ) define( 'EDD_W3EDGE_STORE_URL', 'https://www.w3-edge.com/' );
if ( !defined( 'EDD_W3EDGE_STORE_URL_PLUGIN' ) ) define( 'EDD_W3EDGE_STORE_URL_PLUGIN', 'https://www.w3-edge.com/?w3tc_buy_pro_plugin' );

// the name of your product. This should match the download name in EDD exactly
define( 'EDD_W3EDGE_W3TC_NAME', 'W3 Total Cache Pro: Annual Subscription' );

define( 'W3TC_WIN', ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) );

if ( !defined( 'W3TC_DIR' ) )
	define( 'W3TC_DIR', realpath( dirname( __FILE__ ) ) );

define( 'W3TC_FILE', 'w3-total-cache/w3-total-cache.php' );
define( 'W3TC_INC_DIR', W3TC_DIR . '/inc' );
define( 'W3TC_INC_WIDGET_DIR', W3TC_INC_DIR. '/widget' );
define( 'W3TC_INC_OPTIONS_DIR', W3TC_INC_DIR . '/options' );
define( 'W3TC_INC_LIGHTBOX_DIR', W3TC_INC_DIR . '/lightbox' );
define( 'W3TC_INC_POPUP_DIR', W3TC_INC_DIR . '/popup' );
define( 'W3TC_LIB_DIR', W3TC_DIR . '/lib' );
define( 'W3TC_LIB_NETDNA_DIR', W3TC_LIB_DIR . '/NetDNA' );
define( 'W3TC_LIB_NEWRELIC_DIR', W3TC_LIB_DIR . '/NewRelic' );
define( 'W3TC_INSTALL_DIR', W3TC_DIR . '/wp-content' );
define( 'W3TC_INSTALL_MINIFY_DIR', W3TC_INSTALL_DIR . '/w3tc/min' );
define( 'W3TC_LANGUAGES_DIR', W3TC_DIR . '/languages' );

if ( !defined( 'WP_CONTENT_DIR' ) )
	define( 'WP_CONTENT_DIR', realpath( W3TC_DIR . '/../..' ) );

if ( !defined( 'W3TC_CACHE_DIR' ) )
	define( 'W3TC_CACHE_DIR', WP_CONTENT_DIR . '/cache' );
if ( !defined( 'W3TC_CONFIG_DIR' ) )
	define( 'W3TC_CONFIG_DIR', WP_CONTENT_DIR . '/w3tc-config' );
if ( !defined( 'W3TC_CACHE_MINIFY_DIR' ) )
	define( 'W3TC_CACHE_MINIFY_DIR', W3TC_CACHE_DIR  . '/minify' );
if ( !defined( 'W3TC_CACHE_PAGE_ENHANCED_DIR' ) )
	define( 'W3TC_CACHE_PAGE_ENHANCED_DIR', W3TC_CACHE_DIR  . '/page_enhanced' );
if ( !defined( 'W3TC_CACHE_TMP_DIR' ) )
	define( 'W3TC_CACHE_TMP_DIR', W3TC_CACHE_DIR . '/tmp' );
if ( !defined( 'W3TC_CACHE_BLOGMAP_FILENAME' ) )
	define( 'W3TC_CACHE_BLOGMAP_FILENAME', W3TC_CACHE_DIR . '/blogs.php' );
if ( !defined( 'W3TC_CACHE_FILE_EXPIRE_MAX' ) )
	define( 'W3TC_CACHE_FILE_EXPIRE_MAX', 2592000 );

define( 'W3TC_CDN_COMMAND_UPLOAD', 1 );
define( 'W3TC_CDN_COMMAND_DELETE', 2 );
define( 'W3TC_CDN_COMMAND_PURGE', 3 );
define( 'W3TC_CDN_TABLE_QUEUE', 'w3tc_cdn_queue' );

define( 'W3TC_INSTALL_FILE_ADVANCED_CACHE', W3TC_INSTALL_DIR . '/advanced-cache.php' );
define( 'W3TC_INSTALL_FILE_DB', W3TC_INSTALL_DIR . '/db.php' );
define( 'W3TC_INSTALL_FILE_OBJECT_CACHE', W3TC_INSTALL_DIR . '/object-cache.php' );

define( 'W3TC_ADDIN_FILE_ADVANCED_CACHE', WP_CONTENT_DIR . '/advanced-cache.php' );
define( 'W3TC_ADDIN_FILE_DB', WP_CONTENT_DIR . '/db.php' );
define( 'W3TC_FILE_DB_CLUSTER_CONFIG', WP_CONTENT_DIR . '/db-cluster-config.php' );
define( 'W3TC_ADDIN_FILE_OBJECT_CACHE', WP_CONTENT_DIR . '/object-cache.php' );

define( 'W3TC_MARKER_BEGIN_WORDPRESS', '# BEGIN WordPress' );
define( 'W3TC_MARKER_BEGIN_PGCACHE_CORE', '# BEGIN W3TC Page Cache core' );
define( 'W3TC_MARKER_BEGIN_PGCACHE_CACHE', '# BEGIN W3TC Page Cache cache' );
define( 'W3TC_MARKER_BEGIN_PGCACHE_WPSC', '# BEGIN WPSuperCache' );
define( 'W3TC_MARKER_BEGIN_BROWSERCACHE_CACHE', '# BEGIN W3TC Browser Cache' );
define( 'W3TC_MARKER_BEGIN_BROWSERCACHE_NO404WP', '# BEGIN W3TC Skip 404 error handling by WordPress for static files' );
define( 'W3TC_MARKER_BEGIN_MINIFY_CORE', '# BEGIN W3TC Minify core' );
define( 'W3TC_MARKER_BEGIN_MINIFY_CACHE', '# BEGIN W3TC Minify cache' );
define( 'W3TC_MARKER_BEGIN_MINIFY_LEGACY', '# BEGIN W3TC Minify' );
define( 'W3TC_MARKER_BEGIN_CDN', '# BEGIN W3TC CDN' );


define( 'W3TC_MARKER_END_WORDPRESS', '# END WordPress' );
define( 'W3TC_MARKER_END_PGCACHE_CORE', '# END W3TC Page Cache core' );
define( 'W3TC_MARKER_END_PGCACHE_CACHE', '# END W3TC Page Cache cache' );
define( 'W3TC_MARKER_END_PGCACHE_LEGACY', '# END W3TC Page Cache' );
define( 'W3TC_MARKER_END_PGCACHE_WPSC', '# END WPSuperCache' );
define( 'W3TC_MARKER_END_BROWSERCACHE_CACHE', '# END W3TC Browser Cache' );
define( 'W3TC_MARKER_END_BROWSERCACHE_NO404WP', '# END W3TC Skip 404 error handling by WordPress for static files' );
define( 'W3TC_MARKER_END_MINIFY_CORE', '# END W3TC Minify core' );
define( 'W3TC_MARKER_END_MINIFY_CACHE', '# END W3TC Minify cache' );
define( 'W3TC_MARKER_END_MINIFY_LEGACY', '# END W3TC Minify' );
define( 'W3TC_MARKER_END_CDN', '# END W3TC CDN' );
define( 'W3TC_MARKER_END_NEW_RELIC_CORE', '# END W3TC New Relic core' );


if ( !defined( 'W3TC_EXTENSION_DIR' ) )
	define( 'W3TC_EXTENSION_DIR', ( defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins' ) );

@ini_set( 'pcre.backtrack_limit', 4194304 );
@ini_set( 'pcre.recursion_limit', 4194304 );

global $w3_late_init;
$w3_late_init = false;

/**
 * Class autoloader
 *
 * @param string  $class Classname
 */
function w3tc_class_autoload( $class ) {
	$base = null;

	// some php pass classes with slash
	if ( substr( $class, 0, 1 ) == "\\" )
		$class = substr( $class, 1 );

	if ( substr( $class, 0, 5 ) == 'HTTP_' || substr( $class, 0, 7 ) == 'Minify_' ) {
		$base = W3TC_LIB_DIR . DIRECTORY_SEPARATOR . 'Minify' . DIRECTORY_SEPARATOR;
	} elseif ( substr( $class, 0, 8 ) == 'Minify0_' ) {
		$base = W3TC_LIB_DIR . DIRECTORY_SEPARATOR . 'Minify' . DIRECTORY_SEPARATOR;
		$class = substr( $class, 8 );
	} elseif ( substr( $class, 0, 13 ) == 'W3TCG_Google_' &&
		( !defined( 'W3TC_GOOGLE_LIBRARY' ) || W3TC_GOOGLE_LIBRARY ) ) {
		// Google library
		$classPath = explode( '_', substr( $class, 6 ) );
		if ( count( $classPath ) > 3 ) {
			// Maximum class file path depth in this project is 3.
			$classPath = array_slice( $classPath, 0, 3 );
		}

		$filePath = W3TC_LIB_DIR . DIRECTORY_SEPARATOR .
			implode( '/', $classPath ) . '.php';

		if ( file_exists( $filePath ) )
			require $filePath;
		return;
	}

	if ( !is_null( $base ) ) {
		$file = $base . strtr( $class, "\\_",
			DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR ) . '.php';
		if ( file_exists( $file ) )
			require_once $file;
	} else if ( substr( $class, 0, 5 ) == 'W3TC\\' ) {
			$filename = W3TC_DIR . DIRECTORY_SEPARATOR . substr( $class, 5 ) . '.php';

			if ( file_exists( $filename ) ) {
				require $filename;
			} else {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					echo 'Attempt to create object of class ' . 
						$class . ' has been made, but file ' . 
						$filename . ' doesnt exists';
					debug_print_backtrace();
				}
			}
		}
}

spl_autoload_register( 'w3tc_class_autoload' );

/**
 * W3 Total Cache plugins API
 */

/**
 * Returns config.
 *
 * !!! NOTICE !!!
 * 3rd party developers, please do not modify the plugin's configuration without
 * notifying the user beforehand. As an alternative, throw a notification to the
 * user like: "Configure W3 Total Cache for me" and allow the user to dismiss
 * the notification.
 * !!! NOTICE !!!
 */
function w3tc_config() {
	/*
	 * Some plugins make incorrect decisions based on configuration
	 * and force to disable modules working otherwise or 
	 * adds notices on each wp-admin page without ability to remove it.
	 * By defining W3TC_CONFIG_HIDE you may still use w3tc configuration you like.
	 */
	if ( defined( 'W3TC_CONFIG_HIDE' ) && W3TC_CONFIG_HIDE )
		return new W3_Config();

	$config = \W3TC\Dispatcher::config();
	return $config;
}

/**
 * Shortcut for url varnish flush
 */
function w3tc_flush_all() {
	$o = \W3TC\Dispatcher::component( 'CacheFlush' );
	$o->flush_all();
}

/**
 * Purges/Flushes post page
 */
function w3tc_flush_post( $post_id ) {
	$o = \W3TC\Dispatcher::component( 'CacheFlush' );
	$o->flush_post( $post_id );
}

/**
 * Purges/Flushes all posts
 */
function w3tc_flush_posts() {
	$o = \W3TC\Dispatcher::component( 'CacheFlush' );
	$o->flush_posts();
}

/**
 * Purges/Flushes url
 */
function w3tc_flush_url( $url ) {
	$o = \W3TC\Dispatcher::component( 'CacheFlush' );
	$o->flush_url( $url );
}

/**
 * Shared read-locking access to retrieve contents
 * of an existing file. Although available for use by all, 
 * this was made to assist WP-CLI prime caching.
 *
 * @param   string   $file          File path to read
 * @param   boolean  $serialized    Is the file serialized (to unserialize it when returning)
 * @return  string
 */
function w3tc_lock_read( $file, $serialized = true ) {
    $res = false;
    $h = @fopen( $file, 'r' );

    if ( $h !== false )
    {
        flock( $h, LOCK_SH );

        clearstatcache( true, $file );
        $sz = filesize( $file );

        if ( $sz > 0 )
            $res = fread( $h, $sz );
        else
            $res = "";

        flock( $h, LOCK_UN );
        fclose( $h );

        if ( $serialized ) {
            $res = unserialize( $res );
        }
    }

    return $res;
}

/**
 * Exclusive lock access to write contents to a file.
 * Although available for use by all, this was made to 
 * assist WP-CLI prime caching.
 *
 * @param   string  $file           File path to write to
 * @param   string  $data           The data to write
 * @param   boolean $serialized     Should the data be serialized
 * @return  string
 */
function w3tc_lock_write( $file, $data, $serialized = true ) {
    $res = false;
    $h = @fopen( $file, 'c' );

    if ( $h !== false )
    {
        flock( $h, LOCK_EX );

        ftruncate( $h, 0 );

        if ( $serialized ) {
            $data = serialize( $data );
        }

        $res = fwrite( $h, $data );
        fflush( $h );
        flock( $h, LOCK_UN );
        fclose( $h );
    }

    return $res;
}

/**
 * Unschedule an event by using a given hook name
 *
 * @param   string  $hook   The action hook name to unschedule
 * @return  boolean
 */
function w3tc_clear_hook_crons( $hook ) {
    $res = false;    
    $crons = _get_cron_array();
    if ( empty( $crons ) ) {
        return false;
    }
    foreach( $crons as $timestamp => $cron ) {
        if ( ! empty( $cron[$hook] ) )  {
            unset( $crons[$timestamp][$hook] );
            $res = true;
        }

        if ( empty( $crons[$timestamp] ) ) {
            unset( $crons[$timestamp] );
        }
    }
    _set_cron_array( $crons );
    return $res;
}

/**
 * Stops an actively running WP-CLI page cache prime session.
 *
 * NOTE:
 * ====
 *
 * Because not every PHP environment is the same, if the user requests to stop 
 * an actively running WP-CLI prime session this checks for and uses either the 
 * sysvmsg or posix extension, if available, or the exec() function as a last
 * resort. It does this because the cli priming is carefully managed across
 * separate scheduled event processes and so we keep track of who those
 * active processes are and end them individually when requested to stop.
 *
 * @param   string  &$result    A text message result sent back to the caller
 * @return  boolean
 */
function w3tc_wpcli_stop_prime( &$result = "" ) {
    $w3_prime = \W3TC\Dispatcher::component( 'PgCache_Plugin_Admin' );
    
    if ( extension_loaded( 'sysvmsg' ) ) {
        /**
         * Inter-process messaging is available and was used to manage pids
         */
        $pids = true;
        $que = msg_stat_queue( msg_get_queue( 99909 ) );
        
        if ( $que['msg_qnum'] > 0 ) {
            msg_remove_queue( msg_get_queue( 99909 ) );
        } else {
            $pids = false;
        }
    } elseif (false !== ( $pids = $w3_prime->get_cli_pids() ) ) {
        foreach( $pids as $pid ) {
            if ( extension_loaded( 'posix' ) && w3tc_cmd_enabled( "posix_kill" ) ) {
                /**
                 * The posix extension is available - managed wp-cli pids will be stopped this way
                 */
                @posix_kill( $pid, SIGTERM );
            } elseif ( w3tc_cmd_enabled( "exec" ) ) {
                /**
                 * This is the fallback option since the other two aren't available.
                 * We use the exec to stop our managed wp-cli pids.
                 */
                @exec( "kill -9 $pid >/dev/null 2>&1" );
            } else {
                $result = "Can't issue the command to stop running process(es). Need either: exec, posix, or sysvmsg.";
                return false;
            }
        }

        $w3_prime->delete_cli_pids();
    }

    $w3_prime->delete_cli_urls();
    
    if ( w3tc_clear_hook_crons( 'w3_pgcache_prime_cli' ) === false && $pids === false ) {
        $result = "No page cache priming to stop. Either the priming has completed or was already stopped.";
        return false;
    }

    return true;
}

/**
 * Check if PHP function is available to use
 *
 * @param   string  $cmd   The PHP function name to check
 * @return  boolean
 */
function w3tc_cmd_enabled( $cmd ) {
  $disabled = explode( ',', @ini_get( 'disable_functions' ) );
  return !in_array( $cmd, $disabled );
}

/**
 * deprecated
 * Shortcut for page cache flush
 *
 * @return boolean
 */
function w3tc_pgcache_flush() {
	return w3tc_flush_posts();
}

/**
 * deprecated
 * Shortcut for page post cache flush
 *
 * @param integer $post_id
 * @return boolean
 */
function w3tc_pgcache_flush_post( $post_id ) {
	return w3tc_flush_post( $post_id );
}

/**
 * deprecated
 * Shortcut for page post cache flush by url
 *
 * @param integer $url
 * @return boolean
 */
function w3tc_pgcache_flush_url( $url ) {
	return w3tc_flush_url( $url );
}

/**
 * deprecated
 * Shortcut for refreshing the media query string.
 */
function w3tc_browsercache_flush() {
	$o = \W3TC\Dispatcher::component( 'CacheFlush' );
	return $o->browsercache_flush();
}

/**
 * deprecated
 * Shortcut for database cache flush
 *
 */
function w3tc_dbcache_flush() {
	$o = \W3TC\Dispatcher::component( 'CacheFlush' );
	$o->dbcache_flush();
}

/**
 * deprecated
 * Shortcut for minify cache flush
 *
 */
function w3tc_minify_flush() {
	$o = \W3TC\Dispatcher::component( 'CacheFlush' );
	$o->minifycache_flush();

}

/**
 * deprecated
 * Shortcut for objectcache cache flush
 *
 */
function w3tc_objectcache_flush() {
	$o = \W3TC\Dispatcher::component( 'CacheFlush' );
	$o->objectcache_flush();
}

/**
 * deprecated
 * Shortcut for CDN purge files
 *
 * @param array   $files Array consisting of uri paths (i.e wp-content/uploads/image.pnp)
 * @return mixed
 */
function w3tc_cdn_purge_files( $files ) {
	$o = \W3TC\Dispatcher::component( 'CacheFlush' );
	return $o->cdn_purge_files( $files );
}

/**
 * deprecated
 * Prints script tag for scripts group
 *
 * @param string  $location
 * @return void
 */
function w3tc_minify_script_group( $location ) {
	$o = \W3TC\Dispatcher::component( 'Minify_Plugin' );
	$o->printed_scripts[] = $location;

	$r = $o->get_script_group( $location );
	echo $r['body'];
}

/**
 * deprecated
 * Prints style tag for styles group
 *
 * @param string  $location
 * @return void
 */
function w3tc_minify_style_group( $location ) {
	$o = \W3TC\Dispatcher::component( 'Minify_Plugin' );
	$o->printed_styles[] = $location;

	$r = $o->get_style_group( $location );
	echo $r['body'];
}

/**
 * deprecated
 * Prints script tag for custom scripts
 *
 * @param string|array $files
 * @param boolean $blocking
 * @return void
 */
function w3tc_minify_script_custom( $files, $blocking = true ) {
	$o = \W3TC\Dispatcher::component( 'Minify_Plugin' );
	echo $o->get_script_custom( $files, $blocking );
}

/**
 * deprecated
 * Prints style tag for custom styles
 *
 * @param string|array $files
 * @return void
 */
function w3tc_minify_style_custom( $files ) {
	$o = \W3TC\Dispatcher::component( 'Minify_Plugin' );
	$r = $o->get_style_custom( $files );
	echo $r['body'];
}

/**
 * deprecated
 * Use Util_Theme::get_themes() to get a list themenames to use with user agent groups
 *
 * @param unknown $group_name
 * @param string  $theme      the themename default is default theme. For childtheme it should be parentthemename/childthemename
 * @param string  $redirect
 * @param array   $agents     Remember to escape special characters like spaces, dots or dashes with a backslash. Regular expressions are also supported.
 * @param bool    $enabled
 */
function w3tc_save_user_agent_group( $group_name, $theme = 'default', $redirect = '', $agents = array(), $enabled = false ) {
	$o = \W3TC\Dispatcher::component( 'Mobile_UserAgent' );
	$o->save_group( $group_name, $theme, $redirect, $agents, $enabled );
}

/**
 * deprecated
 *
 * @param unknown $group
 */
function w3tc_delete_user_agent_group( $group ) {
	$o = \W3TC\Dispatcher::component( 'Mobile_UserAgent' );
	$o->delete_group( $group );

}

/**
 * deprecated
 *
 * @param unknown $group
 * @return mixed
 */
function w3tc_get_user_agent_group( $group ) {
	$o = \W3TC\Dispatcher::component( 'Mobile_UserAgent' );
	return $o->get_group_values( $group );
}

/**
 * deprecated
 * Use Util_Theme::get_themes() to get a list themenames to use with referrer groups
 *
 * @param unknown $group_name
 * @param string  $theme      the themename default is default theme. For childtheme it should be parentthemename/childthemename
 * @param string  $redirect
 * @param array   $referrers  Remember to escape special characters like spaces, dots or dashes with a backslash. Regular expressions are also supported.
 * @param bool    $enabled
 */
function w3tc_save_referrer_group( $group_name, $theme = 'default', $redirect = '', $referrers = array(), $enabled = false ) {
	$o = \W3TC\Dispatcher::component( 'Mobile_Referrer' );
	$o->save_group( $group_name, $theme, $redirect, $referrers, $enabled );
}

/**
 * deprecated
 *
 * @param unknown $group
 */
function w3tc_delete_referrer_group( $group ) {
	$o = \W3TC\Dispatcher::component( 'Mobile_Referrer' );
	$o->delete_group( $group );
}

/**
 * deprecated
 *
 * @param unknown $group
 * @return mixed
 */
function w3tc_get_referrer_group( $group ) {
	$o = \W3TC\Dispatcher::component( 'Mobile_Referrer' );
	return $o->get_group_values( $group );
}


/**
 * deprecated
 * Flushes files from opcache.
 *
 * @param bool    $http if delete request should be made over http to current site. Default false.
 * @return mixed
 */
function w3tc_opcache_flush( $http = false ) {
	if ( !$http ) {
		$o = \W3TC\Dispatcher::component( 'CacheFlush' );
		return $o->opcache_flush();
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
				'command' => 'flush' ),
		);
		$result = wp_remote_post( $url, $post );
		if ( is_wp_error( $result ) ) {
			return $result;
		} elseif ( $result['response']['code'] != '200' ) {
			return $result['response']['code'];
		}

		return true;
	}
}

/**
 * deprecated
 * Reloads files.
 *
 * @param string[] $files list of files supports, fullpath, from root, wp-content
 * @param bool    $http  if delete request should be made over http to current site. Default false.
 * @return mixed
 */
function w3tc_opcache_flush_file( $file, $http = false ) {
	if ( !$http ) {
		$o = \W3TC\Dispatcher::component( 'CacheFlush' );
		return $o->opcache_flush_file( $file );
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
				'file' => $file
			),
		);
		$result = wp_remote_post( $url, $post );
		if ( is_wp_error( $result ) ) {
			return $result;
		} elseif ( $result['response']['code'] != '200' ) {
			return $result['response']['code'];
		}

		return true;
	}
}

/**
 * Deprecated. Retained for 3rd parties that used it. see w3tc_config()
 *
 * Some plugins make incorrect decisions based on configuration
 * and force to disable modules working otherwise or 
 * adds notices on each wp-admin page without ability to remove it.
 * By defining W3TC_CONFIG_HIDE you may still use w3tc configuration you like.
 */
if ( defined( 'W3TC_CONFIG_HIDE' ) && W3TC_CONFIG_HIDE ) {
	class W3_Config {
	    public function __construct( $master = false, $blog_id = null ) {
	    }
		
		public function get_string( $key, $default = '', $trim = true ) {
			return '';
		}
    	
    	public function get_integer( $key, $default = 0 ) {
			return 0;
    	}

		public function get_boolean( $key, $default = false ) {
			return false;
    	}
	}
} else {
	class W3_Config extends \W3TC\Config {
	    public function __construct( $master = false, $blog_id = null ) {
	    	if ( $master )
	    		$blog_id = 0;

	        return parent::__construct( $blog_id );
	    }
	}
}

/**
 * Deprecated. Retained for 3rd parties that use it. see w3tc_config()
 */
class W3_ConfigWriter {
	public function __construct( $p1 = 0, $p2 = 0 ) {
    }
    public function set( $p1 = 0, $p2 = 0 ) {
    }
    public function save( $p1 = 0, $p2 = 0 ) {
    }
    public function refresh_w3tc() {
    }
}

/**
Deprecated. Retained for 3rd parties that use it. see w3tc_config()
*/
function w3_instance( $class ) {
    $legacy_class_name = null;

    if ( $class == 'W3_Config' ) {
    	if ( defined( 'W3TC_CONFIG_HIDE' ) && W3TC_CONFIG_HIDE )
    		return new W3_Config();

       	$legacy_class_name = 'Config';
    }
    elseif ( $class == 'W3_ObjectCacheBridge' )
        $legacy_class_name = 'ObjectCache_WpObjectCache';
    elseif ( $class == 'W3_PgCache' )
        $legacy_class_name = 'PgCache_ContentGrabber';
    elseif ( $class == 'W3_Redirect' )
        $legacy_class_name = 'Mobile_Redirect';
    else
    	return null;

    return \W3TC\Dispatcher::component( $legacy_class_name );
}
