<?php

namespace Yoast\WP\Duplicate_Post\Tests\WP\Handlers;

use Yoast\WP\Duplicate_Post\Handlers\Save_Post_Handler;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Class Save_Post_Handler_Test.
 *
 * @coversDefaultClass \Yoast\WP\Duplicate_Post\Handlers\Save_Post_Handler
 */
final class Save_Post_Handler_Test extends TestCase {

	/**
	 * Instance of the Save_Post_Handler class.
	 *
	 * @var Save_Post_Handler
	 */
	private $instance;

	/**
	 * Instance of the Permissions_Helper class.
	 *
	 * @var Permissions_Helper
	 */
	private $permissions_helper;

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	private $admin_user_id;

	/**
	 * Subscriber user ID.
	 *
	 * @var int
	 */
	private $subscriber_user_id;

	/**
	 * Setting up the instance of Save_Post_Handler.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->permissions_helper = new Permissions_Helper();
		$this->instance           = new Save_Post_Handler( $this->permissions_helper );

		// Create test users.
		$this->admin_user_id      = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->subscriber_user_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
	}

	/**
	 * Cleaning up after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		parent::tear_down();

		\wp_set_current_user( 0 );
		unset( $_POST['duplicate_post_remove_original'] );
	}

	/**
	 * Tests the constructor creates instance correctly.
	 *
	 * @covers ::__construct
	 *
	 * @return void
	 */
	public function test_constructor() {
		$this->assertInstanceOf( Save_Post_Handler::class, $this->instance );
	}

	/**
	 * Tests that register_hooks adds the save_post action when meta box option is enabled.
	 *
	 * @covers ::register_hooks
	 *
	 * @return void
	 */
	public function test_register_hooks_with_meta_box_enabled() {
		\update_option( 'duplicate_post_show_original_meta_box', '1' );
		\update_option( 'duplicate_post_show_original_column', '0' );

		$this->instance->register_hooks();

		$this->assertNotFalse( \has_action( 'save_post', [ $this->instance, 'delete_on_save_post' ] ) );
	}

	/**
	 * Tests that register_hooks adds the save_post action when column option is enabled.
	 *
	 * @covers ::register_hooks
	 *
	 * @return void
	 */
	public function test_register_hooks_with_column_enabled() {
		\update_option( 'duplicate_post_show_original_meta_box', '0' );
		\update_option( 'duplicate_post_show_original_column', '1' );

		$this->instance->register_hooks();

		$this->assertNotFalse( \has_action( 'save_post', [ $this->instance, 'delete_on_save_post' ] ) );
	}

	/**
	 * Tests that register_hooks does not add the save_post action when both options are disabled.
	 *
	 * @covers ::register_hooks
	 *
	 * @return void
	 */
	public function test_register_hooks_with_options_disabled() {
		\update_option( 'duplicate_post_show_original_meta_box', '0' );
		\update_option( 'duplicate_post_show_original_column', '0' );

		$this->instance->register_hooks();

		$this->assertFalse( \has_action( 'save_post', [ $this->instance, 'delete_on_save_post' ] ) );
	}

	/**
	 * Tests that delete_on_save_post removes the original meta when checkbox is checked.
	 *
	 * @covers ::delete_on_save_post
	 *
	 * @return void
	 */
	public function test_delete_on_save_post_removes_meta_when_checkbox_checked() {
		\wp_set_current_user( $this->admin_user_id );

		// Create a post with an original reference.
		$original_post = $this->factory->post->create();
		$copy_post     = $this->factory->post->create();
		\add_post_meta( $copy_post, '_dp_original', $original_post );

		// Verify the meta exists.
		$this->assertEquals( $original_post, \get_post_meta( $copy_post, '_dp_original', true ) );

		// Set the checkbox.
		$_POST['duplicate_post_remove_original'] = '1';

		// Call the method.
		$this->instance->delete_on_save_post( $copy_post );

		// Verify the meta is removed.
		$this->assertEmpty( \get_post_meta( $copy_post, '_dp_original', true ) );
	}

