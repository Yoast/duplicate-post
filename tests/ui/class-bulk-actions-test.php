<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests\UI;

use Brain\Monkey;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Tests\TestCase;
use Yoast\WP\Duplicate_Post\UI\Bulk_Actions;

/**
 * Test the Bulk_Actions class.
 */
class Bulk_Actions_Test extends TestCase {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * The instance.
	 *
	 * @var Bulk_Actions
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->permissions_helper = \Mockery::mock( Permissions_Helper::class );

		$this->instance = new Bulk_Actions( $this->permissions_helper );
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Bulk_Actions::register_hooks
	 */
	public function test_register_hooks() {
		$this->instance->register_hooks();

		$this->assertNotFalse( \has_action( 'admin_init', [ $this->instance, 'add_bulk_filters' ] ), 'Does not have expected admin_init action' );
	}

	/**
	 * Tests the add_bulk_filters function when a filter is added.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Bulk_Actions::add_bulk_filters
	 */
	public function test_add_bulk_filters_successful() {
		$duplicate_post_types_enabled = [ 'post', 'page' ];

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_bulkactions' )
			->andReturn( '1' );

		$this->permissions_helper->expects( 'is_current_user_allowed_to_copy' )
			->andReturnTrue();

		$this->permissions_helper->expects( 'get_enabled_post_types' )
			->andReturn( $duplicate_post_types_enabled );

		$this->instance->add_bulk_filters();
		$this->assertNotFalse( \has_filter( 'bulk_actions-edit-post', [ $this->instance, 'register_bulk_action' ] ), 'Does not have expected bulk_actions-edit-post filter' );
		$this->assertNotFalse( \has_filter( 'bulk_actions-edit-page', [ $this->instance, 'register_bulk_action' ] ), 'Does not have expected bulk_actions-edit-page filter' );
	}

	/**
	 * Tests the add_bulk_filters function when the user is not allowed to copy.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Bulk_Actions::add_bulk_filters
	 */
	public function test_add_bulk_filters_unsuccessful_user_not_allowed() {
		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_bulkactions' )
			->andReturn( '1' );

		$this->permissions_helper
			->expects( 'is_current_user_allowed_to_copy' )
			->andReturnFalse();

		$this->permissions_helper
			->expects( 'get_enabled_post_types' )
			->never();

		$this->instance->add_bulk_filters();
		$this->assertFalse( \has_filter( 'bulk_actions-edit-post', [ $this->instance, 'register_bulk_action' ] ), 'Does not have expected bulk_actions-edit-post filter' );
		$this->assertFalse( \has_filter( 'bulk_actions-edit-page', [ $this->instance, 'register_bulk_action' ] ), 'Does not have expected bulk_actions-edit-page filter' );
	}

	/**
	 * Tests the add_bulk_filters function when no post types are enabled.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Bulk_Actions::add_bulk_filters
	 */
	public function test_add_bulk_filters_unsuccessful_no_enabled_post_types() {
		$duplicate_post_types_enabled = [];

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_bulkactions' )
			->andReturn( '1' );

		$this->permissions_helper->expects( 'is_current_user_allowed_to_copy' )
			->andReturnTrue();

		$this->permissions_helper->expects( 'get_enabled_post_types' )
			->andReturn( $duplicate_post_types_enabled );

		$this->instance->add_bulk_filters();
		$this->assertFalse( \has_filter( 'bulk_actions-edit-post', [ $this->instance, 'register_bulk_action' ] ), 'Does not have expected bulk_actions-edit-post filter' );
		$this->assertFalse( \has_filter( 'bulk_actions-edit-page', [ $this->instance, 'register_bulk_action' ] ), 'Does not have expected bulk_actions-edit-page filter' );
	}

	/**
	 * Tests the register_bulk_action function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Bulk_Actions::register_bulk_action
	 */
	public function test_register_bulk_action() {
		$array = [
			'edit'  => 'Edit',
			'trash' => 'Move to Trash',
		];

		$this->assertEquals(
			[
				'edit'                                  => 'Edit',
				'trash'                                 => 'Move to Trash',
				'duplicate_post_bulk_clone'             => 'Clone',
				'duplicate_post_bulk_rewrite_republish' => 'Rewrite & Republish',
			],
			$this->instance->register_bulk_action( $array )
		);
	}
}
