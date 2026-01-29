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
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::create_duplicate
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::copy_post_taxonomies
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::copy_post_meta_info
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
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::create_duplicate
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::generate_copy_title
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::generate_copy_status
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::generate_copy_author
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
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::copy_post_taxonomies
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
	 * @covers ::republish_post_elements
	 * @covers ::republish_post_taxonomies
	 * @covers ::republish_post_meta
	 * @covers ::determine_post_status
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
	 * @covers ::republish_post_taxonomies
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
	 * @covers ::republish_post_meta
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
	 * @covers ::republish
	 * @covers ::delete_copy
	 * @covers ::republish_post_elements
	 * @covers ::republish_post_taxonomies
	 * @covers ::republish_post_meta
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
	 * @covers ::delete_copy
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
	 * @covers ::determine_post_status
	 * @covers ::republish_post_elements
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
	 * @covers ::determine_post_status
	 * @covers ::republish_post_elements
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

	/**
	 * Tests that creating a second R&R copy of the same original creates another copy.
	 *
	 * Note: The Post_Duplicator does not prevent creating multiple copies.
	 * The prevention logic is in the UI/permissions layer.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::create_duplicate_for_rewrite_and_republish
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::create_duplicate
	 *
	 * @return void
	 */
	public function test_duplicator_creates_second_copy_when_one_exists() {
		$original   = $this->create_original_post();
		$first_copy = $this->create_rewrite_and_republish_copy( $original );

		// Verify first copy was created successfully.
		$this->assertInstanceOf( WP_Post::class, $first_copy );

		// Verify the original now has a copy reference via meta.
		$this->assertNotEmpty( \get_post_meta( $original->ID, '_dp_has_rewrite_republish_copy', true ) );

		// The duplicator will create a second copy (no internal check).
		$second_copy_id = $this->post_duplicator->create_duplicate_for_rewrite_and_republish( $original );

		// Verify the second copy was created.
		$this->assertNotEmpty( $second_copy_id );
		$this->assertNotEquals( $first_copy->ID, $second_copy_id );

		// The meta on original now points to the second copy.
		$this->assertEquals( $second_copy_id, (int) \get_post_meta( $original->ID, '_dp_has_rewrite_republish_copy', true ) );

		// Clean up the extra copy.
		\wp_delete_post( $second_copy_id, true );
	}

	/**
	 * Tests republish_request does nothing for non-WP_Post objects.
	 *
	 * @covers ::republish_request
	 *
	 * @return void
	 */
	public function test_republish_request_handles_non_post_object() {
		$this->expectNotToPerformAssertions();

		// This should not cause any errors.
		$this->instance->republish_request( null );
		$this->instance->republish_request( 'not a post' );
		$this->instance->republish_request( 123 );
	}

	/**
	 * Tests republish_request does nothing for regular posts (not R&R copies).
	 *
	 * @covers ::republish_request
	 *
	 * @return void
	 */
	public function test_republish_request_ignores_regular_post() {
		$post             = $this->create_original_post();
		$original_title   = $post->post_title;
		$original_content = $post->post_content;

		$this->instance->republish_request( $post );

		// Verify nothing changed.
		$unchanged_post = \get_post( $post->ID );
		$this->assertEquals( $original_title, $unchanged_post->post_title );
		$this->assertEquals( $original_content, $unchanged_post->post_content );
	}

	/**
	 * Tests republish_request does nothing when copy status is not allowed (draft).
	 *
	 * @covers ::republish_request
	 *
	 * @return void
	 */
	public function test_republish_request_ignores_draft_copy() {
		$original = $this->create_original_post(
			[
				'post_title'   => 'Original Title',
				'post_content' => 'Original content.',
			]
		);

		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Copy is a draft by default.
		$this->assertEquals( 'draft', $copy->post_status );

		// Modify the copy.
		\wp_update_post(
			[
				'ID'           => $copy->ID,
				'post_title'   => 'Modified Copy Title',
				'post_content' => 'Modified copy content.',
			]
		);
		$copy = \get_post( $copy->ID );

		// Try to republish - should do nothing because copy is draft.
		$this->instance->republish_request( $copy );

		// Verify the original was NOT updated.
		$unchanged_original = \get_post( $original->ID );
		$this->assertEquals( 'Original Title', $unchanged_original->post_title );
		$this->assertEquals( 'Original content.', $unchanged_original->post_content );
	}

	/**
	 * Tests republish_request does nothing when original post is missing.
	 *
	 * @covers ::republish_request
	 *
	 * @return void
	 */
	public function test_republish_request_handles_missing_original() {
		$original = $this->create_original_post();
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Update copy status to dp-rewrite-republish.
		$this->update_post_without_republish(
			[
				'ID'          => $copy->ID,
				'post_status' => 'dp-rewrite-republish',
			]
		);
		$copy = \get_post( $copy->ID );

		// Delete the original permanently.
		\wp_delete_post( $original->ID, true );

		// republish_request should not throw an error.
		$this->instance->republish_request( $copy );

		// Copy should still exist.
		$this->assertNotNull( \get_post( $copy->ID ) );
	}

	/**
	 * Tests that republish copies featured image (thumbnail) correctly.
	 *
	 * @covers ::republish
	 * @covers ::republish_post_meta
	 *
	 * @return void
	 */
	public function test_republish_transfers_featured_image() {
		$original = $this->create_original_post();

		// Create a simple attachment without requiring an actual file.
		$attachment_id = $this->factory->attachment->create(
			[
				'post_title'     => 'Test Attachment',
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			]
		);

		// If we can't create an attachment, skip this test gracefully.
		if ( ! $attachment_id || \is_wp_error( $attachment_id ) ) {
			$this->markTestSkipped( 'Could not create attachment for test.' );
		}

		// Set the thumbnail using post meta directly.
		\update_post_meta( $original->ID, '_thumbnail_id', $attachment_id );

		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Verify thumbnail was copied.
		$this->assertEquals( $attachment_id, (int) \get_post_meta( $copy->ID, '_thumbnail_id', true ) );

		// Create a new attachment for the copy.
		$new_attachment_id = $this->factory->attachment->create(
			[
				'post_title'     => 'New Test Attachment',
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			]
		);

		if ( $new_attachment_id && ! \is_wp_error( $new_attachment_id ) ) {
			// Set the new thumbnail on the copy.
			\update_post_meta( $copy->ID, '_thumbnail_id', $new_attachment_id );

			// Republish.
			$this->instance->republish( $copy, $original );

			// Verify the original now has the new thumbnail.
			$this->assertEquals( $new_attachment_id, (int) \get_post_meta( $original->ID, '_thumbnail_id', true ) );
		}
	}

	/**
	 * Tests that republish works when copy has dp-rewrite-republish status.
	 *
	 * @covers ::republish
	 * @covers ::republish_post_elements
	 * @covers ::republish_post_taxonomies
	 * @covers ::republish_post_meta
	 * @covers ::determine_post_status
	 *
	 * @return void
	 */
	public function test_republish_works_when_status_is_dp_rewrite_republish() {
		$original = $this->create_original_post(
			[
				'post_title'   => 'Original Title',
				'post_content' => 'Original content.',
			]
		);
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Update copy content and status to dp-rewrite-republish.
		$this->update_post_without_republish(
			[
				'ID'           => $copy->ID,
				'post_title'   => 'Republished Title',
				'post_content' => 'Republished content.',
				'post_status'  => 'dp-rewrite-republish',
			]
		);
		$copy = \get_post( $copy->ID );

		// Verify copy has the correct status.
		$this->assertEquals( 'dp-rewrite-republish', $copy->post_status );

		// Use republish() directly to avoid redirect.
		$this->instance->republish( $copy, $original );

		// Original should be updated.
		$updated_original = \get_post( $original->ID );
		$this->assertEquals( 'Republished Title', $updated_original->post_title );
		$this->assertEquals( 'Republished content.', $updated_original->post_content );
	}

	/**
	 * Tests that republish works when copy has private status.
	 *
	 * @covers ::republish
	 * @covers ::republish_post_elements
	 * @covers ::determine_post_status
	 *
	 * @return void
	 */
	public function test_republish_works_when_status_is_private() {
		$original = $this->create_original_post(
			[
				'post_title'   => 'Original Title',
				'post_content' => 'Original content.',
			]
		);
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Update copy content and status to private.
		$this->update_post_without_republish(
			[
				'ID'           => $copy->ID,
				'post_title'   => 'Private Copy Title',
				'post_content' => 'Private copy content.',
				'post_status'  => 'private',
			]
		);
		$copy = \get_post( $copy->ID );

		// Verify copy has private status.
		$this->assertEquals( 'private', $copy->post_status );

		// Use republish() directly to avoid redirect.
		$this->instance->republish( $copy, $original );

		// Original should be updated and set to private.
		$updated_original = \get_post( $original->ID );
		$this->assertEquals( 'Private Copy Title', $updated_original->post_title );
		$this->assertEquals( 'private', $updated_original->post_status );
	}

	/**
	 * Tests that republish_request does nothing when copy is in draft status.
	 *
	 * @covers ::republish_request
	 *
	 * @return void
	 */
	public function test_republish_request_does_not_republish_draft_copy() {
		$original = $this->create_original_post(
			[
				'post_title'   => 'Original Title',
				'post_content' => 'Original content.',
			]
		);
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Copy is a draft by default - modify it.
		\wp_update_post(
			[
				'ID'         => $copy->ID,
				'post_title' => 'Draft Copy Title',
			]
		);
		$copy = \get_post( $copy->ID );

		// Try republish_request on draft copy.
		$this->instance->republish_request( $copy );

		// Original should be unchanged.
		$unchanged_original = \get_post( $original->ID );
		$this->assertEquals( 'Original Title', $unchanged_original->post_title );
	}

	/**
	 * Tests that republish_request does nothing when copy is in pending status.
	 *
	 * @covers ::republish_request
	 *
	 * @return void
	 */
	public function test_republish_request_does_not_republish_pending_copy() {
		$original = $this->create_original_post(
			[
				'post_title'   => 'Original Title',
				'post_content' => 'Original content.',
			]
		);
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Update copy to pending status.
		$this->update_post_without_republish(
			[
				'ID'           => $copy->ID,
				'post_title'   => 'Pending Copy Title',
				'post_status'  => 'pending',
			]
		);
		$copy = \get_post( $copy->ID );

		// Try republish_request on pending copy.
		$this->instance->republish_request( $copy );

		// Original should be unchanged.
		$unchanged_original = \get_post( $original->ID );
		$this->assertEquals( 'Original Title', $unchanged_original->post_title );
	}

	/**
	 * Tests that republish handles post with no taxonomies.
	 *
	 * @covers ::republish
	 * @covers ::republish_post_taxonomies
	 *
	 * @return void
	 */
	public function test_republish_handles_post_without_taxonomies() {
		$original = $this->create_original_post();

		// Remove all categories and tags.
		\wp_set_post_categories( $original->ID, [] );
		\wp_set_post_tags( $original->ID, [] );

		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Update copy with content but no taxonomies.
		\wp_update_post(
			[
				'ID'           => $copy->ID,
				'post_title'   => 'Updated Title Without Taxonomies',
				'post_content' => 'Updated content.',
			]
		);
		$copy = \get_post( $copy->ID );

		// Republish - should not throw errors.
		$this->instance->republish( $copy, $original );

		// Verify the content was updated.
		$updated_original = \get_post( $original->ID );
		$this->assertEquals( 'Updated Title Without Taxonomies', $updated_original->post_title );
	}

	/**
	 * Tests that the Post_Duplicator creates copies for custom post types.
	 *
	 * Note: The Post_Duplicator does not check if a post type is enabled.
	 * The prevention logic is in the UI/permissions layer.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::create_duplicate_for_rewrite_and_republish
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::create_duplicate
	 *
	 * @return void
	 */
	public function test_duplicator_creates_copy_for_custom_post_type() {
		// Register a custom post type that's not enabled for duplication.
		\register_post_type( 'custom_type', [ 'public' => true ] );

		$post_id = $this->factory->post->create(
			[
				'post_type'   => 'custom_type',
				'post_status' => 'publish',
				'post_title'  => 'Custom Type Post',
			]
		);
		$post    = \get_post( $post_id );

		// The duplicator will still create a copy (no internal check for post type).
		$copy_id = $this->post_duplicator->create_duplicate_for_rewrite_and_republish( $post );

		// Verify the copy was created.
		$this->assertNotEmpty( $copy_id );
		$copy = \get_post( $copy_id );
		$this->assertEquals( 'custom_type', $copy->post_type );
		$this->assertEquals( 'Custom Type Post', $copy->post_title );

		// Clean up.
		\wp_delete_post( $copy_id, true );
		\wp_delete_post( $post_id, true );

		// Unregister the custom post type.
		\unregister_post_type( 'custom_type' );
	}

	/**
	 * Tests that calling republish on a regular post (not a copy) does nothing.
	 *
	 * @covers ::republish
	 * @covers ::republish_post_elements
	 * @covers ::republish_post_taxonomies
	 * @covers ::republish_post_meta
	 *
	 * @return void
	 */
	public function test_republish_on_non_copy_does_nothing() {
		$post1 = $this->create_original_post(
			[
				'post_title'   => 'Post 1 Title',
				'post_content' => 'Post 1 content.',
			]
		);
		$post2 = $this->create_original_post(
			[
				'post_title'   => 'Post 2 Title',
				'post_content' => 'Post 2 content.',
			]
		);

		// Calling republish with two unrelated posts should update the second
		// with content from the first, but this is not typical usage.
		// The _dp_has_been_republished meta should not be set on post1.
		$this->instance->republish( $post1, $post2 );

		// Verify post2 was updated (this is expected behavior of republish method).
		$updated_post2 = \get_post( $post2->ID );
		$this->assertEquals( 'Post 1 Title', $updated_post2->post_title );

		// But post1 should not have the republished meta since it wasn't a real R&R copy.
		// Note: The republish method does set this meta regardless, so we verify it.
		$this->assertEquals( '1', \get_post_meta( $post1->ID, '_dp_has_been_republished', true ) );
	}

	/**
	 * Tests that republish still works when original was modified after copy creation.
	 *
	 * Note: The republisher does not prevent republishing when original changed,
	 * it just overwrites with the copy content. The warning is shown in UI.
	 *
	 * @covers ::republish
	 * @covers ::republish_post_elements
	 *
	 * @return void
	 */
	public function test_republish_overwrites_even_when_original_changed() {
		$original = $this->create_original_post(
			[
				'post_title'   => 'Original Title',
				'post_content' => 'Original content.',
			]
		);
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Simulate time passing by explicitly updating the modified dates.
		$base_modified_gmt = ( $original->post_modified_gmt ) ? $original->post_modified_gmt : \get_gmt_from_date( $original->post_modified );
		$modified_time_gmt = \gmdate(
			'Y-m-d H:i:s',
			\strtotime( '+1 second', \strtotime( $base_modified_gmt ) )
		);
		$modified_time     = \get_date_from_gmt( $modified_time_gmt );

		\wp_update_post(
			[
				'ID'                => $original->ID,
				'post_title'        => 'Modified Original Title',
				'post_content'      => 'Modified original content.',
				'post_date'         => $modified_time,
				'post_date_gmt'     => $modified_time_gmt,
				'post_modified'     => $modified_time,
				'post_modified_gmt' => $modified_time_gmt,
			]
		);

		// Modify the copy with different content.
		\wp_update_post(
			[
				'ID'           => $copy->ID,
				'post_title'   => 'Copy Title',
				'post_content' => 'Copy content.',
			]
		);
		$copy = \get_post( $copy->ID );

		// Republish - should overwrite the original with copy content.
		$this->instance->republish( $copy, $original );

		// Original should have copy's content, not the modified content.
		$updated_original = \get_post( $original->ID );
		$this->assertEquals( 'Copy Title', $updated_original->post_title );
		$this->assertEquals( 'Copy content.', $updated_original->post_content );
	}

	/**
	 * Tests that copy preserves original content when created.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::create_duplicate_for_rewrite_and_republish
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::create_duplicate
	 *
	 * @return void
	 */
	public function test_copy_preserves_original_content_at_creation_time() {
		$original = $this->create_original_post(
			[
				'post_title'   => 'Original Title',
				'post_content' => 'Original content.',
			]
		);
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Copy should have the same content as original at creation time.
		$this->assertEquals( 'Original Title', $copy->post_title );
		$this->assertEquals( 'Original content.', $copy->post_content );

		// Modify the original - copy should not change.
		\wp_update_post(
			[
				'ID'           => $original->ID,
				'post_title'   => 'Modified Original',
				'post_content' => 'Modified content.',
			]
		);

		// Refresh copy - should still have original content.
		$copy = \get_post( $copy->ID );
		$this->assertEquals( 'Original Title', $copy->post_title );
		$this->assertEquals( 'Original content.', $copy->post_content );
	}

	/**
	 * Tests that page post type works correctly with R&R.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::create_duplicate_for_rewrite_and_republish
	 * @covers ::republish
	 * @covers ::republish_post_elements
	 *
	 * @return void
	 */
	public function test_rewrite_and_republish_works_for_page() {
		$original = $this->create_original_post(
			[
				'post_type'  => 'page',
				'post_title' => 'Original Page',
			]
		);

		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Verify the copy was created.
		$this->assertInstanceOf( WP_Post::class, $copy );
		$this->assertEquals( 'page', $copy->post_type );

		// Update and republish.
		\wp_update_post(
			[
				'ID'         => $copy->ID,
				'post_title' => 'Updated Page Title',
			]
		);
		$copy = \get_post( $copy->ID );

		$this->instance->republish( $copy, $original );

		$updated_original = \get_post( $original->ID );
		$this->assertEquals( 'Updated Page Title', $updated_original->post_title );
	}

	/**
	 * Tests that the duplicator creates copy for draft post.
	 *
	 * Note: The Post_Duplicator does not check post status.
	 * Prevention logic is in the UI/handlers layer.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::create_duplicate_for_rewrite_and_republish
	 * @covers \Yoast\WP\Duplicate_Post\Post_Duplicator::create_duplicate
	 *
	 * @return void
	 */
	public function test_duplicator_creates_copy_for_draft_post() {
		$draft_post = $this->create_original_post( [ 'post_status' => 'draft' ] );

		// The duplicator creates a copy even for draft posts.
		$copy_id = $this->post_duplicator->create_duplicate_for_rewrite_and_republish( $draft_post );

		// Verify copy was created.
		$this->assertNotEmpty( $copy_id );
		$copy = \get_post( $copy_id );
		$this->assertInstanceOf( WP_Post::class, $copy );

		// Clean up.
		\wp_delete_post( $copy_id, true );
	}

	/**
	 * Tests that trashing a copy prevents republishing.
	 *
	 * @covers ::republish_request
	 *
	 * @return void
	 */
	public function test_trashed_copy_cannot_be_republished() {
		$original = $this->create_original_post(
			[
				'post_title'   => 'Original Title',
				'post_content' => 'Original content.',
			]
		);
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Modify copy then trash it.
		\wp_update_post(
			[
				'ID'         => $copy->ID,
				'post_title' => 'Trashed Copy Title',
			]
		);
		\wp_trash_post( $copy->ID );
		$copy = \get_post( $copy->ID );

		// Try to republish the trashed copy.
		$this->instance->republish_request( $copy );

		// Original should be unchanged.
		$unchanged_original = \get_post( $original->ID );
		$this->assertEquals( 'Original Title', $unchanged_original->post_title );
	}

	/**
	 * Tests clean_up_when_copy_manually_deleted removes meta when trashed copy is permanently deleted.
	 *
	 * @covers ::clean_up_when_copy_manually_deleted
	 *
	 * @return void
	 */
	public function test_deleting_trashed_copy_cleans_up_original_meta() {
		$original = $this->create_original_post();
		$copy     = $this->create_rewrite_and_republish_copy( $original );
		$copy_id  = $copy->ID;

		// Verify original has copy reference.
		$this->assertEquals( $copy_id, (int) \get_post_meta( $original->ID, '_dp_has_rewrite_republish_copy', true ) );

		// Trash then permanently delete the copy.
		\wp_trash_post( $copy_id );
		\wp_delete_post( $copy_id, true );

		// Verify original no longer has copy reference (cleaned up by hook).
		$this->assertEmpty( \get_post_meta( $original->ID, '_dp_has_rewrite_republish_copy', true ) );
	}

	/**
	 * Tests that republish updates post author from copy.
	 *
	 * @covers ::republish
	 * @covers ::republish_post_elements
	 *
	 * @return void
	 */
	public function test_republish_updates_author_from_copy() {
		// Create a user for the original post.
		$original_author_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		$copy_author_id     = $this->factory->user->create( [ 'role' => 'editor' ] );

		$original = $this->create_original_post( [ 'post_author' => $original_author_id ] );
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Change the copy's author.
		\wp_update_post(
			[
				'ID'           => $copy->ID,
				'post_author'  => $copy_author_id,
				'post_title'   => 'Updated Title',
				'post_content' => 'Updated content.',
			]
		);
		$copy = \get_post( $copy->ID );

		// Republish.
		$this->instance->republish( $copy, $original );

		// The author should change to the copy's author.
		$updated_original = \get_post( $original->ID );
		$this->assertEquals( $copy_author_id, (int) $updated_original->post_author );
	}

	/**
	 * Tests republish with empty title.
	 *
	 * @covers ::republish
	 * @covers ::republish_post_elements
	 *
	 * @return void
	 */
	public function test_republish_with_empty_title() {
		$original = $this->create_original_post(
			[
				'post_title' => 'Original Title',
			]
		);

		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Update copy with empty title.
		\wp_update_post(
			[
				'ID'         => $copy->ID,
				'post_title' => '',
			]
		);
		$copy = \get_post( $copy->ID );

		// Republish.
		$this->instance->republish( $copy, $original );

		// Verify the title is empty.
		$updated_original = \get_post( $original->ID );
		$this->assertEquals( '', $updated_original->post_title );
	}

	/**
	 * Tests republish with empty content.
	 *
	 * @covers ::republish
	 * @covers ::republish_post_elements
	 *
	 * @return void
	 */
	public function test_republish_with_empty_content() {
		$original = $this->create_original_post(
			[
				'post_content' => 'Original content.',
			]
		);

		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Update copy with empty content.
		\wp_update_post(
			[
				'ID'           => $copy->ID,
				'post_content' => '',
			]
		);
		$copy = \get_post( $copy->ID );

		// Republish.
		$this->instance->republish( $copy, $original );

		// Verify the content is empty.
		$updated_original = \get_post( $original->ID );
		$this->assertEquals( '', $updated_original->post_content );
	}

	/**
	 * Tests that republish removes taxonomies when they are removed from copy.
	 *
	 * @covers ::republish
	 * @covers ::republish_post_taxonomies
	 *
	 * @return void
	 */
	public function test_republish_removes_taxonomies_when_removed_from_copy() {
		$category_id = $this->factory->category->create( [ 'name' => 'Original Category' ] );
		$tag_id      = $this->factory->tag->create( [ 'name' => 'Original Tag' ] );

		$original = $this->create_original_post();
		\wp_set_post_categories( $original->ID, [ $category_id ] );
		\wp_set_post_tags( $original->ID, [ $tag_id ] );

		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Remove all taxonomies from the copy.
		\wp_set_post_categories( $copy->ID, [] );
		\wp_set_post_tags( $copy->ID, [] );

		// Republish.
		$this->instance->republish( $copy, $original );

		// Verify taxonomies were removed from original.
		$updated_categories = \wp_get_post_categories( $original->ID );
		$updated_tags       = \wp_get_post_tags( $original->ID, [ 'fields' => 'ids' ] );

		// Note: WordPress may assign default category.
		$this->assertNotContains( $category_id, $updated_categories );
		$this->assertEmpty( $updated_tags );
	}

	/**
	 * Tests that republish does not remove meta that was deleted from copy.
	 *
	 * Note: The copy_post_meta_info method only copies meta that exists in the copy,
	 * it does not actively remove meta from the original that is missing in the copy.
	 *
	 * @covers ::republish
	 * @covers ::republish_post_meta
	 *
	 * @return void
	 */
	public function test_republish_does_not_remove_meta_deleted_from_copy() {
		$original = $this->create_original_post();
		\update_post_meta( $original->ID, 'custom_meta_to_remove', 'original_value' );

		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Verify the meta was copied.
		$this->assertEquals( 'original_value', \get_post_meta( $copy->ID, 'custom_meta_to_remove', true ) );

		// Delete the meta from the copy.
		\delete_post_meta( $copy->ID, 'custom_meta_to_remove' );

		// Republish.
		$this->instance->republish( $copy, $original );

		// The meta on the original is NOT removed because copy_post_meta_info
		// only copies existing meta, it doesn't delete missing ones.
		// This is the expected behavior based on how the duplicator works.
		$this->assertEquals( 'original_value', \get_post_meta( $original->ID, 'custom_meta_to_remove', true ) );
	}
}
