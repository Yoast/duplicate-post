<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests;

use Mockery;
use Yoast\WP\Duplicate_Post\Handler;
use Yoast\WP\Duplicate_Post\User_Interface;

/**
 * Test the User Interface class.
 */
class User_Interface_Test extends TestCase {

	/**
	 * Holds the handler.
	 *
	 * @var Handler|Mockery\MockInterface
	 */
	protected $handler;

	/**
	 * The instance.
	 *
	 * @var User_Interface
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->handler  = Mockery::mock( Handler::class );
		$this->instance = new User_Interface( $this->handler );
	}
}
