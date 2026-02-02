<?php

namespace Yoast\WP\Duplicate_Post\Tests\Unit\Handlers;

use Brain\Monkey\Functions;
use Mockery;
use WP_Post;
use Yoast\WP\Duplicate_Post\Handlers\Save_Post_Handler;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Tests\Unit\TestCase;

/**
 * Test the Save_Post_Handler class.
 *
 * @coversDefaultClass \Yoast\WP\Duplicate_Post\Handlers\Save_Post_Handler
 */
final class Save_Post_Handler_Test extends TestCase {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * The instance.
	 *
	 * @var Save_Post_Handler
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 *
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		$this->permissions_helper = Mockery::mock( Permissions_Helper::class );

		$this->instance = new Save_Post_Handler( $this->permissions_helper );
	}

	/**
	 * Tears down the test.
	 *
	 * @return void
	 */
	protected function tear_down() {
		parent::tear_down();

		unset( $_POST['duplicate_post_remove_original'] );
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers ::__construct
	 *
	 * @return void
	 */
	public function test_constructor() {
		$this->assertInstanceOf(
			Permissions_Helper::class,
			$this->getPropertyValue( $this->instance, 'permissions_helper' )
		);
	}

	/**
	 * Tests the registration of the hooks when both options are disabled.
	 *
	 * @covers ::register_hooks
	 *
	 * @return void
	 */
	public function test_register_hooks_no_options_enabled() {
		Functions\expect( 'get_option' )
			->with( 'duplicate_post_show_original_meta_box' )
			->andReturn( '0' );

		Functions\expect( 'get_option' )
			->with( 'duplicate_post_show_original_column' )
			->andReturn( '0' );

		$this->instance->register_hooks();

		$this->assertFalse( \has_action( 'save_post', [ $this->instance, 'delete_on_save_post' ] ) );
	}

	/**
	 * Tests the registration of the hooks when meta box option is enabled.
	 *
	 * @covers ::register_hooks
	 *
	 * @return void
	 */
	public function test_register_hooks_with_meta_box_option_enabled() {
		Functions\expect( 'get_option' )
			->with( 'duplicate_post_show_original_meta_box' )
			->andReturn( '1' );

		$this->instance->register_hooks();

		$this->assertNotFalse( \has_action( 'save_post', [ $this->instance, 'delete_on_save_post' ] ) );
	}

	/**
	 * Tests the registration of the hooks when column option is enabled.
	 *
	 * @covers ::register_hooks
	 *
	 * @return void
	 */
	public function test_register_hooks_with_column_option_enabled() {
		Functions\expect( 'get_option' )
			->once()
			->with( 'duplicate_post_show_original_meta_box' )
			->andReturn( '0' );

		Functions\expect( 'get_option' )
			->once()
			->with( 'duplicate_post_show_original_column' )
			->andReturn( '1' );

		$this->instance->register_hooks();

		$this->assertNotFalse( \has_action( 'save_post', [ $this->instance, 'delete_on_save_post' ] ) );
	}

	/**
	 * Tests that delete_on_save_post returns early during autosave.
	 *
	 * @covers ::delete_on_save_post
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_delete_on_save_post_returns_during_autosave() {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- This is a WordPress core constant.
		\define( 'DOING_AUTOSAVE', true );

		Functions\expect( 'current_user_can' )->never();
		Functions\expect( 'get_post' )->never();
		Functions\expect( 'delete_post_meta' )->never();

		$this->instance->delete_on_save_post( 123 );
	}

	/**
	 * Tests that delete_on_save_post returns early when user cannot edit post.
	 *
	 * @covers ::delete_on_save_post
	 *
	 * @return void
	 */
	public function test_delete_on_save_post_returns_when_user_cannot_edit() {
		Functions\expect( 'current_user_can' )
			->with( 'edit_post', 123 )
			->andReturn( false );

		Functions\expect( 'get_post' )->never();
		Functions\expect( 'delete_post_meta' )->never();

		$this->instance->delete_on_save_post( 123 );
	}

	/**
	 * Tests that delete_on_save_post returns early when post does not exist.
	 *
	 * @covers ::delete_on_save_post
	 *
	 * @return void
	 */
	public function test_delete_on_save_post_returns_when_post_not_found() {
		Functions\expect( 'current_user_can' )
			->with( 'edit_post', 123 )
			->andReturn( true );

		Functions\expect( 'get_post' )
			->with( 123 )
			->andReturn( null );

		$this->permissions_helper->shouldNotReceive( 'is_rewrite_and_republish_copy' );
		Functions\expect( 'delete_post_meta' )->never();

		$this->instance->delete_on_save_post( 123 );
	}

	/**
	 * Tests that delete_on_save_post returns early for Rewrite & Republish copy.
	 *
	 * @covers ::delete_on_save_post
	 *
	 * @return void
	 */
	public function test_delete_on_save_post_returns_for_rewrite_republish_copy() {
		$post     = Mockery::mock( WP_Post::class );
		$post->ID = 123;

		Functions\expect( 'current_user_can' )
			->with( 'edit_post', 123 )
			->andReturn( true );

		Functions\expect( 'get_post' )
			->with( 123 )
			->andReturn( $post );

		$this->permissions_helper
			->shouldReceive( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturn( true );

		Functions\expect( 'delete_post_meta' )->never();

		$this->instance->delete_on_save_post( 123 );
	}

	/**
	 * Tests that delete_on_save_post does nothing when checkbox is not checked.
	 *
	 * @covers ::delete_on_save_post
	 *
	 * @return void
	 */
	public function test_delete_on_save_post_does_nothing_without_checkbox() {
		$post     = Mockery::mock( WP_Post::class );
		$post->ID = 123;

		Functions\expect( 'current_user_can' )
			->with( 'edit_post', 123 )
			->andReturn( true );

		Functions\expect( 'get_post' )
			->with( 123 )
			->andReturn( $post );

		$this->permissions_helper
			->shouldReceive( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturn( false );

		// Checkbox not set.
		unset( $_POST['duplicate_post_remove_original'] );

		Functions\expect( 'delete_post_meta' )->never();

		$this->instance->delete_on_save_post( 123 );
	}

	/**
	 * Tests that delete_on_save_post removes the meta when checkbox is checked.
	 *
	 * @covers ::delete_on_save_post
	 *
	 * @return void
	 */
	public function test_delete_on_save_post_removes_meta_when_checkbox_checked() {
		$post     = Mockery::mock( WP_Post::class );
		$post->ID = 123;

		Functions\expect( 'current_user_can' )
			->with( 'edit_post', 123 )
			->andReturn( true );

		Functions\expect( 'get_post' )
			->with( 123 )
			->andReturn( $post );

		$this->permissions_helper
			->shouldReceive( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturn( false );

		// Checkbox is set.
		$_POST['duplicate_post_remove_original'] = '1';

		Functions\expect( 'delete_post_meta' )
			->with( 123, '_dp_original' )
			->once();

		$this->instance->delete_on_save_post( 123 );
	}
}
