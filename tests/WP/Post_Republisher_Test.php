<?php

namespace Yoast\WP\Duplicate_Post\Tests\WP;

use WP_Post;
use WPDieException;
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
			],
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
			],
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
			],
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

		// Verify the copy is NOT deleted by republish() - deletion is handled separately.
		$this->assertNotNull( \get_post( $copy->ID ) );

		// Verify the copy was marked as republished.
		$this->assertEquals( '1', \get_post_meta( $copy->ID, '_dp_has_been_republished', true ) );
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
			],
		);

		$original_id = $original->ID;

		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Update copy content.
		\wp_update_post(
			[
				'ID'           => $copy->ID,
				'post_title'   => 'Scheduled Updated Title',
				'post_content' => 'Scheduled updated content.',
			],
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
			],
		);
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Update copy content and status to dp-rewrite-republish.
		$this->update_post_without_republish(
			[
				'ID'           => $copy->ID,
				'post_title'   => 'Republished Title',
				'post_content' => 'Republished content.',
				'post_status'  => 'dp-rewrite-republish',
			],
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
	 * Tests that republish works when copy has private status and updates original to private.
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
			],
		);
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Update copy content and status to private.
		$this->update_post_without_republish(
			[
				'ID'           => $copy->ID,
				'post_title'   => 'Private Copy Title',
				'post_content' => 'Private copy content.',
				'post_status'  => 'private',
			],
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
			],
		);
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Copy is a draft by default - modify it.
		\wp_update_post(
			[
				'ID'         => $copy->ID,
				'post_title' => 'Draft Copy Title',
			],
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
			],
		);
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Update copy to pending status.
		$this->update_post_without_republish(
			[
				'ID'           => $copy->ID,
				'post_title'   => 'Pending Copy Title',
				'post_status'  => 'pending',
			],
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
			],
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
			],
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
			],
		);
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Simulate time passing by explicitly updating the modified dates.
		$base_modified_gmt = ( $original->post_modified_gmt ) ? $original->post_modified_gmt : \get_gmt_from_date( $original->post_modified );
		$modified_time_gmt = \gmdate(
			'Y-m-d H:i:s',
			\strtotime( '+1 second', \strtotime( $base_modified_gmt ) ),
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
			],
		);

		// Modify the copy with different content.
		\wp_update_post(
			[
				'ID'           => $copy->ID,
				'post_title'   => 'Copy Title',
				'post_content' => 'Copy content.',
			],
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
			],
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
			],
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
			],
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
			],
		);
		$copy = \get_post( $copy->ID );

		$this->instance->republish( $copy, $original );

		$updated_original = \get_post( $original->ID );
		$this->assertEquals( 'Updated Page Title', $updated_original->post_title );
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
			],
		);
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Modify copy then trash it.
		\wp_update_post(
			[
				'ID'         => $copy->ID,
				'post_title' => 'Trashed Copy Title',
			],
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
			],
		);
		$copy = \get_post( $copy->ID );

		// Republish.
		$this->instance->republish( $copy, $original );

		// The author should change to the copy's author.
		$updated_original = \get_post( $original->ID );
		$this->assertEquals( $copy_author_id, (int) $updated_original->post_author );
	}

	/**
	 * Tests that republish removes taxonomies when they are removed from copy.
	 *
	 * @covers ::republish
	 * @covers ::republish_post_taxonomies
	 *
	 * @return void
	 */
	public function test_republish_replaces_taxonomies_from_copy() {
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
	 * Tests that republish_request dies when user cannot edit the original post.
	 *
	 * @covers ::republish_request
	 *
	 * @return void
	 */
	public function test_republish_request_dies_when_user_cannot_edit_original() {
		// Create an admin user who will own the original post.
		$admin_user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		\wp_set_current_user( $admin_user_id );

		$original = $this->create_original_post(
			[
				'post_title'   => 'Admin Post',
				'post_content' => 'Admin content.',
				'post_author'  => $admin_user_id,
			],
		);

		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Update copy status to dp-rewrite-republish.
		$this->update_post_without_republish(
			[
				'ID'          => $copy->ID,
				'post_status' => 'dp-rewrite-republish',
			],
		);
		$copy = \get_post( $copy->ID );

		// Switch to a contributor user who cannot edit the admin's post.
		$contributor_user_id = $this->factory->user->create( [ 'role' => 'contributor' ] );
		\wp_set_current_user( $contributor_user_id );

		// Verify the contributor cannot edit the original post.
		$this->assertFalse( \current_user_can( 'edit_post', $original->ID ) );

		// Expect wp_die to be called.
		$this->expectException( 'WPDieException' );

		$this->instance->republish_request( $copy );
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

	/**
	 * Tests the Rewrite & Republish workflow updates original content.
	 *
	 * Note: This test verifies that republish() updates the original post content.
	 * The copy deletion is handled separately by delete_copy() and tested in
	 * test_full_rewrite_and_republish_workflow_with_delete().
	 *
	 * @covers ::republish
	 * @covers ::republish_post_elements
	 * @covers ::republish_post_taxonomies
	 * @covers ::republish_post_meta
	 *
	 * @return void
	 */
	public function test_full_rewrite_and_republish_workflow() {
		// Step 1: Create and publish an original post.
		$original = $this->create_original_post(
			[
				'post_title'   => 'Original Title',
				'post_content' => 'Original content.',
				'post_status'  => 'publish',
			],
		);

		// Step 2: Create a Rewrite & Republish copy.
		$copy = $this->create_rewrite_and_republish_copy( $original );
		$this->assertInstanceOf( WP_Post::class, $copy );

		// Verify the relationship is established.
		$this->assertEquals( 1, (int) \get_post_meta( $copy->ID, '_dp_is_rewrite_republish_copy', true ) );
		$this->assertEquals( $copy->ID, (int) \get_post_meta( $original->ID, '_dp_has_rewrite_republish_copy', true ) );

		// Step 3: Edit the copy.
		\wp_update_post(
			[
				'ID'           => $copy->ID,
				'post_title'   => 'Rewritten Title',
				'post_content' => 'Rewritten content.',
			],
		);

		$copy = \get_post( $copy->ID );

		// Step 4: Republish the copy onto the original.
		$this->instance->republish( $copy, $original );

		// Step 5: Verify the original has the new content.
		$updated_original = \get_post( $original->ID );
		$this->assertEquals( 'Rewritten Title', $updated_original->post_title );
		$this->assertEquals( 'Rewritten content.', $updated_original->post_content );
		$this->assertEquals( 'publish', $updated_original->post_status );

		// Step 6: Verify the copy is NOT deleted by republish() - deletion is separate.
		$this->assertNotNull( \get_post( $copy->ID ) );

		// Verify the copy was marked as republished.
		$this->assertEquals( '1', \get_post_meta( $copy->ID, '_dp_has_been_republished', true ) );
	}

	/**
	 * Tests that republish completely replaces taxonomies (not just adds them).
	 *
	 * @covers ::republish
	 * @covers ::republish_post_taxonomies
	 *
	 * @return void
	 */
	public function test_republish_replaces_taxonomies_completely() {
		// Create categories.
		$cat1 = $this->factory->category->create( [ 'name' => 'Category 1' ] );
		$cat2 = $this->factory->category->create( [ 'name' => 'Category 2' ] );
		$cat3 = $this->factory->category->create( [ 'name' => 'Category 3' ] );

		// Create original post with category 1.
		$original = $this->create_original_post();
		\wp_set_post_categories( $original->ID, [ $cat1 ] );

		// Create a R&R copy.
		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Change the copy's categories to cat2 and cat3.
		\wp_set_post_categories( $copy->ID, [ $cat2, $cat3 ] );

		// Republish.
		$this->instance->republish( $copy, $original );

		// Verify the original now has cat2 and cat3, and NOT cat1.
		$original_categories = \wp_get_post_categories( $original->ID );
		$this->assertCount( 2, $original_categories );
		$this->assertContains( $cat2, $original_categories );
		$this->assertContains( $cat3, $original_categories );
		$this->assertNotContains( $cat1, $original_categories );
	}

	/**
	 * Tests that republish transfers new meta added to copy to the original.
	 *
	 * @covers ::republish
	 * @covers ::republish_post_meta
	 *
	 * @return void
	 */
	public function test_republish_transfers_new_meta_to_original() {
		// Create original post with custom meta.
		$original = $this->create_original_post();
		\update_post_meta( $original->ID, 'existing_meta_key', 'original_value' );

		// Create a R&R copy.
		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Verify the copy has the original meta.
		$this->assertEquals( 'original_value', \get_post_meta( $copy->ID, 'existing_meta_key', true ) );

		// Update the copy's existing meta and add new meta.
		\update_post_meta( $copy->ID, 'existing_meta_key', 'updated_value' );
		\update_post_meta( $copy->ID, 'new_meta_key', 'new_value' );

		// Republish.
		$this->instance->republish( $copy, $original );

		// Verify the original has the updated meta.
		$this->assertEquals( 'updated_value', \get_post_meta( $original->ID, 'existing_meta_key', true ) );

		// Verify the original also has the new meta.
		$this->assertEquals( 'new_value', \get_post_meta( $original->ID, 'new_meta_key', true ) );
	}

	/**
	 * Tests that republish preserves the original post slug even when title changes.
	 *
	 * @covers ::republish
	 * @covers ::republish_post_elements
	 *
	 * @return void
	 */
	public function test_republish_preserves_original_post_slug() {
		// Create original post with a specific slug.
		$original = $this->create_original_post(
			[
				'post_title' => 'Original Title',
				'post_name'  => 'original-slug',
			],
		);

		// Create a R&R copy.
		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Update copy with a completely different title.
		\wp_update_post(
			[
				'ID'         => $copy->ID,
				'post_title' => 'Completely Different Title',
			],
		);

		$copy = \get_post( $copy->ID );

		// Republish.
		$this->instance->republish( $copy, $original );

		// Refresh the original post.
		$updated_original = \get_post( $original->ID );

		// Verify the slug is preserved.
		$this->assertEquals( 'original-slug', $updated_original->post_name );
		// Verify the title is updated.
		$this->assertEquals( 'Completely Different Title', $updated_original->post_title );
	}

	/**
	 * Tests that republish handles a trashed original post correctly.
	 *
	 * @covers ::republish
	 * @covers ::republish_post_elements
	 * @covers ::determine_post_status
	 *
	 * @return void
	 */
	public function test_republish_with_trashed_original() {
		// Create original post.
		$original = $this->create_original_post(
			[
				'post_title'  => 'Original Title',
				'post_status' => 'publish',
			],
		);

		// Create a R&R copy.
		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Trash the original.
		\wp_trash_post( $original->ID );

		// Refresh the original.
		$original = \get_post( $original->ID );

		// Update copy.
		\wp_update_post(
			[
				'ID'         => $copy->ID,
				'post_title' => 'Updated Title',
			],
		);

		$copy = \get_post( $copy->ID );

		// Republish.
		$this->instance->republish( $copy, $original );

		// Refresh the original post.
		$updated_original = \get_post( $original->ID );

		// Verify the original remains trashed but has updated content.
		$this->assertEquals( 'trash', $updated_original->post_status );
		$this->assertEquals( 'Updated Title', $updated_original->post_title );
	}

	/**
	 * Tests that clean_up_when_copy_manually_deleted removes meta from original.
	 *
	 * @covers ::clean_up_when_copy_manually_deleted
	 *
	 * @return void
	 */
	public function test_clean_up_when_copy_manually_deleted_removes_original_meta() {
		// Create original post.
		$original = $this->create_original_post();

		// Create a R&R copy.
		$copy = $this->create_rewrite_and_republish_copy( $original );

		// Verify the meta exists.
		$this->assertEquals( $copy->ID, (int) \get_post_meta( $original->ID, '_dp_has_rewrite_republish_copy', true ) );

		// Simulate manual deletion by calling clean_up_when_copy_manually_deleted.
		$this->instance->clean_up_when_copy_manually_deleted( $copy->ID );

		// Verify the meta is removed from the original.
		$this->assertEmpty( \get_post_meta( $original->ID, '_dp_has_rewrite_republish_copy', true ) );
	}

	/**
	 * Tests that delete_copy fires the duplicate_post_after_rewriting action.
	 *
	 * @covers ::delete_copy
	 *
	 * @return void
	 */
	public function test_delete_copy_fires_action_before_deletion() {
		$original = $this->create_original_post();
		$copy     = $this->create_rewrite_and_republish_copy( $original );
		$copy_id  = $copy->ID;

		$action_fired  = false;
		$fired_copy_id = null;
		$fired_post_id = null;

		// Add a listener for the action.
		\add_action(
			'duplicate_post_after_rewriting',
			static function ( $copy_id, $post_id ) use ( &$action_fired, &$fired_copy_id, &$fired_post_id ) {
				$action_fired  = true;
				$fired_copy_id = $copy_id;
				$fired_post_id = $post_id;
			},
			10,
			2,
		);

		// Delete the copy.
		$this->instance->delete_copy( $copy_id, $original->ID );

		// Verify the action was fired.
		$this->assertTrue( $action_fired );
		$this->assertEquals( $copy_id, $fired_copy_id );
		$this->assertEquals( $original->ID, $fired_post_id );
	}

	/**
	 * Tests that delete_copy cleans up meta even if wp_delete_post fails.
	 *
	 * This verifies the behavior when wp_delete_post() fails during delete_copy().
	 * The meta cleanup should still happen even if the post is not actually deleted.
	 *
	 * @covers ::delete_copy
	 *
	 * @return void
	 */
	public function test_delete_copy_cleans_meta_even_if_wp_delete_post_fails() {
		$original = $this->create_original_post();
		$copy     = $this->create_rewrite_and_republish_copy( $original );
		$copy_id  = $copy->ID;

		// Verify the meta exists before deletion.
		$this->assertEquals( $copy_id, (int) \get_post_meta( $original->ID, '_dp_has_rewrite_republish_copy', true ) );

		// Prevent deletion by filtering pre_delete_post.
		$prevent_deletion = static function ( $delete, $post ) use ( $copy_id ) {
			if ( $post->ID === $copy_id ) {
				return false; // Prevent deletion.
			}
			return $delete;
		};
		\add_filter( 'pre_delete_post', $prevent_deletion, 10, 2 );

		// Call delete_copy - wp_delete_post will fail but meta cleanup should still happen.
		$this->instance->delete_copy( $copy_id, $original->ID );

		// Remove the filter.
		\remove_filter( 'pre_delete_post', $prevent_deletion, 10 );

		// Verify the copy still exists (because deletion failed).
		$still_existing_copy = \get_post( $copy_id );
		$this->assertNotNull( $still_existing_copy );

		// Verify the meta was still cleaned up from the original.
		$this->assertEmpty( \get_post_meta( $original->ID, '_dp_has_rewrite_republish_copy', true ) );

		// Clean up manually.
		\wp_delete_post( $copy_id, true );
	}

	/**
	 * Tests that duplicate_post_before_republish is fired before duplicate_post_after_republish.
	 *
	 * @covers ::republish
	 *
	 * @return void
	 */
	public function test_republish_fires_hooks_in_correct_order() {
		$action_order    = [];
		$before_callback = static function () use ( &$action_order ) {
			$action_order[] = 'before';
		};
		$after_callback  = static function () use ( &$action_order ) {
			$action_order[] = 'after';
		};

		\add_action( 'duplicate_post_before_republish', $before_callback );
		\add_action( 'duplicate_post_after_republish', $after_callback );

		$original = $this->create_original_post();
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		$this->instance->republish( $copy, $original );

		\remove_action( 'duplicate_post_before_republish', $before_callback );
		\remove_action( 'duplicate_post_after_republish', $after_callback );

		$this->assertSame( [ 'before', 'after' ], $action_order );
	}

	/**
	 * Tests that clean_up_after_redirect does nothing when no redirect parameters are present.
	 *
	 * @covers ::clean_up_after_redirect
	 *
	 * @return void
	 */
	public function test_clean_up_after_redirect_does_nothing_without_params() {
		// Ensure no $_GET parameters are set.
		unset( $_GET['dprepublished'], $_GET['post'], $_GET['dpnonce'] );

		// This should not throw any exception.
		$this->instance->clean_up_after_redirect();

		// If we get here without errors, the test passed.
		$this->assertTrue( true );
	}

	/**
	 * Tests that clean_up_after_redirect does nothing when only some redirect parameters are present.
	 *
	 * @covers ::clean_up_after_redirect
	 *
	 * @return void
	 */
	public function test_clean_up_after_redirect_does_nothing_with_partial_params() {
		// Set only dprepublished, missing post and dpnonce.
		$_GET['dprepublished'] = '1';
		unset( $_GET['post'], $_GET['dpnonce'] );

		// This should not throw any exception.
		$this->instance->clean_up_after_redirect();

		// Clean up.
		unset( $_GET['dprepublished'] );

		// If we get here without errors, the test passed.
		$this->assertTrue( true );
	}

	/**
	 * Tests that clean_up_after_redirect verifies the nonce when all redirect parameters are present.
	 *
	 * @covers ::clean_up_after_redirect
	 *
	 * @return void
	 */
	public function test_clean_up_after_redirect_verifies_nonce_with_valid_nonce() {
		$original = $this->create_original_post();

		// Set the redirect parameters with a valid nonce.
		$_GET['dprepublished'] = '1';
		$_GET['post']          = $original->ID;
		$_GET['dpnonce']       = \wp_create_nonce( 'dp-republish' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput,WordPress.Security.NonceVerification -- Setting up test data.
		$_REQUEST['dpnonce'] = $_GET['dpnonce'];

		// This should not throw any exception because the nonce is valid.
		$this->instance->clean_up_after_redirect();

		// Clean up.
		unset( $_GET['dprepublished'], $_GET['post'], $_GET['dpnonce'], $_REQUEST['dpnonce'] );

		// If we get here without errors, the test passed.
		$this->assertTrue( true );
	}

	/**
	 * Tests that clean_up_after_redirect dies with invalid nonce.
	 *
	 * @covers ::clean_up_after_redirect
	 *
	 * @return void
	 */
	public function test_clean_up_after_redirect_dies_with_invalid_nonce() {
		$original = $this->create_original_post();
		$copy     = $this->create_rewrite_and_republish_copy( $original );

		// Set the redirect parameters with an invalid nonce.
		$_GET['dprepublished'] = '1';
		$_GET['dpcopy']        = $copy->ID;
		$_GET['post']          = $original->ID;
		$_GET['dpnonce']       = 'invalid_nonce';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput,WordPress.Security.NonceVerification -- Setting up test data.
		$_REQUEST['dpnonce'] = $_GET['dpnonce'];

		// Expect wp_die to be called due to invalid nonce.
		$this->expectException( WPDieException::class );

		$this->instance->clean_up_after_redirect();

		// Clean up (may not be reached due to exception).
		unset( $_GET['dprepublished'], $_GET['dpcopy'], $_GET['post'], $_GET['dpnonce'], $_REQUEST['dpnonce'] );
	}
}
