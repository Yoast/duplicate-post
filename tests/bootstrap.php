<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Yoast\WP\Duplicate_Post\Tests
 */

define( 'OBJECT', 'OBJECT' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'ARRAY_N', 'ARRAY_N' );

define( 'DUPLICATE_POST_FILE', '/var/www/html/wp-content/plugins/duplicate-post/duplicate-post.php' );
define( 'DUPLICATE_POST_CURRENT_VERSION', '4.0' );

if ( file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) === false ) {
	echo PHP_EOL, 'ERROR: Run `composer install` to generate the autoload files before running the unit tests.', PHP_EOL;
	exit( 1 );
}

require_once __DIR__ . '/../vendor/yoast/wp-test-utils/src/BrainMonkey/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';
