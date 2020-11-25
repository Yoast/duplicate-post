<?php
/**
 * Duplicate Post base test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests;

use Brain\Monkey;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * TestCase base class.
 */
abstract class TestCase extends BaseTestCase {

	/**
	 * Test setup.
	 */
	protected function setUp() {

		parent::setUp();
		Monkey\setUp();

		Monkey\Functions\stubs(
			array(
				// Passing "null" makes the function return its first argument.
				'esc_attr'       => null,
				'esc_html'       => null,
				'esc_textarea'   => null,
				'__'             => null,
				'_n'             => function( $single, $plural, $number ) {
					if ( $number === 1 ) {
						return $single;
					}

					return $plural;
				},
				'_x'             => null,
				'esc_html__'     => null,
				'esc_html_x'     => null,
				'esc_html_e'     => null,
				'esc_attr__'     => null,
				'esc_attr_x'     => null,
				'esc_url'        => null,
				'esc_url_raw'    => null,
				'is_multisite'   => false,
				'site_url'       => 'https://www.example.org',
				'wp_slash'       => null,
				'wp_unslash'     => function( $value ) {
					return \is_string( $value ) ? \stripslashes( $value ) : $value;
				},
				'absint'         => function( $value ) {
					return \abs( \intval( $value ) );
				},
				'wp_parse_args'  => function ( $settings, $defaults ) {
					return \array_merge( $defaults, $settings );
				},
			)
		);
	}

	/**
	 * Test tear down.
	 */
	protected function tearDown() {
		Monkey\tearDown();
		parent::tearDown();
	}
}
