<?php

namespace Yoast\WP\Duplicate_Post\Tests\WP;

use WP_Post;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Post_Duplicator;
use Yoast\WP\Duplicate_Post\Post_Republisher;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Integration tests for the Rewrite & Republish feature.
 *
 * @coversDefaultClass \Yoast\WP\Duplicate_Post\Post_Republisher
 */
final class Post_Republisher_Test extends TestCase {

	/**
	 * Instance of the Post_Republisher class.
	 *
	 * @var Post_Republisher
	 */
	private $instance;

	/**
	 * Instance of the Post_Duplicator class.
	 *
	 * @var Post_Duplicator
	 */
	private $post_duplicator;

	/**
	 * Instance of the Permissions_Helper class.
	 *
	 * @var Permissions_Helper
	 */
	private $permissions_helper;

	/**
	 * Setting up the instance of Post_Republisher.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		// Enable post and page for duplication.
		\update_option( 'duplicate_post_types_enabled', [ 'post', 'page' ] );

		$this->post_duplicator    = new Post_Duplicator();
		$this->permissions_helper = new Permissions_Helper();
		$this->instance           = new Post_Republisher( $this->post_duplicator, $this->permissions_helper );

		// Remove the republish_after_post_request hook that causes redirects.
		global $wp_filter;
		if ( isset( $wp_filter['wp_insert_post'] ) ) {
			unset( $wp_filter['wp_insert_post']->callbacks[ \PHP_INT_MAX ] );
		}
	}

	/**
	 * Helper method to create a published post.
	 *
	 * @param array $args Optional. Arguments for wp_insert_post.
	 *
	 * @return WP_Post The created post object.
	 */
	private function create_original_post( $args = [] ) {
		$defaults = [
			'post_title'   => 'Original Post Title',
			'post_content' => 'Original post content.',
			'post_excerpt' => 'Original excerpt.',
			'post_status'  => 'publish',
			'post_type'    => 'post',
		];

		$post_id = $this->factory->post->create( \array_merge( $defaults, $args ) );

		return \get_post( $post_id );
	}

	/**
	 * Helper method to create a Rewrite & Republish copy of a post.
	 *
	 * @param WP_Post $original The original post.
	 *
	 * @return WP_Post The copy post object.
	 */
	private function create_rewrite_and_republish_copy( WP_Post $original ) {
		$copy_id = $this->post_duplicator->create_duplicate_for_rewrite_and_republish( $original );

		return \get_post( $copy_id );
	}

	/**
	 * Helper method to update a post without triggering the republish redirect.
	 *
	 * This prevents the republish flow by removing the filter that changes the
	 * copy status and by simulating a meta-box-loader request.
	 *
	 * @param array $postarr An array of post data to update.
	 *
	 * @return int|WP_Error The post ID on success, WP_Error on failure.
	 */
	private function update_post_without_republish( array $postarr ) {
		// Store and remove the wp_insert_post_data filter (priority 1) that changes status to dp-rewrite-republish.
		$data_filter_removed = \remove_filter( 'wp_insert_post_data', [ $this->instance, 'change_post_copy_status' ], 1 );

		// Store and remove the wp_insert_post hook (priority PHP_INT_MAX) that triggers republish.
		$insert_hook_removed = \remove_action( 'wp_insert_post', [ $this->instance, 'republish_after_post_request' ], \PHP_INT_MAX );

		$result = \wp_update_post( $postarr );

		// Restore the filters.
		if ( $data_filter_removed ) {
			\add_filter( 'wp_insert_post_data', [ $this->instance, 'change_post_copy_status' ], 1, 2 );
		}
		if ( $insert_hook_removed ) {
			\add_action( 'wp_insert_post', [ $this->instance, 'republish_after_post_request' ], \PHP_INT_MAX, 2 );
		}

		return $result;
	}

