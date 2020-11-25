<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests\UI;

use Brain\Monkey;
use Yoast\WP\Duplicate_Post\Tests\TestCase;
use Yoast\WP\Duplicate_Post\UI\Post_States;

/**
 * Test the Post_States class.
 */
class Post_States_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Post_States
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_original_in_post_states' )
			->andReturn( '1' );
		$this->instance = new Post_States();
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_States::register_hooks
	 */
	public function test_register_hooks() {
		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_original_in_post_states' )
			->andReturn( '1' );

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
		$utils       = \Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$post        = \Mockery::mock( \WP_Post::class );
		$original    = \Mockery::mock( \WP_Post::class );
		$post_states = [
			'draft' => 'Draft',
		];

		$utils->expects( 'get_original' )
			->with( $post )
			->andReturn( $original );

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
		$utils       = \Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$post        = \Mockery::mock( \WP_Post::class );
		$post_states = [
			'draft' => 'Draft',
		];

		$utils->expects( 'get_original' )
			->with( $post )
			->andReturnNull();

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
