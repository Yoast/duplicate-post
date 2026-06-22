<?php

namespace Yoast\WP\Duplicate_Post\Tests\WP\Handlers;

use WP_REST_Request;
use Yoast\WP\Duplicate_Post\Handlers\Rest_API_Handler;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Class Rest_API_Handler_Test.
 *
 * @coversDefaultClass \Yoast\WP\Duplicate_Post\Handlers\Rest_API_Handler
 */
final class Rest_API_Handler_Test extends TestCase {

	/**
	 * Instance of the Rest_API_Handler class.
	 *
	 * @var Rest_API_Handler
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
	 * Setting up the instance of Rest_API_Handler.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->permissions_helper = new Permissions_Helper();
		$this->instance           = new Rest_API_Handler( $this->permissions_helper );

		// Suppress the "doing it wrong" notice for registering routes outside of rest_api_init.
		$this->setExpectedIncorrectUsage( 'register_rest_route' );

		// Register the routes for testing.
		$this->instance->register_routes();

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
	}

	/**
	 * Tests that the REST route is registered.
	 *
	 * @covers ::register_hooks
	 * @covers ::register_routes
	 *
	 * @return void
	 */
	public function test_route_is_registered() {
		$routes = \rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/duplicate-post/v1/original/(?P<post_id>\\d+)', $routes );
	}

	/**
	 * Tests that the original reference is removed successfully.
	 *
	 * @covers ::remove_original
	 * @covers ::can_remove_original
	 *
	 * @return void
	 */
	public function test_remove_original_success() {
		\wp_set_current_user( $this->admin_user_id );

		// Create a post with an original reference.
		$original_post = $this->factory->post->create();
		$copy_post     = $this->factory->post->create();
		\add_post_meta( $copy_post, '_dp_original', $original_post );

		// Verify the meta exists.
		$this->assertSame( $original_post, (int) \get_post_meta( $copy_post, '_dp_original', true ) );

		// Make the REST request.
		$request  = new WP_REST_Request( 'DELETE', '/duplicate-post/v1/original/' . $copy_post );
		$response = \rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );

		// Verify the meta is removed.
		$this->assertSame( '', \get_post_meta( $copy_post, '_dp_original', true ) );
	}

	/**
	 * Tests that removing original fails when user has no permission.
	 *
	 * @covers ::can_remove_original
	 *
	 * @return void
	 */
	public function test_remove_original_forbidden_for_subscriber() {
		\wp_set_current_user( $this->subscriber_user_id );

		// Create a post with an original reference.
		$original_post = $this->factory->post->create();
		$copy_post     = $this->factory->post->create();
		\add_post_meta( $copy_post, '_dp_original', $original_post );

		// Make the REST request.
		$request  = new WP_REST_Request( 'DELETE', '/duplicate-post/v1/original/' . $copy_post );
		$response = \rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );

		// Verify the meta still exists.
		$this->assertSame( $original_post, (int) \get_post_meta( $copy_post, '_dp_original', true ) );
	}

	/**
	 * Tests that removing original fails when not logged in.
	 *
	 * @covers ::can_remove_original
	 *
	 * @return void
	 */
	public function test_remove_original_forbidden_when_not_logged_in() {
		\wp_set_current_user( 0 );

		// Create a post with an original reference.
		$original_post = $this->factory->post->create();
		$copy_post     = $this->factory->post->create();
		\add_post_meta( $copy_post, '_dp_original', $original_post );

		// Make the REST request.
		$request  = new WP_REST_Request( 'DELETE', '/duplicate-post/v1/original/' . $copy_post );
		$response = \rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );

		// Verify the meta still exists.
		$this->assertSame( $original_post, (int) \get_post_meta( $copy_post, '_dp_original', true ) );
	}

	/**
	 * Tests that removing original fails for non-existent post.
	 *
	 * @covers ::can_remove_original
	 *
	 * @return void
	 */
	public function test_remove_original_post_not_found() {
		\wp_set_current_user( $this->admin_user_id );

		// Make the REST request with a non-existent post ID.
		$request  = new WP_REST_Request( 'DELETE', '/duplicate-post/v1/original/999999' );
		$response = \rest_do_request( $request );

		$this->assertSame( 404, $response->get_status() );
	}

	/**
	 * Tests that removing original fails for Rewrite & Republish copy.
	 *
	 * @covers ::can_remove_original
	 *
	 * @return void
	 */
	public function test_remove_original_forbidden_for_rewrite_republish_copy() {
		\wp_set_current_user( $this->admin_user_id );

		// Create a Rewrite & Republish copy.
		$original_post = $this->factory->post->create( [ 'post_status' => 'publish' ] );
		$copy_post     = $this->factory->post->create( [ 'post_status' => 'dp-rewrite-republish' ] );
		\add_post_meta( $copy_post, '_dp_original', $original_post );
		\add_post_meta( $copy_post, '_dp_is_rewrite_republish_copy', '1' );

		// Make the REST request.
		$request  = new WP_REST_Request( 'DELETE', '/duplicate-post/v1/original/' . $copy_post );
		$response = \rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );

		// Verify the meta still exists.
		$this->assertSame( $original_post, (int) \get_post_meta( $copy_post, '_dp_original', true ) );
	}

	/**
	 * Tests that removing original fails when no original meta exists.
	 *
	 * @covers ::remove_original
	 *
	 * @return void
	 */
	public function test_remove_original_no_meta_exists() {
		\wp_set_current_user( $this->admin_user_id );

		// Create a post without an original reference.
		$post = $this->factory->post->create();

		// Make the REST request.
		$request  = new WP_REST_Request( 'DELETE', '/duplicate-post/v1/original/' . $post );
		$response = \rest_do_request( $request );

		// delete_post_meta returns false when the meta doesn't exist, so we expect a 500 error.
		$this->assertSame( 500, $response->get_status() );
	}

	/**
	 * Tests that the permission callback validates post_id parameter.
	 *
	 * @covers ::register_routes
	 *
	 * @return void
	 */
	public function test_invalid_post_id_parameter() {
		\wp_set_current_user( $this->admin_user_id );

		// Make the REST request with an invalid post ID (non-numeric handled by route regex).
		$request  = new WP_REST_Request( 'DELETE', '/duplicate-post/v1/original/0' );
		$response = \rest_do_request( $request );

		// 0 is not valid per our validate_callback.
		$this->assertSame( 400, $response->get_status() );
	}
}