	/**
	 * Tests that create_duplicate_for_rewrite_and_republish creates a copy with correct meta.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::create_duplicate_for_rewrite_and_republish
	 *
	 * @return void
	 */
	public function test_create_duplicate_for_rewrite_and_republish_sets_correct_meta() {
		$original = $this->create_original_post();
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Verify the copy was created.
		$this->assertInstanceOf( WP_Post::class, $copy );
		$this->assertNotEquals( $original->ID, $copy->ID );

		// Verify the copy has the R&R meta.
		$this->assertEquals( 1, (int) \get_post_meta( $copy->ID, '_dp_is_rewrite_republish_copy', true ) );

		// Verify the original has reference to the copy.
		$this->assertEquals( $copy->ID, (int) \get_post_meta( $original->ID, '_dp_has_rewrite_republish_copy', true ) );

		// Verify the creation date is set.
		$creation_date = \get_post_meta( $copy->ID, '_dp_creation_date_gmt', true );
		$this->assertNotEmpty( $creation_date );

		// Verify the copy references the original.
		$this->assertEquals( $original->ID, (int) \get_post_meta( $copy->ID, '_dp_original', true ) );
	}

	/**
	 * Tests that create_duplicate_for_rewrite_and_republish copies content correctly.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::create_duplicate_for_rewrite_and_republish
	 *
	 * @return void
	 */
	public function test_create_duplicate_for_rewrite_and_republish_copies_content() {
		$original = $this->create_original_post(
			[
				'post_title'   => 'Test Title',
				'post_content' => 'Test content here.',
				'post_excerpt' => 'Test excerpt.',
			]
		);

		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Verify content is copied.
		$this->assertEquals( $original->post_title, $copy->post_title );
		$this->assertEquals( $original->post_content, $copy->post_content );
		$this->assertEquals( $original->post_excerpt, $copy->post_excerpt );

		// Verify the copy is a draft.
		$this->assertEquals( 'draft', $copy->post_status );
	}

	/**
	 * Tests that create_duplicate_for_rewrite_and_republish copies taxonomies.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::create_duplicate_for_rewrite_and_republish
	 *
	 * @return void
	 */
	public function test_create_duplicate_for_rewrite_and_republish_copies_taxonomies() {
		$category_id = $this->factory->category->create( [ 'name' => 'Test Category' ] );
		$tag_id      = $this->factory->tag->create( [ 'name' => 'Test Tag' ] );

		$original = $this->create_original_post();
		\wp_set_post_categories( $original->ID, [ $category_id ] );
		\wp_set_post_tags( $original->ID, [ $tag_id ] );

		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Verify categories are copied.
		$original_categories = \wp_get_post_categories( $original->ID );
		$copy_categories     = \wp_get_post_categories( $copy->ID );
		$this->assertEquals( $original_categories, $copy_categories );

		// Verify tags are copied.
		$original_tags = \wp_get_post_tags( $original->ID, [ 'fields' => 'ids' ] );
		$copy_tags     = \wp_get_post_tags( $copy->ID, [ 'fields' => 'ids' ] );
		$this->assertEquals( $original_tags, $copy_tags );
	}

	/**
	 * Tests that republish overwrites the original post with copy content.
	 *
	 * @covers ::republish
	 *
	 * @return void
	 */
	public function test_republish_overwrites_original_content() {
		$original = $this->create_original_post(
			[
				'post_title'   => 'Original Title',
				'post_content' => 'Original content.',
			]
		);

		$original_slug = $original->post_name;
		$original_id   = $original->ID;

		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Modify the copy content.
		\wp_update_post(
			[
				'ID'           => $copy->ID,
				'post_title'   => 'Updated Title',
				'post_content' => 'Updated content.',
				'post_excerpt' => 'Updated excerpt.',
			]
		);

		// Refresh the copy object.
		$copy = \get_post( $copy->ID );

		// Republish.
		$this->instance->republish( $copy, $original );

		// Refresh the original post.
		$updated_original = \get_post( $original_id );

		// Verify the original post has updated content.
		$this->assertEquals( 'Updated Title', $updated_original->post_title );
		$this->assertEquals( 'Updated content.', $updated_original->post_content );
		$this->assertEquals( 'Updated excerpt.', $updated_original->post_excerpt );

		// Verify the original keeps its ID and slug.
		$this->assertEquals( $original_id, $updated_original->ID );
		$this->assertEquals( $original_slug, $updated_original->post_name );

		// Verify the original is still published.
		$this->assertEquals( 'publish', $updated_original->post_status );

		// Verify the copy is marked as republished.
		$this->assertEquals( '1', \get_post_meta( $copy->ID, '_dp_has_been_republished', true ) );
	}

