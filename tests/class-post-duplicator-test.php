<?php

namespace Yoast\WP\Duplicate_Post\Tests;

use Brain\Monkey;
use Mockery;
use WP_Post;
use WP_User;
use Yoast\WP\Duplicate_Post\Post_Duplicator;

/**
 * Test the Post_Duplicator class.
 */
class Post_Duplicator_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Post_Duplicator
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->instance = new Post_Duplicator();
	}

	/**
	 * Tests the get_default_options function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::get_default_options
	 */
	public function test_get_default_options() {
		$this->assertSame(
			[
				'copy_title'             => true,
				'copy_date'              => false,
				'copy_status'            => false,
				'copy_name'              => false,
				'copy_excerpt'           => true,
				'copy_content'           => true,
				'copy_thumbnail'         => true,
				'copy_template'          => true,
				'copy_format'            => true,
				'copy_author'            => false,
				'copy_password'          => false,
				'copy_attachments'       => false,
				'copy_children'          => false,
				'copy_comments'          => false,
				'copy_menu_order'        => true,
				'title_prefix'           => '',
				'title_suffix'           => '',
				'increase_menu_order_by' => null,
				'parent_id'              => null,
				'meta_excludelist'       => [],
				'taxonomies_excludelist' => [],
				'use_filters'            => true,
			],
			$this->instance->get_default_options()
		);
	}

	/**
	 * Tests the generate_copy_title function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::generate_copy_title
	 * @dataProvider generate_copy_title_provider
	 *
	 * @param mixed $original Input value.
	 * @param mixed $expected Expected output.
	 */
	public function test_generate_copy_title( $original, $expected ) {
		$post             = Mockery::mock( WP_Post::class );
		$post->post_title = 'Title';

		Monkey\Functions\expect( '\sanitize_text_field' )
			->withAnyArgs()
			->twice()
			->andReturnFirstArg();

		$this->assertEquals( $expected, $this->instance->generate_copy_title( $post, $original ) );
	}

	/**
	 * Data provider for test_generate_copy_title.
	 *
	 * @return array
	 */
	public function generate_copy_title_provider() {
		$data = [];

		$data[] = [
			[
				'title_prefix' => '',
				'title_suffix' => '',
				'copy_title'   => true,
			],
			'Title',
		];

		$data[] = [
			[
				'title_prefix' => 'Copy of',
				'title_suffix' => '',
				'copy_title'   => true,
			],
			'Copy of Title',
		];

		$data[] = [
			[
				'title_prefix' => '',
				'title_suffix' => '(dup)',
				'copy_title'   => true,
			],
			'Title (dup)',
		];

		$data[] = [
			[
				'title_prefix' => 'Copy of',
				'title_suffix' => '(dup)',
				'copy_title'   => true,
			],
			'Copy of Title (dup)',
		];

		$data[] = [
			[
				'title_prefix' => '',
				'title_suffix' => '',
				'copy_title'   => false,
			],
			'',
		];

		$data[] = [
			[
				'title_prefix' => 'Copy of',
				'title_suffix' => '(dup)',
				'copy_title'   => false,
			],
			'Copy of(dup)',
		];

		return $data;
	}

	/**
	 * Tests the generate_copy_status function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::generate_copy_status
	 * @dataProvider generate_copy_status_provider
	 *
	 * @param mixed $original Input value.
	 * @param mixed $expected Expected output.
	 */
	public function test_generate_copy_status( $original, $expected ) {
		$post              = Mockery::mock( WP_Post::class );
		$post->post_status = $original['post_status'];
		$post->post_type   = $original['post_type'];

		$options                = [];
		$options['copy_status'] = $original['copy_status'];

		Monkey\Functions\expect( '\is_post_type_hierarchical' )
			->with( $post->post_type )
			->andReturnUsing(
				static function ( $post_type ) {
					return $post_type !== 'post' && $post_type === 'page';
				}
			);

		Monkey\Functions\expect( '\current_user_can' )
			->with( 'publish_posts' )
			->andReturn( $original['capability'] );

		Monkey\Functions\expect( '\current_user_can' )
			->with( 'publish_pages' )
			->andReturn( $original['capability'] );

		$this->assertEquals( $expected, $this->instance->generate_copy_status( $post, $options ) );
	}

	/**
	 * Data provider for test_generate_copy_status.
	 *
	 * @return array
	 */
	public function generate_copy_status_provider() {
		$data = [];

		$data[] = [
			[
				'post_status' => 'publish',
				'post_type'   => 'post',
				'copy_status' => true,
				'capability'  => true,
			],
			'publish',
		];

		$data[] = [
			[
				'post_status' => 'publish',
				'post_type'   => 'post',
				'copy_status' => false,
				'capability'  => true,
			],
			'draft',
		];

		$data[] = [
			[
				'post_status' => 'publish',
				'post_type'   => 'post',
				'copy_status' => true,
				'capability'  => false,
			],
			'pending',
		];

		$data[] = [
			[
				'post_status' => 'future',
				'post_type'   => 'page',
				'copy_status' => true,
				'capability'  => false,
			],
			'pending',
		];

		$data[] = [
			[
				'post_status' => 'draft',
				'post_type'   => 'post',
				'copy_status' => true,
				'capability'  => false,
			],
			'draft',
		];

		return $data;
	}

	/**
	 * Tests the generate_copy_author function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::generate_copy_author
	 * @dataProvider generate_copy_author_provider
	 *
	 * @param mixed $original Input value.
	 * @param mixed $expected Expected output.
	 */
	public function test_generate_copy_author( $original, $expected ) {
		$post              = Mockery::mock( WP_Post::class );
		$post->post_author = 1;
		$post->post_type   = $original['post_type'];

		$options                = [];
		$options['copy_author'] = $original['copy_author'];

		$user     = Mockery::mock( WP_User::class );
		$user->ID = 2;

		Monkey\Functions\expect( '\wp_get_current_user' )
			->once()
			->andReturn( $user );

		Monkey\Functions\expect( '\is_post_type_hierarchical' )
			->with( $post->post_type )
			->andReturnUsing(
				static function ( $post_type ) {
					return $post_type !== 'post' && $post_type === 'page';
				}
			);

		Monkey\Functions\expect( '\current_user_can' )
			->with( 'edit_others_pages' )
			->andReturn( $original['capability'] );

		Monkey\Functions\expect( '\current_user_can' )
			->with( 'edit_others_posts' )
			->andReturn( $original['capability'] );

		$this->assertEquals( $expected, $this->instance->generate_copy_author( $post, $options ) );
	}

	/**
	 * Data provider for test_generate_copy_author.
	 *
	 * @return array
	 */
	public function generate_copy_author_provider() {
		$data = [];

		$data[] = [
			[
				'post_type'   => 'post',
				'copy_author' => false,
				'capability'  => true,
			],
			2,
		];

		$data[] = [
			[
				'post_type'   => 'post',
				'copy_author' => true,
				'capability'  => true,
			],
			1,
		];

		$data[] = [
			[
				'post_type'   => 'post',
				'copy_author' => true,
				'capability'  => false,
			],
			2,
		];

		$data[] = [
			[
				'post_type'   => 'page',
				'copy_author' => true,
				'capability'  => true,
			],
			1,
		];

		$data[] = [
			[
				'post_type'   => 'page',
				'copy_author' => true,
				'capability'  => false,
			],
			2,
		];

		return $data;
	}
}
