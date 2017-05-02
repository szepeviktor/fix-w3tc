<?php
namespace W3TC;



class Generic_AdminActions_Test {

	private $_config = null;

	/**
	 * Current page
	 *
	 * @var null|string
	 */
	private $_page = null;

	function __construct() {
		$this->_config = Dispatcher::config();

		$this->_page = Util_Admin::get_current_page();
	}


	/**
	 * Test memcached
	 *
	 * @return void
	 */
	function w3tc_test_memcached() {
		$servers = Util_Request::get_array( 'servers' );

		$this->respond_test_result( $this->is_memcache_available( $servers ) );
	}

	/**
	 * Test memcached
	 *
	 * @return void
	 */
	function w3tc_test_redis() {
		$servers = Util_Request::get_array( 'servers' );
		$persistent = Util_Request::get_boolean( 'persistent', false );
		$password   = Util_Request::get_string('password', '');
		$dbid       = Util_Request::get_integer( 'dbid', 0 );

		if ( count( $servers ) <= 0 )
			$success = false;
		else {
			$success = true;

			foreach ( $servers as $server ) {
				@$cache = Cache::instance( 'redis', array(
						'servers' => $server,
						'persistent' => $persistent,
						'password'   => $password,
						'dbid'       => $dbid
					) );
				if ( is_null( $cache ) )
					$success = false;

				$test_string = sprintf( 'test_' . md5( time() ) );
				$test_value = array( 'content' => $test_string );
				$cache->set( $test_string, $test_value, 60 );
				$test_value = $cache->get( $test_string );
				if ( $test_value['content'] != $test_string )
					$success = false;
			}
		}

		$this->respond_test_result( $success );
	}

	private function respond_test_result( $success ) {
		if ( $success ) {
			$response = array(
				'result' => true,
				'error' => __( 'Test passed.', 'w3-total-cache' )
			);
		} else {
			$response = array(
				'result' => false,
				'error' => __( 'Test failed.', 'w3-total-cache' )
			);
		}

		echo json_encode( $response );
		exit();
	}

	/**
	 * Test minifier action
	 *
	 * @return void
	 */
	function w3tc_test_minifier() {
		$engine = Util_Request::get_string( 'engine' );
		$path_java = Util_Request::get_string( 'path_java' );
		$path_jar = Util_Request::get_string( 'path_jar' );

		$result = false;
		$error = '';

		if ( $engine != 'googleccjs' ) {
			if ( !$path_java )
				$error = __( 'Empty JAVA executable path.', 'w3-total-cache' );
			elseif ( !$path_jar )
				$error = __( 'Empty JAR file path.', 'w3-total-cache' );
		}

		if ( empty( $error ) ) {
			switch ( $engine ) {
			case 'yuijs':
				\Minify_YUICompressor::$tempDir = Util_File::create_tmp_dir();
				\Minify_YUICompressor::$javaExecutable = $path_java;
				\Minify_YUICompressor::$jarFile = $path_jar;

				$result = \Minify_YUICompressor::testJs( $error );
				break;

			case 'yuicss':
				\Minify_YUICompressor::$tempDir = Util_File::create_tmp_dir();
				\Minify_YUICompressor::$javaExecutable = $path_java;
				\Minify_YUICompressor::$jarFile = $path_jar;

				$result = \Minify_YUICompressor::testCss( $error );
				break;

			case 'ccjs':
				\Minify_ClosureCompiler::$tempDir = Util_File::create_tmp_dir();
				\Minify_ClosureCompiler::$javaExecutable = $path_java;
				\Minify_ClosureCompiler::$jarFile = $path_jar;

				$result = \Minify_ClosureCompiler::test( $error );
				break;

			case 'googleccjs':

				$result = \Minify_JS_ClosureCompiler::test( $error );
				break;

			default:
				$error = __( 'Invalid engine.', 'w3-total-cache' );
				break;
			}
		}

		$response = array(
			'result' => $result,
			'error' => $error
		);

		echo json_encode( $response );
	}

	/**
	 * Check if memcache is available
	 *
	 * @param array   $servers
	 * @return boolean
	 */
	private function is_memcache_available( $servers ) {
		if ( count( $servers ) <= 0 )
			return false;

		foreach ( $servers as $server ) {
			@$memcached = Cache::instance( 'memcached', array(
					'servers' => $server,
					'persistent' => false
				) );
			if ( is_null( $memcached ) )
				return false;

			$test_string = sprintf( 'test_' . md5( time() ) );
			$test_value = array( 'content' => $test_string );
			$memcached->set( $test_string, $test_value, 60 );
			$test_value = $memcached->get( $test_string );
			if ( $test_value['content'] != $test_string )
				return false;
		}

		return true;
	}

	/**
	 * Self test action
	 */
	function w3tc_test_self() {
		include W3TC_INC_LIGHTBOX_DIR . '/self_test.php';
	}

	/**
	 * Minify recommendations action
	 *
	 * @return void
	 */
	function w3tc_test_minify_recommendations() {
		$options_minify = new Minify_Page();
		$options_minify->recommendations();
	}


	/**
	 * Page Speed results action
	 *
	 * @return void
	 */
	function w3tc_test_pagespeed_results() {
		$title = 'Google Page Speed';

		$config = Dispatcher::config();
		$key = $config->get_string( 'widget.pagespeed.key' );
		$ref = $config->get_string( 'widget.pagespeed.key.restrict.referrer' );
		
		$w3_pagespeed = new PageSpeed_Api( $key, $ref );

		$results = $w3_pagespeed->analyze( get_home_url() );
		include W3TC_INC_POPUP_DIR . '/pagespeed_results.php';
	}
}
