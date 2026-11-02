<?php

namespace Yoast\WP\Duplicate_Post\Tests\Unit\Handlers;

use Brain\Monkey;
use Mockery;
use RuntimeException;
use WP_Post;
use Yoast\WP\Duplicate_Post\Handlers\Link_Handler;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Post_Duplicator;
use Yoast\WP\Duplicate_Post\Tests\Unit\TestCase;

/**
 * Test the Link_Handler class.
 */
final class Link_Handler_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Link_Handler
	 */
	protected $instance;

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper|Mockery\Mock
	 */
	protected $permissions_helper;

	/**
	 * Sets the instance.
	 *
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		$this->permissions_helper = Mockery::mock( Permissions_Helper::class );
		$this->instance           = new Link_Handler(
			Mockery::mock( Post_Duplicator::class ),
			$this->permissions_helper,
		);
	}

	/**
	 * Tears down the test.
	 *
	 * @return void
	 */
	protected function tear_down() {
		parent::tear_down();

		unset( $_GET['post'] );
	}

	/**
	 * Tests that the clone handler redirects to a relative post list URL.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Link_Handler::clone_link_action_handler
	 *
	 * @return void
	 */
	public function test_clone_link_action_handler_uses_relative_redirect_url() {
		$post            = Mockery::mock( WP_Post::class );
		$post->ID        = 123;
		$post->post_type = 'post';
		$_GET['post']    = '123';

		$this->permissions_helper
			->expects( 'is_current_user_allowed_to_copy' )
			->andReturn( true );
		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturn( false );

		Monkey\Functions\expect( '\\check_admin_referer' )
			->with( 'duplicate_post_clone_123' );
		Monkey\Functions\expect( '\\get_post' )
			->with( 123 )
			->andReturn( $post );
		Monkey\Functions\expect( '\\duplicate_post_create_duplicate' )
			->with( $post )
			->andReturn( 456 );
		Monkey\Functions\expect( '\\is_wp_error' )
			->with( 456 )
			->andReturn( false );
		Monkey\Functions\expect( '\\wp_get_referer' )
			->andReturn( 'https://admin.example.test/wp-admin/edit.php' );
		Monkey\Functions\expect( '\\remove_query_arg' )
			->with( [ 'trashed', 'untrashed', 'deleted', 'cloned', 'ids' ], 'https://admin.example.test/wp-admin/edit.php' )
			->andReturn( 'https://admin.example.test/wp-admin/edit.php' );
		Monkey\Functions\expect( '\\wp_make_link_relative' )
			->with( 'https://admin.example.test/wp-admin/edit.php' )
			->andReturn( '/wp-admin/edit.php' );
		Monkey\Functions\expect( '\\add_query_arg' )
			->with(
				[
					'cloned' => 1,
					'ids'    => 123,
				],
				'/wp-admin/edit.php',
			)
			->andReturn( '/wp-admin/edit.php?cloned=1&ids=123' );
		Monkey\Functions\expect( '\\wp_safe_redirect' )
			->with( '/wp-admin/edit.php?cloned=1&ids=123' )
			->andThrow( new RuntimeException() );

		$this->expectException( RuntimeException::class );
		$this->instance->clone_link_action_handler();
	}

	/**
	 * Tests that the clone handler redirects to a relative post list URL when the referer is rejected.
	 *
	 * This covers installs where the WordPress Address and the Site Address are on different
	 * hosts: wp_get_referer() returns false and the admin_url() fallback must stay usable.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Link_Handler::clone_link_action_handler
	 *
	 * @return void
	 */
	public function test_clone_link_action_handler_falls_back_to_relative_post_list_url() {
		$post            = Mockery::mock( WP_Post::class );
		$post->ID        = 123;
		$post->post_type = 'post';
		$_GET['post']    = '123';

		$this->permissions_helper
			->expects( 'is_current_user_allowed_to_copy' )
			->andReturn( true );
		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturn( false );

		Monkey\Functions\expect( '\\check_admin_referer' )
			->with( 'duplicate_post_clone_123' );
		Monkey\Functions\expect( '\\get_post' )
			->with( 123 )
			->andReturn( $post );
		Monkey\Functions\expect( '\\duplicate_post_create_duplicate' )
			->with( $post )
			->andReturn( 456 );
		Monkey\Functions\expect( '\\is_wp_error' )
			->with( 456 )
			->andReturn( false );
		Monkey\Functions\expect( '\\wp_get_referer' )
			->andReturn( false );
		Monkey\Functions\expect( '\\admin_url' )
			->with( 'edit.php' )
			->andReturn( 'https://admin.example.test/wp-admin/edit.php' );
		Monkey\Functions\expect( '\\add_query_arg' )
			->with( 'post_type', 'post', 'https://admin.example.test/wp-admin/edit.php' )
			->andReturn( 'https://admin.example.test/wp-admin/edit.php?post_type=post' );
		Monkey\Functions\expect( '\\wp_make_link_relative' )
			->with( 'https://admin.example.test/wp-admin/edit.php?post_type=post' )
			->andReturn( '/wp-admin/edit.php?post_type=post' );
		Monkey\Functions\expect( '\\add_query_arg' )
			->with(
				[
					'cloned' => 1,
					'ids'    => 123,
				],
				'/wp-admin/edit.php?post_type=post',
			)
			->andReturn( '/wp-admin/edit.php?post_type=post&cloned=1&ids=123' );
		Monkey\Functions\expect( '\\wp_safe_redirect' )
			->with( '/wp-admin/edit.php?post_type=post&cloned=1&ids=123' )
			->andThrow( new RuntimeException() );

		$this->expectException( RuntimeException::class );
		$this->instance->clone_link_action_handler();
	}
}
