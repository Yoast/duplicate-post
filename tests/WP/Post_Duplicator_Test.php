<?php

namespace Yoast\WP\Duplicate_Post\Tests\WP;

use WP_Error;
use Yoast\WP\Duplicate_Post\Post_Duplicator;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Class Post_Duplicator.
 *
 * @coversDefaultClass \Yoast\WP\Duplicate_Post\Post_Duplicator
 */
final class Post_Duplicator_Test extends TestCase {

	/**
	 * Instance of the Post_Duplicator class.
	 *
	 * @var Post_Duplicator
	 */
	private $instance;

	/**
	 * Setting up the instance of Post_Duplicator.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->instance = new Post_Duplicator();
	}

	/**
	 * Tests whether the admin page is generated correctly.
	 *
	 * @covers ::create_duplicate
	 * @covers ::get_default_options
	 * @covers ::generate_copy_title
	 * @covers ::generate_copy_status
	 * @covers ::generate_copy_author
	 * @covers ::set_modified
	 *
	 * @return void
	 */
	public function test_create_duplicate() {

		$post = $this->factory->post->create_and_get();

		$id = $this->instance->create_duplicate( $post, [ 'copy_date' => true ] );

		$this->assertIsInt( $id );
	}

	/**
	 * Tests that creating an R&R copy claims the slot on the original post.
	 *
	 * @covers ::create_duplicate_for_rewrite_and_republish
	 *
	 * @return void
	 */
	public function test_create_duplicate_for_rewrite_and_republish_claims_slot() {
		$post = $this->factory->post->create_and_get( [ 'post_status' => 'publish' ] );

		$copy_id = $this->instance->create_duplicate_for_rewrite_and_republish( $post );

		$this->assertIsInt( $copy_id );
		$this->assertSame( (string) $copy_id, \get_post_meta( $post->ID, '_dp_has_rewrite_republish_copy', true ) );
		$this->assertSame( 1, (int) \get_post_meta( $copy_id, '_dp_is_rewrite_republish_copy', true ) );
	}

	/**
	 * Tests that creating an R&R copy fails when the original already has one.
	 *
	 * @covers ::create_duplicate_for_rewrite_and_republish
	 *
	 * @return void
	 */
	public function test_create_duplicate_for_rewrite_and_republish_blocks_duplicate() {
		$post = $this->factory->post->create_and_get( [ 'post_status' => 'publish' ] );

		$first_copy_id = $this->instance->create_duplicate_for_rewrite_and_republish( $post );

		$this->assertIsInt( $first_copy_id );

		$second_result = $this->instance->create_duplicate_for_rewrite_and_republish( $post );

		$this->assertInstanceOf( WP_Error::class, $second_result );
		$this->assertSame( 'duplicate_post_already_has_copy', $second_result->get_error_code() );

		// Original should still reference the first copy only.
		$meta_values = \get_post_meta( $post->ID, '_dp_has_rewrite_republish_copy', false );
		$this->assertCount( 1, $meta_values );
		$this->assertSame( (string) $first_copy_id, $meta_values[0] );
	}

	/**
	 * Tests that the claim is rolled back when copy creation fails.
	 *
	 * @covers ::create_duplicate_for_rewrite_and_republish
	 *
	 * @return void
	 */
	public function test_create_duplicate_for_rewrite_and_republish_rolls_back_on_failure() {
		$post = $this->factory->post->create_and_get( [ 'post_status' => 'publish' ] );

		// Force wp_insert_post to fail by filtering the post data.
		\add_filter(
			'wp_insert_post_empty_content',
			'__return_true',
		);

		$result = $this->instance->create_duplicate_for_rewrite_and_republish( $post );

		\remove_filter( 'wp_insert_post_empty_content', '__return_true' );

		$this->assertInstanceOf( WP_Error::class, $result );

		// The claim should have been rolled back.
		$this->assertSame( '', \get_post_meta( $post->ID, '_dp_has_rewrite_republish_copy', true ) );
	}
}
