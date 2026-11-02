<?php

namespace Yoast\WP\Duplicate_Post\Tests\WP;

use Yoast\WP\Duplicate_Post\Abilities;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Post_Duplicator;
use Yoast\WP\Duplicate_Post\Post_Republisher;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Class Abilities_Test.
 *
 * @coversDefaultClass \Yoast\WP\Duplicate_Post\Abilities
 */
final class Abilities_Test extends TestCase {

	/**
	 * Instance of the Abilities class.
	 *
	 * @var Abilities
	 */
	private $instance;

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	private $admin_user_id;

	/**
	 * Subscriber user ID.
	 *
	 * @var int
	 */
	private $subscriber_user_id;

	/**
	 * Setting up the instance of Abilities.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$permissions_helper = new Permissions_Helper();
		$post_duplicator    = new Post_Duplicator();

		$this->instance = new Abilities(
			$post_duplicator,
			new Post_Republisher( $post_duplicator, $permissions_helper ),
			$permissions_helper,
		);

		$this->admin_user_id      = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->subscriber_user_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );

		// The capability is normally added to the roles when the plugin is installed or upgraded.
		\get_role( 'administrator' )->add_cap( 'copy_posts' );

		\update_option( 'duplicate_post_types_enabled', [ 'post', 'page' ] );
		\update_option( 'duplicate_post_copytitle', '1' );
		\update_option( 'duplicate_post_copycontent', '1' );
		\update_option( 'duplicate_post_copyexcerpt', '1' );
		\update_option( 'duplicate_post_copystatus', '1' );
		\update_option( 'duplicate_post_title_prefix', '' );
		\update_option( 'duplicate_post_title_suffix', '' );
		\update_option( 'duplicate_post_blacklist', '' );
		\update_option( 'duplicate_post_taxonomies_blacklist', [] );
	}

	/**
	 * Cleaning up after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		\get_role( 'administrator' )->remove_cap( 'copy_posts' );
		\wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Tests that cloning a post creates a copy with the configured copy settings.
	 *
	 * @covers ::clone_post
	 * @covers ::can_clone
	 * @covers ::can_copy
	 * @covers ::can_copy_post_type
	 * @covers ::create_copy
	 * @covers ::get_post
	 * @covers ::prepare_post_response
	 *
	 * @return void
	 */
	public function test_clone_creates_a_copy() {
		\wp_set_current_user( $this->admin_user_id );
		\update_option( 'duplicate_post_title_prefix', 'Copy of' );

		$post_id = $this->factory->post->create(
			[
				'post_title'   => 'Original post',
				'post_content' => 'Original content',
				'post_status'  => 'publish',
			],
		);
		\wp_set_post_terms( $post_id, [ 'duplicated' ], 'post_tag' );
		\add_post_meta( $post_id, '_my_meta', 'meta value' );

		$input = [ 'post_id' => $post_id ];

		$this->assertTrue( $this->instance->can_clone( $input ) );

		$result = $this->instance->clone_post( $input );

		$this->assertIsArray( $result );
		$this->assertNotSame( $post_id, $result['post_id'] );
		$this->assertSame( 'Copy of Original post', $result['title'] );
		$this->assertSame( 'publish', $result['status'] );

		$copy = \get_post( $result['post_id'] );

		$this->assertSame( 'Original content', $copy->post_content );
		$this->assertSame( $post_id, (int) \get_post_meta( $copy->ID, '_dp_original', true ) );
		$this->assertSame( 'meta value', \get_post_meta( $copy->ID, '_my_meta', true ) );
		$this->assertSame( [ 'duplicated' ], \wp_get_post_terms( $copy->ID, 'post_tag', [ 'fields' => 'names' ] ) );
	}

	/**
	 * Tests that copying a post to a new draft creates a draft, regardless of the copy settings.
	 *
	 * @covers ::copy_to_new_draft
	 * @covers ::can_copy_to_new_draft
	 * @covers ::create_copy
	 *
	 * @return void
	 */
	public function test_copy_to_new_draft_creates_a_draft() {
		\wp_set_current_user( $this->admin_user_id );

		$post_id = $this->factory->post->create(
			[
				'post_title'  => 'Original post',
				'post_status' => 'publish',
			],
		);

		$input = [ 'post_id' => $post_id ];

		$this->assertTrue( $this->instance->can_copy_to_new_draft( $input ) );

		$result = $this->instance->copy_to_new_draft( $input );

		$this->assertIsArray( $result );
		$this->assertSame( 'draft', $result['status'] );
		$this->assertSame( 'Original post', $result['title'] );
	}

