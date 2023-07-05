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

if ( ! defined( 'YOAST_DUPLICATE_POST_TEST_ROOT_DIR' ) ) {
	define( 'YOAST_DUPLICATE_POST_TEST_ROOT_DIR', __DIR__ . '/' ); // Includes trailing slash.
}

/*
 * Load the plugin(s).
 */
require_once dirname( __DIR__, 2 ) . '/vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

$GLOBALS['wp_tests_options'] = [
	'active_plugins' => [
		'duplicate-post/duplicate-post',
	],
];

$_wp_tests_dir = WPIntegration\get_path_to_wp_test_dir();
if ( $_wp_tests_dir === false ) {
	$_wp_tests_dir = YOAST_DUPLICATE_POST_TEST_ROOT_DIR . '../../../../tests/phpunit/';
}


// Load some helpful functions.
require_once $_wp_tests_dir . 'includes/functions.php';

/**
 * Activates this plugin in WordPress so it can be tested.
 */
function _manually_load_plugin() {
	require YOAST_DUPLICATE_POST_TEST_ROOT_DIR . '../../duplicate-post.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/*
 * Load WordPress, which will load the Composer autoload file, and load the MockObject autoloader after that.
 */
WPIntegration\bootstrap_it();
