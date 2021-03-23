<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Yoast\WP\Duplicate_Post\Tests
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Reason: CS configuration is not complete yet.
define( 'ABSPATH', true );

define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'WEEK_IN_SECONDS', 604800 );
define( 'MONTH_IN_SECONDS', 2592000 );
define( 'YEAR_IN_SECONDS', 31536000 );

define( 'OBJECT', 'OBJECT' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'ARRAY_N', 'ARRAY_N' );

define( 'DUPLICATE_POST_FILE', '/var/www/html/wp-content/plugins/duplicate-post/duplicate-post.php' );
define( 'DUPLICATE_POST_CURRENT_VERSION', '4.0' );
// phpcs:enable

// phpcs:disable PHPCompatibility.FunctionUse.NewFunctions -- Reason: Properly wrapped within a check.
if ( function_exists( 'opcache_reset' ) ) {
	opcache_reset();
}
// phpcs:enable

if ( file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) === false ) {
	echo PHP_EOL, 'ERROR: Run `composer install` to generate the autoload files before running the unit tests.', PHP_EOL;
	exit( 1 );
}

require_once __DIR__ . '/../vendor/autoload.php';
