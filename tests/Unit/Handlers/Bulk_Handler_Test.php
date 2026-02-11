<?php

namespace Yoast\WP\Duplicate_Post\Tests\Unit\Handlers;

use Brain\Monkey;
use Mockery;
use WP_Post;
use Yoast\WP\Duplicate_Post\Handlers\Bulk_Handler;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Post_Duplicator;
use Yoast\WP\Duplicate_Post\Tests\Unit\TestCase;

/**
 * Test the Bulk_Handler class.
 */
final class Bulk_Handler_Test extends TestCase {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper|Mockery\Mock
	 */
	protected $permissions_helper;

	/**
	 * Holds the post duplicator.
	 *
	 * @var Post_Duplicator|Mockery\Mock
	 */
	protected $post_duplicator;

	/**
	 * The instance.
	 *
	 * @var Bulk_Handler|Mockery\Mock
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 *
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		$this->post_duplicator    = Mockery::mock( Post_Duplicator::class );
		$this->permissions_helper = Mockery::mock( Permissions_Helper::class );

		$this->instance = new Bulk_Handler( $this->post_duplicator, $this->permissions_helper );
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Bulk_Handler::__construct
	 *
	 * @return void
	 */
	public function test_constructor() {
		$this->assertInstanceOf(
			Post_Duplicator::class,
			$this->getPropertyValue( $this->instance, 'post_duplicator' )
		);

		$this->assertInstanceOf(
			Permissions_Helper::class,
			$this->getPropertyValue( $this->instance, 'permissions_helper' )
		);
	}

	/**
	 * Tests that clone_bulk_action_handler returns early when action is not clone.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Bulk_Handler::clone_bulk_action_handler
	 *
	 * @return void
	 */
	public function test_clone_bulk_action_handler_returns_early_for_wrong_action() {
		$redirect_to = 'http://example.com/wp-admin/edit.php';

		$result = $this->instance->clone_bulk_action_handler( $redirect_to, 'trash', [ 1, 2 ] );

		$this->assertEquals( $redirect_to, $result );
	}

	/**
	 * Tests that clone_bulk_action_handler skips posts user cannot edit.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Bulk_Handler::clone_bulk_action_handler
	 *
	 * @return void
	 */
	public function test_clone_bulk_action_handler_skips_posts_user_cannot_edit() {
		$redirect_to      = 'http://example.com/wp-admin/edit.php';
		$post1            = Mockery::mock( WP_Post::class );
		$post1->ID        = 1;
		$post1->post_type = 'post';
		$post2            = Mockery::mock( WP_Post::class );
		$post2->ID        = 2;
		$post2->post_type = 'post';

		Monkey\Functions\expect( 'get_post' )
			->with( 1 )
			->andReturn( $post1 );

		Monkey\Functions\expect( 'get_post' )
			->with( 2 )
			->andReturn( $post2 );

		$this->permissions_helper
			->allows( 'is_rewrite_and_republish_copy' )
			->with( $post1 )
			->andReturn( false );

		$this->permissions_helper
			->allows( 'is_rewrite_and_republish_copy' )
			->with( $post2 )
			->andReturn( false );

		Monkey\Functions\expect( 'get_option' )
			->with( 'duplicate_post_copychildren' )
			->andReturn( 0 );

		Monkey\Functions\expect( 'is_post_type_hierarchical' )
			->with( 'post' )
			->andReturn( false );

		Monkey\Functions\expect( 'current_user_can' )
			->with( 'edit_post', 1 )
			->andReturn( false );

		Monkey\Functions\expect( 'current_user_can' )
			->with( 'edit_post', 2 )
			->andReturn( false );

		Monkey\Functions\expect( 'add_query_arg' )
			->andReturnUsing(
				static function ( $key, $value, $url ) {
					return $url . ( ( \strpos( $url, '?' ) === false ) ? '?' : '&' ) . $key . '=' . $value;
				}
			);

		$result = $this->instance->clone_bulk_action_handler( $redirect_to, 'duplicate_post_bulk_clone', [ 1, 2 ] );

		$this->assertStringContainsString( 'bulk_cloned=0', $result );
		$this->assertStringContainsString( 'bulk_cloned_skipped=2', $result );
	}

	/**
	 * Tests that clone_bulk_action_handler processes posts user can edit.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Bulk_Handler::clone_bulk_action_handler
	 *
	 * @return void
	 */
	public function test_clone_bulk_action_handler_processes_posts_user_can_edit() {
		$redirect_to     = 'http://example.com/wp-admin/edit.php';
		$post            = Mockery::mock( WP_Post::class );
		$post->ID        = 1;
		$post->post_type = 'post';

		Monkey\Functions\expect( 'current_user_can' )
			->with( 'edit_post', 1 )
			->andReturn( true );

		Monkey\Functions\expect( 'get_post' )
			->with( 1 )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturn( false );

		Monkey\Functions\expect( 'get_option' )
			->with( 'duplicate_post_copychildren' )
			->andReturn( 0 );

		Monkey\Functions\expect( 'is_post_type_hierarchical' )
			->with( 'post' )
			->andReturn( false );

		Monkey\Functions\expect( 'duplicate_post_create_duplicate' )
			->with( $post )
			->andReturn( 2 );

		Monkey\Functions\expect( 'is_wp_error' )
			->with( 2 )
			->andReturn( false );

		Monkey\Functions\expect( 'add_query_arg' )
			->with( 'bulk_cloned', 1, $redirect_to )
			->andReturn( $redirect_to . '?bulk_cloned=1' );

		$result = $this->instance->clone_bulk_action_handler( $redirect_to, 'duplicate_post_bulk_clone', [ 1 ] );

		$this->assertEquals( $redirect_to . '?bulk_cloned=1', $result );
	}