	/**
	 * Tests that a post which doesn't exist can't be copied.
	 *
	 * @covers ::can_clone
	 * @covers ::get_post
	 *
	 * @return void
	 */
	public function test_can_clone_returns_error_for_unknown_post() {
		\wp_set_current_user( $this->admin_user_id );

		$result = $this->instance->can_clone( [ 'post_id' => 123_456 ] );

		$this->assertWPError( $result );
		$this->assertSame( 'duplicate_post_post_not_found', $result->get_error_code() );
	}

	/**
	 * Tests that a user without the copy_posts capability can't copy a post.
	 *
	 * @covers ::can_clone
	 * @covers ::can_copy_post_type
	 *
	 * @return void
	 */
	public function test_can_clone_returns_error_for_user_without_capability() {
		\wp_set_current_user( $this->subscriber_user_id );

		$post_id = $this->factory->post->create( [ 'post_status' => 'publish' ] );

		$result = $this->instance->can_clone( [ 'post_id' => $post_id ] );

		$this->assertWPError( $result );
		$this->assertSame( 'duplicate_post_cannot_copy', $result->get_error_code() );
	}

	/**
	 * Tests that a post of a post type which is not enabled can't be copied.
	 *
	 * @covers ::can_clone
	 * @covers ::can_copy_post_type
	 *
	 * @return void
	 */
	public function test_can_clone_returns_error_for_disabled_post_type() {
		\wp_set_current_user( $this->admin_user_id );
		\update_option( 'duplicate_post_types_enabled', [ 'page' ] );

		$post_id = $this->factory->post->create( [ 'post_status' => 'publish' ] );

		$result = $this->instance->can_clone( [ 'post_id' => $post_id ] );

		$this->assertWPError( $result );
		$this->assertSame( 'duplicate_post_post_type_not_enabled', $result->get_error_code() );
	}

	/**
	 * Tests that a copy intended for Rewrite & Republish can't be copied.
	 *
	 * @covers ::can_clone
	 * @covers ::can_copy
	 *
	 * @return void
	 */
	public function test_can_clone_returns_error_for_rewrite_and_republish_copy() {
		\wp_set_current_user( $this->admin_user_id );

		$post_id = $this->factory->post->create( [ 'post_status' => 'publish' ] );
		\add_post_meta( $post_id, '_dp_is_rewrite_republish_copy', 1 );

		$result = $this->instance->can_clone( [ 'post_id' => $post_id ] );

		$this->assertWPError( $result );
		$this->assertSame( 'duplicate_post_cannot_copy', $result->get_error_code() );
	}

	/**
	 * Tests that the duplicate_post_allow filter blocks copying a post.
	 *
	 * @covers ::can_copy
	 *
	 * @return void
	 */
	public function test_can_clone_respects_the_duplicate_post_allow_filter() {
		\wp_set_current_user( $this->admin_user_id );

		$post_id = $this->factory->post->create( [ 'post_status' => 'publish' ] );

		\add_filter( 'duplicate_post_allow', '__return_false' );
		$result = $this->instance->can_clone( [ 'post_id' => $post_id ] );
		\remove_filter( 'duplicate_post_allow', '__return_false' );

		$this->assertWPError( $result );
		$this->assertSame( 'duplicate_post_cannot_copy', $result->get_error_code() );
	}

	/**
	 * Tests that rewriting a published post creates a Rewrite & Republish copy.
	 *
	 * @covers ::rewrite
	 * @covers ::can_rewrite
	 *
	 * @return void
	 */
	public function test_rewrite_creates_a_rewrite_and_republish_copy() {
		\wp_set_current_user( $this->admin_user_id );

		$post_id = $this->factory->post->create(
			[
				'post_title'  => 'Original post',
				'post_status' => 'publish',
			],
		);

		$input = [ 'post_id' => $post_id ];

		$this->assertTrue( $this->instance->can_rewrite( $input ) );

		$result = $this->instance->rewrite( $input );

		$this->assertIsArray( $result );
		$this->assertSame( 1, (int) \get_post_meta( $result['post_id'], '_dp_is_rewrite_republish_copy', true ) );
		$this->assertSame( $post_id, (int) \get_post_meta( $result['post_id'], '_dp_original', true ) );
		$this->assertSame( $result['post_id'], (int) \get_post_meta( $post_id, '_dp_has_rewrite_republish_copy', true ) );

		// A post can only have one Rewrite & Republish copy at a time.
		$this->assertWPError( $this->instance->can_rewrite( $input ) );
	}