	/**
	 * Tests that delete_on_save_post does not remove meta when checkbox is not checked.
	 *
	 * @covers ::delete_on_save_post
	 *
	 * @return void
	 */
	public function test_delete_on_save_post_keeps_meta_when_checkbox_not_checked() {
		\wp_set_current_user( $this->admin_user_id );

		// Create a post with an original reference.
		$original_post = $this->factory->post->create();
		$copy_post     = $this->factory->post->create();
		\add_post_meta( $copy_post, '_dp_original', $original_post );

		// Verify the meta exists.
		$this->assertEquals( $original_post, \get_post_meta( $copy_post, '_dp_original', true ) );

		// Don't set the checkbox.
		unset( $_POST['duplicate_post_remove_original'] );

		// Call the method.
		$this->instance->delete_on_save_post( $copy_post );

		// Verify the meta still exists.
		$this->assertEquals( $original_post, \get_post_meta( $copy_post, '_dp_original', true ) );
	}

	/**
	 * Tests that delete_on_save_post does nothing when user cannot edit post.
	 *
	 * @covers ::delete_on_save_post
	 *
	 * @return void
	 */
	public function test_delete_on_save_post_does_nothing_for_unauthorized_user() {
		\wp_set_current_user( $this->subscriber_user_id );

		// Create a post with an original reference.
		$original_post = $this->factory->post->create( [ 'post_author' => $this->admin_user_id ] );
		$copy_post     = $this->factory->post->create( [ 'post_author' => $this->admin_user_id ] );
		\add_post_meta( $copy_post, '_dp_original', $original_post );

		// Verify the meta exists.
		$this->assertEquals( $original_post, \get_post_meta( $copy_post, '_dp_original', true ) );

		// Set the checkbox.
		$_POST['duplicate_post_remove_original'] = '1';

		// Call the method.
		$this->instance->delete_on_save_post( $copy_post );

		// Verify the meta still exists (user has no permission).
		$this->assertEquals( $original_post, \get_post_meta( $copy_post, '_dp_original', true ) );
	}

	/**
	 * Tests that delete_on_save_post does nothing for non-existent post.
	 *
	 * @covers ::delete_on_save_post
	 *
	 * @return void
	 */
	public function test_delete_on_save_post_does_nothing_for_nonexistent_post() {
		$this->expectNotToPerformAssertions();

		\wp_set_current_user( $this->admin_user_id );

		// Set the checkbox.
		$_POST['duplicate_post_remove_original'] = '1';

		// Call the method with a non-existent post ID - should not throw error.
		$this->instance->delete_on_save_post( 999999 );

		// If we get here without errors, the test passes.
	}

	/**
	 * Tests that delete_on_save_post does nothing for Rewrite & Republish copy.
	 *
	 * @covers ::delete_on_save_post
	 *
	 * @return void
	 */
	public function test_delete_on_save_post_does_nothing_for_rewrite_republish_copy() {
		\wp_set_current_user( $this->admin_user_id );

		// Create the original post.
		$original_post = $this->factory->post->create( [ 'post_status' => 'publish' ] );

		// Create a Rewrite & Republish copy (with special post status and meta).
		$copy_post = $this->factory->post->create( [ 'post_status' => 'dp-rewrite-republish' ] );
		\add_post_meta( $copy_post, '_dp_original', $original_post );
		\add_post_meta( $copy_post, '_dp_is_rewrite_republish_copy', '1' );

		// Verify the meta exists.
		$this->assertEquals( $original_post, \get_post_meta( $copy_post, '_dp_original', true ) );

		// Set the checkbox.
		$_POST['duplicate_post_remove_original'] = '1';

		// Call the method.
		$this->instance->delete_on_save_post( $copy_post );

		// Verify the meta still exists (R&R copy should not be modified).
		$this->assertEquals( $original_post, \get_post_meta( $copy_post, '_dp_original', true ) );
	}
}
