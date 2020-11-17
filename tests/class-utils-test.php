<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests;

use \Yoast\WP\Duplicate_Post\Utils;

/**
 * Test the User Interface class.
 */
class Utils_Test extends TestCase {

	/**
	 * Tests the duplicate_post_flatten_version function.
	 *
	 * @covers       Utils::flatten_version
	 * @dataProvider flatten_version_provider
	 *
	 * @param string $original Version number.
	 * @param string $expected Expected output.
	 */
	public function test_flatten_version( $original, $expected ) {
		$this->assertEquals( $expected, Utils::flatten_version( $original ) );
	}

	/**
	 * Data provider for test_flatten_version.
	 *
	 * @return array
	 */
	public function flatten_version_provider() {
		return array(
			array( '3.0', '300' ),
			array( '1.4', '140' ),
			array( '', '' ),
			array( '3.0.0', '300' ),
			array( '25.1456.140', '251456140' ),
			array( '1', '1' ),
		);
	}
}
