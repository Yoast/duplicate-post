<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests;

use Yoast\WP\Duplicate_Post\Duplicate_Post_User_Interface;

/**
 * Test the User Interface class.
 */
class Duplicate_Post_User_Interface_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Duplicate_Post_User_Interface
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->instance = new Duplicate_Post_User_Interface();
	}

	/**
	 * Tests the duplicate_post_flatten_version function.
	 *
	 * @covers       Duplicate_Post_User_Interface::duplicate_post_flatten_version
	 * @dataProvider flatten_version_provider
	 *
	 * @param string $original Version number.
	 * @param string $expected Expected output.
	 */
	public function test_duplicate_post_flatten_version( $original, $expected ) {
		$this->assertEquals( $expected, $this->instance->duplicate_post_flatten_version( $original ) );
	}

	/**
	 * Data provider for test_flatten_version.
	 *
	 * @return array
	 */
	public function flatten_version_provider() {
		return array(
			array( 'abc', '300' ),
			array( '1.4', '140' ),
			array( '', '' ),
			array( '3.0.0', '300' ),
			array( '25.1456.140', '251456140' ),
			array( '1', '1' ),
		);
	}
}