	/**
	 * Tests that clone_bulk_action_handler does not increment counter when duplication returns WP_Error.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Bulk_Handler::clone_bulk_action_handler
	 *
	 * @return void
	 */
	public function test_clone_bulk_action_handler_handles_wp_error() {
		$redirect_to     = 'http://example.com/wp-admin/edit.php';
		$post            = Mockery::mock( WP_Post::class );
		$post->ID        = 1;
		$post->post_type = 'post';
		$wp_error        = Mockery::mock( 'WP_Error' );

		Monkey\Functions\expect( 'current_user_can' )
			->with( 'edit_post', 1 )
			->andReturn( true );

		Monkey\Functions\expect( 'get_post' )
			->with( 1 )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturn( false );

		Monkey\Functions\expect( 'get_option' )
			->with( 'duplicate_post_copychildren' )
			->andReturn( 0 );

		Monkey\Functions\expect( 'is_post_type_hierarchical' )
			->with( 'post' )
			->andReturn( false );

		Monkey\Functions\expect( 'duplicate_post_create_duplicate' )
			->with( $post )
			->andReturn( $wp_error );

		Monkey\Functions\expect( 'is_wp_error' )
			->with( $wp_error )
			->andReturn( true );

		Monkey\Functions\expect( 'add_query_arg' )
			->andReturnUsing(
				static function ( $key, $value, $url ) {
					return $url . ( ( \strpos( $url, '?' ) === false ) ? '?' : '&' ) . $key . '=' . $value;
				}
			);

		$result = $this->instance->clone_bulk_action_handler( $redirect_to, 'duplicate_post_bulk_clone', [ 1 ] );

		$this->assertStringContainsString( 'bulk_cloned=0', $result );
	}

	/**
	 * Tests that rewrite_bulk_action_handler returns early when action is not rewrite.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Bulk_Handler::rewrite_bulk_action_handler
	 *
	 * @return void
	 */
	public function test_rewrite_bulk_action_handler_returns_early_for_wrong_action() {
		$redirect_to = 'http://example.com/wp-admin/edit.php';

		$result = $this->instance->rewrite_bulk_action_handler( $redirect_to, 'trash', [ 1, 2 ] );

		$this->assertEquals( $redirect_to, $result );
	}

	/**
	 * Tests that rewrite_bulk_action_handler skips posts user cannot edit.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Bulk_Handler::rewrite_bulk_action_handler
	 *
	 * @return void
	 */
	public function test_rewrite_bulk_action_handler_skips_posts_user_cannot_edit() {
		$redirect_to        = 'http://example.com/wp-admin/edit.php';
		$post1              = Mockery::mock( WP_Post::class );
		$post1->ID          = 1;
		$post1->post_status = 'publish';
		$post2              = Mockery::mock( WP_Post::class );
		$post2->ID          = 2;
		$post2->post_status = 'publish';

		Monkey\Functions\expect( 'get_post' )
			->with( 1 )
			->andReturn( $post1 );

		Monkey\Functions\expect( 'get_post' )
			->with( 2 )
			->andReturn( $post2 );

		$this->permissions_helper
			->allows( 'should_rewrite_and_republish_be_allowed' )
			->with( $post1 )
			->andReturn( true );

		$this->permissions_helper
			->allows( 'should_rewrite_and_republish_be_allowed' )
			->with( $post2 )
			->andReturn( true );

		Monkey\Functions\expect( 'current_user_can' )
			->with( 'edit_post', 1 )
			->andReturn( false );

		Monkey\Functions\expect( 'current_user_can' )
			->with( 'edit_post', 2 )
			->andReturn( false );

		Monkey\Functions\expect( 'add_query_arg' )
			->andReturnUsing(
				static function ( $key, $value, $url ) {
					return $url . ( ( \strpos( $url, '?' ) === false ) ? '?' : '&' ) . $key . '=' . $value;
				}
			);

		$result = $this->instance->rewrite_bulk_action_handler( $redirect_to, 'duplicate_post_bulk_rewrite_republish', [ 1, 2 ] );

		$this->assertStringContainsString( 'bulk_rewriting=0', $result );
		$this->assertStringContainsString( 'bulk_rewriting_skipped=2', $result );
	}

	/**
	 * Tests that rewrite_bulk_action_handler processes posts user can edit.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Bulk_Handler::rewrite_bulk_action_handler
	 *
	 * @return void
	 */
	public function test_rewrite_bulk_action_handler_processes_posts_user_can_edit() {
		$redirect_to       = 'http://example.com/wp-admin/edit.php';
		$post              = Mockery::mock( WP_Post::class );
		$post->ID          = 1;
		$post->post_status = 'publish';

		Monkey\Functions\expect( 'current_user_can' )
			->with( 'edit_post', 1 )
			->andReturn( true );

		Monkey\Functions\expect( 'get_post' )
			->with( 1 )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'should_rewrite_and_republish_be_allowed' )
			->with( $post )
			->andReturn( true );

		$this->post_duplicator
			->expects( 'create_duplicate_for_rewrite_and_republish' )
			->with( $post )
			->andReturn( 2 );

		Monkey\Functions\expect( 'is_wp_error' )
			->with( 2 )
			->andReturn( false );

		Monkey\Functions\expect( 'add_query_arg' )
			->with( 'bulk_rewriting', 1, $redirect_to )
			->andReturn( $redirect_to . '?bulk_rewriting=1' );

		$result = $this->instance->rewrite_bulk_action_handler( $redirect_to, 'duplicate_post_bulk_rewrite_republish', [ 1 ] );

		$this->assertEquals( $redirect_to . '?bulk_rewriting=1', $result );
	}
}
