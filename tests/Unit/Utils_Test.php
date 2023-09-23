<?php

namespace Yoast\WP\Duplicate_Post\Tests\Unit;

use Yoast\WP\Duplicate_Post\Utils;

/**
 * Test the Utils class.
 */
final class Utils_Test extends TestCase {

	/**
	 * Tests the flatten_version function.
	 *
	 * @covers       \Yoast\WP\Duplicate_Post\Utils::flatten_version
	 * @dataProvider flatten_version_provider
	 *
	 * @param string $original Version number.
	 * @param string $expected Expected output.
	 */
	public function test_flatten_version( $original, $expected ) {
		$this->assertSame( $expected, Utils::flatten_version( $original ) );
	}

	/**
	 * Data provider for test_flatten_version.
	 *
	 * @return array
	 */
	public static function flatten_version_provider() {
		return [
			[ '3.0', '300' ],
			[ '1.4', '140' ],
			[ '', '' ],
			[ '3.0.0', '300' ],
			[ '25.1456.140', '251456140' ],
			[ '1', '1' ],
		];
	}

	/**
	 * Tests the addslashes_to_strings_only function.
	 *
	 * @covers       \Yoast\WP\Duplicate_Post\Utils::addslashes_to_strings_only
	 * @dataProvider addslashes_to_strings_only_provider
	 *
	 * @param mixed $original Input value.
	 * @param mixed $expected Expected output.
	 */
	public function test_addslashes_to_strings_only( $original, $expected ) {
		$this->assertSame( $expected, Utils::addslashes_to_strings_only( $original ) );
	}

	/**
	 * Data provider for test_addslashes_to_strings_only.
	 *
	 * @return array
	 */
	public static function addslashes_to_strings_only_provider() {
		return [
			[ "O'Reilly", "O\'Reilly" ],
			[ 'A string with "quotes"', 'A string with \"quotes\"' ],
			[ 'C:\\Windows', 'C:\\\\Windows' ],
			[ 'A string with NUL \0 in the middle', 'A string with NUL \\\0 in the middle' ],
			[ 1000, 1000 ],
			[ 999.99, 999.99 ],
			[ true, true ],
			[ [ 'must', 'remain', 'array' ], [ 'must', 'remain', 'array' ] ],
		];
	}
}
