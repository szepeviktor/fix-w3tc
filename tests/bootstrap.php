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
	$plugin_dir  = dirname( dirname( __FILE__ ) );
	$plugins_dir = dirname($plugin_dir);
	$content_dir = dirname($plugins_dir);
	
	require $plugin_dir. '/w3-total-cache.php';
	update_option( 'active_plugins', 'w3-total-cache/w3-total-cache.php' );
	
	copy($plugin_dir.'/wp-content/advanced-cache.php', $content_dir.'/advanced-cache.php');
	copy($plugin_dir.'/wp-content/db.php',             $content_dir.'/db.php');
	copy($plugin_dir.'/wp-content/object-cache.php',   $content_dir.'/object-cache.php');
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
