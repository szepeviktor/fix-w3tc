<?php
/**
 * PHPUnit bootstrap file
 *
 * @package W3_Total_Cache
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	copy('/tmp/wordpress/wp-content/plugins/w3-total-cache/wp-content/advanced-cache.php', '/tmp/wordpress/wp-content/advanced-cache.php');
	copy('/tmp/wordpress/wp-content/plugins/w3-total-cache/wp-content/db.php',             '/tmp/wordpress/wp-content/db.php');
	copy('/tmp/wordpress/wp-content/plugins/w3-total-cache/wp-content/object-cache.php',   '/tmp/wordpress/wp-content/object-cache.php');
	
	require dirname( dirname( __FILE__ ) ) . '/w3-total-cache.php';
	update_option( 'active_plugins', 'w3-total-cache/w3-total-cache.php' );
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