	/**
	 * Tests that a post which is not published can't be rewritten.
	 *
	 * @covers ::can_rewrite
	 *
	 * @return void
	 */
	public function test_can_rewrite_returns_error_for_draft() {
		\wp_set_current_user( $this->admin_user_id );

		$post_id = $this->factory->post->create( [ 'post_status' => 'draft' ] );

		$result = $this->instance->can_rewrite( [ 'post_id' => $post_id ] );

		$this->assertWPError( $result );
		$this->assertSame( 'duplicate_post_rewrite_not_allowed', $result->get_error_code() );
	}

	/**
	 * Tests that republishing overwrites the original post and removes the copy.
	 *
	 * @covers ::republish
	 * @covers ::can_republish
	 * @covers ::get_rewrite_and_republish_copy
	 *
	 * @return void
	 */
	public function test_republish_overwrites_the_original() {
		\wp_set_current_user( $this->admin_user_id );

		$post_id = $this->factory->post->create(
			[
				'post_title'   => 'Original post',
				'post_content' => 'Original content',
				'post_status'  => 'publish',
			],
		);

		$copy_id = $this->instance->rewrite( [ 'post_id' => $post_id ] )['post_id'];

		\wp_update_post(
			[
				'ID'           => $copy_id,
				'post_title'   => 'Rewritten post',
				'post_content' => 'Rewritten content',
			],
		);

		$input = [ 'post_id' => $copy_id ];

		$this->assertTrue( $this->instance->can_republish( $input ) );

		$result = $this->instance->republish( $input );

		$this->assertIsArray( $result );
		$this->assertSame( $post_id, $result['post_id'] );
		$this->assertSame( 'Rewritten post', $result['title'] );
		$this->assertSame( 'publish', $result['status'] );

		$original = \get_post( $post_id );

		$this->assertSame( 'Rewritten content', $original->post_content );
		$this->assertNull( \get_post( $copy_id ) );
		$this->assertSame( '', \get_post_meta( $post_id, '_dp_has_rewrite_republish_copy', true ) );
	}

	/**
	 * Tests that republishing can be requested with the ID of the original post.
	 *
	 * @covers ::republish
	 * @covers ::get_rewrite_and_republish_copy
	 *
	 * @return void
	 */
	public function test_republish_accepts_the_id_of_the_original_post() {
		\wp_set_current_user( $this->admin_user_id );

		$post_id = $this->factory->post->create(
			[
				'post_title'  => 'Original post',
				'post_status' => 'publish',
			],
		);

		$copy_id = $this->instance->rewrite( [ 'post_id' => $post_id ] )['post_id'];

		\wp_update_post(
			[
				'ID'         => $copy_id,
				'post_title' => 'Rewritten post',
			],
		);

		$result = $this->instance->republish( [ 'post_id' => $post_id ] );

		$this->assertIsArray( $result );
		$this->assertSame( $post_id, $result['post_id'] );
		$this->assertSame( 'Rewritten post', $result['title'] );
	}

	/**
	 * Tests that a post without a Rewrite & Republish copy can't be republished.
	 *
	 * @covers ::can_republish
	 * @covers ::get_rewrite_and_republish_copy
	 *
	 * @return void
	 */
	public function test_can_republish_returns_error_without_copy() {
		\wp_set_current_user( $this->admin_user_id );

		$post_id = $this->factory->post->create( [ 'post_status' => 'publish' ] );

		$result = $this->instance->can_republish( [ 'post_id' => $post_id ] );

		$this->assertWPError( $result );
		$this->assertSame( 'duplicate_post_copy_not_found', $result->get_error_code() );
	}

	/**
	 * Tests that a user who can't edit the original post can't republish its copy.
	 *
	 * @covers ::can_republish
	 *
	 * @return void
	 */
	public function test_can_republish_returns_error_for_user_without_capability() {
		\wp_set_current_user( $this->admin_user_id );

		$post_id = $this->factory->post->create( [ 'post_status' => 'publish' ] );
		$copy_id = $this->instance->rewrite( [ 'post_id' => $post_id ] )['post_id'];

		\wp_set_current_user( $this->subscriber_user_id );

		$result = $this->instance->can_republish( [ 'post_id' => $copy_id ] );

		$this->assertWPError( $result );
		$this->assertSame( 'duplicate_post_cannot_republish', $result->get_error_code() );
	}
}