	/**
	 * Tests that republish transfers taxonomies from copy to original.
	 *
	 * @covers ::republish
	 *
	 * @return void
	 */
	public function test_republish_transfers_taxonomies() {
		$original_category = $this->factory->category->create( [ 'name' => 'Original Category' ] );
		$new_category      = $this->factory->category->create( [ 'name' => 'New Category' ] );

		$original = $this->create_original_post();
		\wp_set_post_categories( $original->ID, [ $original_category ] );

		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Change the copy's category.
		\wp_set_post_categories( $copy->ID, [ $new_category ] );

		// Republish.
		$this->instance->republish( $copy, $original );

		// Verify the original now has the new category.
		$updated_categories = \wp_get_post_categories( $original->ID );
		$this->assertContains( $new_category, $updated_categories );
		$this->assertNotContains( $original_category, $updated_categories );
	}

	/**
	 * Tests that republish transfers meta from copy to original.
	 *
	 * @covers ::republish
	 *
	 * @return void
	 */
	public function test_republish_transfers_meta() {
		$original = $this->create_original_post();
		\update_post_meta( $original->ID, 'custom_meta_key', 'original_value' );

		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Verify meta was copied.
		$this->assertEquals( 'original_value', \get_post_meta( $copy->ID, 'custom_meta_key', true ) );

		// Update the copy's meta.
		\update_post_meta( $copy->ID, 'custom_meta_key', 'updated_value' );

		// Republish.
		$this->instance->republish( $copy, $original );

		// Verify the original now has the updated meta.
		$this->assertEquals( 'updated_value', \get_post_meta( $original->ID, 'custom_meta_key', true ) );
	}

	/**
	 * Tests that change_post_copy_status changes publish to dp-rewrite-republish for R&R copies.
	 *
	 * @covers ::change_post_copy_status
	 *
	 * @return void
	 */
	public function test_change_post_copy_status_changes_status_for_copy() {
		$original = $this->create_original_post();
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		$data    = [ 'post_status' => 'publish' ];
		$postarr = [ 'ID' => $copy->ID ];

		$result = $this->instance->change_post_copy_status( $data, $postarr );

		$this->assertEquals( 'dp-rewrite-republish', $result['post_status'] );
	}

	/**
	 * Tests that change_post_copy_status does not change status for non-R&R posts.
	 *
	 * @covers ::change_post_copy_status
	 *
	 * @return void
	 */
	public function test_change_post_copy_status_does_not_change_status_for_regular_post() {
		$post = $this->create_original_post();

		$data    = [ 'post_status' => 'publish' ];
		$postarr = [ 'ID' => $post->ID ];

		$result = $this->instance->change_post_copy_status( $data, $postarr );

		$this->assertEquals( 'publish', $result['post_status'] );
	}

	/**
	 * Tests that change_post_copy_status does not change non-publish status.
	 *
	 * @covers ::change_post_copy_status
	 *
	 * @return void
	 */
	public function test_change_post_copy_status_does_not_change_draft_status() {
		$original = $this->create_original_post();
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		$data    = [ 'post_status' => 'draft' ];
		$postarr = [ 'ID' => $copy->ID ];

		$result = $this->instance->change_post_copy_status( $data, $postarr );

		$this->assertEquals( 'draft', $result['post_status'] );
	}

	/**
	 * Tests that delete_copy permanently deletes the copy and cleans up meta.
	 *
	 * @covers ::delete_copy
	 *
	 * @return void
	 */
	public function test_delete_copy_permanently_deletes_copy() {
		$original = $this->create_original_post();
		$copy     = $this->create_rewrite_and_republish_copy( $original );
		$copy_id  = $copy->ID;

		// Verify the original has the copy reference.
		$this->assertEquals( $copy_id, (int) \get_post_meta( $original->ID, '_dp_has_rewrite_republish_copy', true ) );

		// Delete the copy.
		$this->instance->delete_copy( $copy_id, $original->ID );

		// Verify the copy is deleted.
		$this->assertNull( \get_post( $copy_id ) );

		// Verify the meta is cleaned up from the original.
		$this->assertEmpty( \get_post_meta( $original->ID, '_dp_has_rewrite_republish_copy', true ) );
	}

	/**
	 * Tests that delete_copy with permanently_delete=false moves copy to trash.
	 *
	 * @covers ::delete_copy
	 *
	 * @return void
	 */
	public function test_delete_copy_moves_to_trash_when_not_permanent() {
		$original = $this->create_original_post();
		$copy     = $this->create_rewrite_and_republish_copy( $original );
		$copy_id  = $copy->ID;

		// Delete the copy without permanent deletion.
		$this->instance->delete_copy( $copy_id, $original->ID, false );

		// Verify the copy is trashed, not deleted.
		$trashed_copy = \get_post( $copy_id );
		$this->assertNotNull( $trashed_copy );
		$this->assertEquals( 'trash', $trashed_copy->post_status );
	}

