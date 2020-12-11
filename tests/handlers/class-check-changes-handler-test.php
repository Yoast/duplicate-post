<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests\Handlers;

use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Tests\TestCase;
use Yoast\WP\Duplicate_Post\Handlers\Check_Changes_Handler;

/**
 * Test the Check_Changes_Handler class.
 */
class Check_Changes_Handler_Test extends TestCase {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * The instance.
	 *
	 * @var Check_Changes_Handler
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->permissions_helper = \Mockery::mock( Permissions_Helper::class );

		$this->instance = new Check_Changes_Handler( $this->permissions_helper );
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Check_Changes_Handler::__construct
	 */
	public function test_constructor() {
		$this->assertAttributeInstanceOf( Permissions_Helper::class, 'permissions_helper', $this->instance );
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Check_Changes_Handler::register_hooks
	 */
	public function test_register_hooks() {
		$this->instance->register_hooks();

		$this->assertNotFalse( \has_action( 'admin_action_duplicate_post_check_changes', [ $this->instance, 'check_changes_action_handler' ] ), 'Does not have expected admin_action_duplicate_post_check_changes action' );
	}
}
