<?php

namespace Yoast\WP\Duplicate_Post\Tests\Unit\UI;

use Brain\Monkey;
use Mockery;
use WP_Post;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Tests\Unit\TestCase;
use Yoast\WP\Duplicate_Post\UI\Post_States;

/**
 * Test the Post_States class.
 */
class Post_States_Test extends TestCase {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * The instance.
	 *
	 * @var Post_States
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	protected function set_up() {
		parent::set_up();

		$this->permissions_helper = Mockery::mock( Permissions_Helper::class );

		$this->instance = new Post_States( $this->permissions_helper );
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_States::__construct
	 */
	public function test_constructor() {
		$this->assertInstanceOf(
			Permissions_Helper::class,
			$this->getPropertyValue( $this->instance, 'permissions_helper' )
		);
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_States::register_hooks
	 */
	public function test_register_hooks() {
		$this->instance->register_hooks();

		$this->assertNotFalse( \has_filter( 'display_post_states', [ $this->instance, 'show_original_in_post_states' ] ), 'Does not have expected display_post_states filter' );
	}

	/**
	 * Tests the show_original_in_post_states function when a post state is added.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_States::show_original_in_post_states
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_show_original_in_post_states_successful() {
		$this->stubTranslationFunctions();

		$utils       = Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$post        = Mockery::mock( WP_Post::class );
		$original    = Mockery::mock( WP_Post::class );
		$post_states = [
			'draft' => 'Draft',
		];

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_original_in_post_states' )
			->andReturn( '1' );

		$utils->expects( 'get_original' )
			->with( $post )
			->andReturn( $original );

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturnFalse();

		$utils->expects( 'get_edit_or_view_link' )
			->with( $original )
			->andReturn( '<a href="http://basic.wordpress.test/wp-admin/post.php?post=373&amp;action=edit" aria-label="Edit “Original post”">Original post</a>' );

		$this->assertSame(
			[
				'draft'                        => 'Draft',
				'duplicate_post_original_item' => 'Original: <a href="http://basic.wordpress.test/wp-admin/post.php?post=373&amp;action=edit" aria-label="Edit “Original post”">Original post</a>',
			],
			$this->instance->show_original_in_post_states( $post_states, $post )
		);
	}

	/**
	 * Tests the show_original_in_post_states function when a post state is not added.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_States::show_original_in_post_states
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_show_original_in_post_states_unsuccessful() {
		$utils       = Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$post        = Mockery::mock( WP_Post::class );
		$post_states = [
			'draft' => 'Draft',
		];

		$utils->expects( 'get_original' )
			->with( $post )
			->andReturnNull();

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->never();

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_original_in_post_states' )
			->never();

		$utils->expects( 'get_edit_or_view_link' )
			->never();

		$this->assertSame(
			[
				'draft' => 'Draft',
			],
			$this->instance->show_original_in_post_states( $post_states, $post )
		);
	}

	/**
	 * Tests the show_original_in_post_states function when a post is a copy for Rewrite & Republish.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_States::show_original_in_post_states
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_show_original_in_rewrite_republish_post_successful() {
		$this->stubTranslationFunctions();

		$utils       = Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$post        = Mockery::mock( WP_Post::class );
		$original    = Mockery::mock( WP_Post::class );
		$post_states = [
			'draft' => 'Draft',
		];

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_original_in_post_states' )
			->andReturn( '0' );

		$utils->expects( 'get_original' )
			->with( $post )
			->andReturn( $original );

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturnTrue();

		$utils->expects( 'get_edit_or_view_link' )
			->with( $original )
			->andReturn( '<a href="http://basic.wordpress.test/wp-admin/post.php?post=373&amp;action=edit" aria-label="Edit “Original post”">Original post</a>' );

		$this->assertSame(
			[
				'draft'                        => 'Draft',
				'duplicate_post_original_item' => 'Rewrite & Republish of <a href="http://basic.wordpress.test/wp-admin/post.php?post=373&amp;action=edit" aria-label="Edit “Original post”">Original post</a>',
			],
			$this->instance->show_original_in_post_states( $post_states, $post )
		);
	}

	/**
	 * Tests the show_original_in_post_states function when a post is not a copy for Rewrite & Republish.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_States::show_original_in_post_states
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_show_original_in_rewrite_republish_post_unsuccessful() {
		$utils       = Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$post        = Mockery::mock( WP_Post::class );
		$post_states = [
			'draft' => 'Draft',
		];

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_original_in_post_states' )
			->andReturn( '0' );

		$utils->expects( 'get_original' )
			->with( $post )
			->andReturnNull();

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->never();

		$utils->expects( 'get_edit_or_view_link' )
			->never();

		$this->assertSame(
			[
				'draft' => 'Draft',
			],
			$this->instance->show_original_in_post_states( $post_states, $post )
		);
	}
}