	/**
	 * Tests republish_scheduled_post republishes a scheduled copy.
	 *
	 * @covers ::republish_scheduled_post
	 *
	 * @return void
	 */
	public function test_republish_scheduled_post_republishes_copy() {
		$original = $this->create_original_post(
			[
				'post_title'   => 'Original Title',
				'post_content' => 'Original content.',
			]
		);

		$original_id = $original->ID;

		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Update copy content.
		\wp_update_post(
			[
				'ID'           => $copy->ID,
				'post_title'   => 'Scheduled Updated Title',
				'post_content' => 'Scheduled updated content.',
			]
		);

		// Refresh the copy.
		$copy = \get_post( $copy->ID );

		// Simulate scheduled post transition.
		$this->instance->republish_scheduled_post( $copy );

		// Refresh the original.
		$updated_original = \get_post( $original_id );

		// Verify the original was updated.
		$this->assertEquals( 'Scheduled Updated Title', $updated_original->post_title );
		$this->assertEquals( 'Scheduled updated content.', $updated_original->post_content );

		// Verify the copy was deleted.
		$this->assertNull( \get_post( $copy->ID ) );

		// Verify meta cleanup.
		$this->assertEmpty( \get_post_meta( $original_id, '_dp_has_rewrite_republish_copy', true ) );
	}

	/**
	 * Tests republish_scheduled_post trashes copy when original is deleted.
	 *
	 * @covers ::republish_scheduled_post
	 *
	 * @return void
	 */
	public function test_republish_scheduled_post_trashes_copy_when_original_deleted() {
		$original = $this->create_original_post();
		$copy     = $this->create_rewrite_and_republish_copy( $original );
		$copy_id  = $copy->ID;

		// Permanently delete the original.
		\wp_delete_post( $original->ID, true );

		// Simulate scheduled post transition.
		$this->instance->republish_scheduled_post( $copy );

		// Verify the copy is trashed, not deleted.
		$trashed_copy = \get_post( $copy_id );
		$this->assertNotNull( $trashed_copy );
		$this->assertEquals( 'trash', $trashed_copy->post_status );
	}

	/**
	 * Tests republish_scheduled_post does nothing for non-R&R posts.
	 *
	 * @covers ::republish_scheduled_post
	 *
	 * @return void
	 */
	public function test_republish_scheduled_post_ignores_non_copy() {
		$post             = $this->create_original_post();
		$original_title   = $post->post_title;
		$original_content = $post->post_content;

		// Call republish_scheduled_post on a non-copy.
		$this->instance->republish_scheduled_post( $post );

		// Verify nothing changed.
		$unchanged_post = \get_post( $post->ID );
		$this->assertEquals( $original_title, $unchanged_post->post_title );
		$this->assertEquals( $original_content, $unchanged_post->post_content );
	}

	/**
	 * Tests that republish preserves original post status when it's trashed.
	 *
	 * @covers ::republish
	 *
	 * @return void
	 */
	public function test_republish_preserves_trash_status() {
		$original = $this->create_original_post();
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Trash the original.
		\wp_trash_post( $original->ID );
		$original = \get_post( $original->ID );

		// Republish.
		$this->instance->republish( $copy, $original );

		// Verify the original stays trashed.
		$updated_original = \get_post( $original->ID );
		$this->assertEquals( 'trash', $updated_original->post_status );
	}

	/**
	 * Tests that republish uses private status when copy is private.
	 *
	 * @covers ::republish
	 *
	 * @return void
	 */
	public function test_republish_uses_private_status_from_copy() {
		$original = $this->create_original_post();
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Make the copy private (without triggering the republish hook).
		$this->update_post_without_republish(
			[
				'ID'          => $copy->ID,
				'post_status' => 'private',
			]
		);
		$copy = \get_post( $copy->ID );

		// Republish.
		$this->instance->republish( $copy, $original );

		// Verify the original is now private.
		$updated_original = \get_post( $original->ID );
		$this->assertEquals( 'private', $updated_original->post_status );
	}
}
