<?php
/**
 * Yoast Duplicate Post plugin test file.
 *
 * @package Yoast\WP\Duplicate_Post\Tests
 */

use Yoast\WPTestUtils\WPIntegration;

// Disable xdebug backtrace.
if ( function_exists( 'xdebug_disable' ) ) {
	xdebug_disable();
}

echo 'Welcome to the Duplicate Post Test Suite' . PHP_EOL;
echo 'Version: 1.0' . PHP_EOL . PHP_EOL;

/*
 * Load the plugin(s).
 */
require_once dirname( __DIR__, 2 ) . '/vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

$_wp_tests_dir = WPIntegration\get_path_to_wp_test_dir();

// Get access to tests_add_filter() function.
require_once $_wp_tests_dir . 'includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	function() {
		require_once dirname( __DIR__, 2 ) . '/duplicate-post.php';
	}
);

/*
 * Load WordPress, which will load the Composer autoload file, and load the MockObject autoloader after that.
 */
WPIntegration\bootstrap_it();
