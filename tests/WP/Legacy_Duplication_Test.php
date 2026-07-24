<?php

namespace Yoast\WP\Duplicate_Post\Tests\WP;

use WP_Post;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Characterization tests for the legacy duplication functions.
 *
 * These tests pin the behaviour of duplicate_post_create_duplicate() and its
 * after-copy handlers, so that refactors of the duplication code can prove
 * they preserve it. Any change to this file in a refactor is an explicit
 * statement of changed behaviour.
 */
final class Legacy_Duplication_Test extends TestCase {

	/**
	 * A 1x1 transparent PNG, hex encoded.
	 *
	 * @var string
	 */
	private const PNG_1X1 = '89504e470d0a1a0a0000000d49484452000000010000000108060000001f15c4890000000d4944415478da636460f85f0f0002870180eb47ba920000000049454e44ae426082';

	/**
	 * The file served by the fake_download() filter.
	 *
	 * @var string
	 */
	private $download_source = '';

	/**
	 * Original option values to restore after tests.
	 *
	 * @var array<string, mixed>
	 */
	private $original_options = [];

	/**
	 * Options to reset for each test.
	 *
	 * @var array<int, string>
	 */
	private static $options_to_reset = [
		'duplicate_post_copytitle',
		'duplicate_post_title_prefix',
		'duplicate_post_title_suffix',
		'duplicate_post_copycontent',
		'duplicate_post_copyexcerpt',
		'duplicate_post_copydate',
		'duplicate_post_copystatus',
		'duplicate_post_copyslug',
		'duplicate_post_copyauthor',
		'duplicate_post_copypassword',
		'duplicate_post_copymenuorder',
		'duplicate_post_increase_menu_order_by',
		'duplicate_post_copychildren',
		'duplicate_post_copyattachments',
		'duplicate_post_copycomments',
		'duplicate_post_copythumbnail',
		'duplicate_post_copytemplate',
		'duplicate_post_copyformat',
		'duplicate_post_types_enabled',
		'duplicate_post_blacklist',
		'duplicate_post_taxonomies_blacklist',
	];

	/**
	 * Setting up before the class.
	 *
	 * @return void
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		// Load admin-functions.php manually since is_admin() returns false in WP tests.
		// We simulate the admin context by setting the screen first.
		\set_current_screen( 'edit.php' );

		if ( ! \function_exists( 'duplicate_post_create_duplicate' ) ) {
			require_once \DUPLICATE_POST_PATH . 'admin-functions.php';
		}

		// Register the always-on handlers that are normally registered in admin_init.
		if ( ! \has_action( 'duplicate_post_after_duplicated', 'duplicate_post_copy_post_meta_info' ) ) {
			\add_action( 'duplicate_post_after_duplicated', 'duplicate_post_copy_post_meta_info', 10, 2 );
		}
		if ( ! \has_action( 'duplicate_post_after_duplicated', 'duplicate_post_copy_post_taxonomies' ) ) {
			\add_action( 'duplicate_post_after_duplicated', 'duplicate_post_copy_post_taxonomies', 50, 2 );
		}
	}

	/**
	 * Setting up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		\set_current_screen( 'edit.php' );

		foreach ( self::$options_to_reset as $option ) {
			$this->original_options[ $option ] = \get_option( $option );
		}

		\update_option( 'duplicate_post_types_enabled', [ 'post', 'page' ] );
		\update_option( 'duplicate_post_copytitle', '1' );
		\update_option( 'duplicate_post_title_prefix', '' );
		\update_option( 'duplicate_post_title_suffix', '' );
		\update_option( 'duplicate_post_copycontent', '1' );
		\update_option( 'duplicate_post_copyexcerpt', '1' );
		\update_option( 'duplicate_post_copydate', '0' );
		\update_option( 'duplicate_post_copystatus', '0' );
		\update_option( 'duplicate_post_copyslug', '0' );
		\update_option( 'duplicate_post_copyauthor', '0' );
		\update_option( 'duplicate_post_copypassword', '0' );
		\update_option( 'duplicate_post_copymenuorder', '1' );
		\update_option( 'duplicate_post_increase_menu_order_by', '' );
		\update_option( 'duplicate_post_copychildren', '0' );
		\update_option( 'duplicate_post_copyattachments', '0' );
		\update_option( 'duplicate_post_copycomments', '0' );
		\update_option( 'duplicate_post_copythumbnail', '1' );
		\update_option( 'duplicate_post_copytemplate', '1' );
		\update_option( 'duplicate_post_copyformat', '1' );
		\update_option( 'duplicate_post_blacklist', '' );
		\update_option( 'duplicate_post_taxonomies_blacklist', [] );

		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		\wp_set_current_user( $admin_id );
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		foreach ( $this->original_options as $option => $value ) {
			if ( $value === false ) {
				\delete_option( $option );
			}
			else {
				\update_option( $option, $value );
			}
		}

		parent::tear_down();
	}

	/**
	 * Helper to create an original post for duplication.
	 *
	 * @param array<string, mixed> $args Optional. Arguments for wp_insert_post.
	 *
	 * @return WP_Post The created post object.
	 */
	private function create_original_post( $args = [] ) {
		$defaults = [
			'post_title'   => 'Characterization original',
			'post_content' => 'Original content.',
			'post_status'  => 'publish',
			'post_type'    => 'post',
		];

		$post_id = $this->factory->post->create( \array_merge( $defaults, $args ) );

		return \get_post( $post_id );
	}

	/**
	 * Creates an attachment with a real file, attached to the given post.
	 *
	 * @param int $parent_id The parent post ID.
	 *
	 * @return int The attachment ID.
	 */
	private function create_real_attachment( $parent_id ) {
		$upload = \wp_upload_bits( 'dp-characterization.png', null, \hex2bin( self::PNG_1X1 ) );
		$this->assertFalse( $upload['error'] );

		$attachment_id = $this->factory->attachment->create_object(
			$upload['file'],
			$parent_id,
			[
				'post_mime_type' => 'image/png',
				'post_title'     => 'Attached image',
				'post_excerpt'   => 'Caption text',
				'post_content'   => 'Description text',
			],
		);
		\update_attached_file( $attachment_id, $upload['file'] );
		\update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Alt text' );

		return $attachment_id;
	}

	/**
	 * Serves $this->download_source for download_url(), as a pre_http_request filter.
	 *
	 * @param false|array<string, mixed> $response    The pre-filter response value.
	 * @param array<string, mixed>       $parsed_args The request arguments.
	 *
	 * @return array<string, mixed> The faked HTTP response.
	 */
	public function fake_download( $response, $parsed_args ) {
		if ( ! empty( $parsed_args['filename'] ) ) {
			\copy( $this->download_source, $parsed_args['filename'] );
		}

		return [
			'headers'  => [],
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'body'     => '',
			'cookies'  => [],
			'filename' => ( $parsed_args['filename'] ?? '' ),
		];
	}

	/**
	 * Tests that attachments are copied with their files, fields, alt text, and thumbnail status.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_attachments
	 *
	 * @return void
	 */
	public function test_copies_attachments_with_files_and_thumbnail() {
		\update_option( 'duplicate_post_copyattachments', '1' );
		\update_option( 'duplicate_post_copythumbnail', '1' );

		// Register the handlers the way the plugin does, so the option gate is exercised.
		\duplicate_post_admin_init();

		$original      = $this->create_original_post();
		$attachment_id = $this->create_real_attachment( $original->ID );
		\add_post_meta( $original->ID, '_thumbnail_id', $attachment_id );

		$this->download_source = \get_attached_file( $attachment_id );
		\add_filter( 'pre_http_request', [ $this, 'fake_download' ], 10, 2 );

		$new_id = \duplicate_post_create_duplicate( $original );

		\remove_filter( 'pre_http_request', [ $this, 'fake_download' ], 10 );

		$this->assertIsInt( $new_id );

		$new_attachments = \get_children(
			[
				'post_parent' => $new_id,
				'post_type'   => 'attachment',
			],
		);
		$this->assertCount( 1, $new_attachments );

		$new_attachment = \array_shift( $new_attachments );
		$this->assertSame( 'Attached image', $new_attachment->post_title );
		$this->assertSame( 'Caption text', $new_attachment->post_excerpt );
		$this->assertSame( 'Description text', $new_attachment->post_content );
		$this->assertSame( 'Alt text', \get_post_meta( $new_attachment->ID, '_wp_attachment_image_alt', true ) );
		$this->assertFileExists( \get_attached_file( $new_attachment->ID ) );

		// The copied attachment becomes the thumbnail of the copy.
		$this->assertSame( $new_attachment->ID, (int) \get_post_meta( $new_id, '_thumbnail_id', true ) );
	}

	/**
	 * Tests that attachments are not copied when the setting is disabled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_does_not_copy_attachments_when_disabled() {
		\update_option( 'duplicate_post_copyattachments', '0' );

		// Register the handlers the way the plugin does, so the option gate is exercised.
		\duplicate_post_admin_init();
		$this->assertFalse( \has_action( 'duplicate_post_after_duplicated', 'duplicate_post_copy_attachments' ) );

		$original = $this->create_original_post();
		$this->create_real_attachment( $original->ID );

		$new_id = \duplicate_post_create_duplicate( $original );

		$this->assertIsInt( $new_id );
		$this->assertCount(
			0,
			\get_children(
				[
					'post_parent' => $new_id,
					'post_type'   => 'attachment',
				],
			),
		);
	}

	/**
	 * Tests that copied comments keep their threading and that pingbacks are skipped.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_comments
	 *
	 * @return void
	 */
	public function test_copies_comments_with_threading_and_skips_pingbacks() {
		\update_option( 'duplicate_post_copycomments', '1' );
		\add_action( 'duplicate_post_after_duplicated', 'duplicate_post_copy_comments', 40, 2 );

		$original = $this->create_original_post();

		$parent_comment_id = $this->factory->comment->create(
			[
				'comment_post_ID'  => $original->ID,
				'comment_content'  => 'Parent comment',
				'comment_date'     => '2026-01-01 10:00:00',
				'comment_date_gmt' => '2026-01-01 10:00:00',
			],
		);
		$this->factory->comment->create(
			[
				'comment_post_ID'  => $original->ID,
				'comment_content'  => 'Reply comment',
				'comment_parent'   => $parent_comment_id,
				'comment_date'     => '2026-01-01 11:00:00',
				'comment_date_gmt' => '2026-01-01 11:00:00',
			],
		);
		$this->factory->comment->create(
			[
				'comment_post_ID'  => $original->ID,
				'comment_content'  => 'A pingback',
				'comment_type'     => 'pingback',
				'comment_date'     => '2026-01-01 12:00:00',
				'comment_date_gmt' => '2026-01-01 12:00:00',
			],
		);

		$new_id = \duplicate_post_create_duplicate( $original );

		\remove_action( 'duplicate_post_after_duplicated', 'duplicate_post_copy_comments', 40 );

		$new_comments = \get_comments( [ 'post_id' => $new_id ] );

		// Copies get the current timestamp, so identify them by content instead of by order.
		$by_content = [];
		foreach ( $new_comments as $new_comment ) {
			$by_content[ $new_comment->comment_content ] = $new_comment;
		}

		$this->assertCount( 2, $new_comments );
		$this->assertArrayHasKey( 'Parent comment', $by_content );
		$this->assertArrayHasKey( 'Reply comment', $by_content );
		$this->assertSame( (int) $by_content['Parent comment']->comment_ID, (int) $by_content['Reply comment']->comment_parent );
	}

	/**
	 * Tests that copied children include grandchildren via recursion.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_children
	 *
	 * @return void
	 */
	public function test_copies_grandchildren_recursively() {
		\update_option( 'duplicate_post_copychildren', '1' );
		\add_action( 'duplicate_post_after_duplicated', 'duplicate_post_copy_children', 20, 3 );

		$parent = $this->factory->post->create_and_get(
			[
				'post_type'   => 'page',
				'post_title'  => 'Level one',
				'post_status' => 'publish',
			],
		);
		$child  = $this->factory->post->create_and_get(
			[
				'post_type'   => 'page',
				'post_title'  => 'Level two',
				'post_parent' => $parent->ID,
				'post_status' => 'publish',
			],
		);
		$this->factory->post->create_and_get(
			[
				'post_type'   => 'page',
				'post_title'  => 'Level three',
				'post_parent' => $child->ID,
				'post_status' => 'publish',
			],
		);

		$new_parent_id = \duplicate_post_create_duplicate( $parent );

		\remove_action( 'duplicate_post_after_duplicated', 'duplicate_post_copy_children', 20 );

		$new_children = \get_posts(
			[
				'post_type'   => 'page',
				'post_status' => 'any',
				'post_parent' => $new_parent_id,
				'numberposts' => -1,
			],
		);
		$this->assertCount( 1, $new_children );
		$this->assertSame( 'Level two', $new_children[0]->post_title );

		$new_grandchildren = \get_posts(
			[
				'post_type'   => 'page',
				'post_status' => 'any',
				'post_parent' => $new_children[0]->ID,
				'numberposts' => -1,
			],
		);
		$this->assertCount( 1, $new_grandchildren );
		$this->assertSame( 'Level three', $new_grandchildren[0]->post_title );
	}

	/**
	 * Tests that a wildcard in the meta excludelist only excludes fully matching keys.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_post_meta_info
	 *
	 * @return void
	 */
	public function test_wildcard_meta_excludelist_is_anchored() {
		\update_option( 'duplicate_post_blacklist', 'custom_foo*, plain_key' );

		$original = $this->create_original_post();
		\add_post_meta( $original->ID, 'custom_food', 'excluded by wildcard' );
		\add_post_meta( $original->ID, 'acustom_foo', 'kept, prefix mismatch' );
		\add_post_meta( $original->ID, 'plain_key', 'excluded exactly' );
		\add_post_meta( $original->ID, 'xplain_key', 'kept, suffix lookalike' );

		$new_id = \duplicate_post_create_duplicate( $original );

		$this->assertSame( '', \get_post_meta( $new_id, 'custom_food', true ) );
		$this->assertSame( '', \get_post_meta( $new_id, 'plain_key', true ) );
		$this->assertSame( 'kept, prefix mismatch', \get_post_meta( $new_id, 'acustom_foo', true ) );
		$this->assertSame( 'kept, suffix lookalike', \get_post_meta( $new_id, 'xplain_key', true ) );
	}

	/**
	 * Tests that serialized and multi-value meta survive copying intact.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_post_meta_info
	 *
	 * @return void
	 */
	public function test_copies_serialized_and_multivalue_meta() {
		$original = $this->create_original_post();

		$array_value = [
			'level' => [ 'deep' => 'value' ],
			'slash' => 'O\'Brien',
		];
		\add_post_meta( $original->ID, 'array_meta', $array_value );
		\add_post_meta( $original->ID, 'multi_meta', 'first' );
		\add_post_meta( $original->ID, 'multi_meta', 'second' );

		$new_id = \duplicate_post_create_duplicate( $original );

		$this->assertSame( $array_value, \get_post_meta( $new_id, 'array_meta', true ) );
		$this->assertSame( [ 'first', 'second' ], \get_post_meta( $new_id, 'multi_meta', false ) );
	}

	/**
	 * Tests that the duplicate_post_excludelist_filter can exclude additional meta keys.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_post_meta_info
	 *
	 * @return void
	 */
	public function test_excludelist_filter_excludes_meta() {
		$original = $this->create_original_post();
		\add_post_meta( $original->ID, 'filtered_out', 'value' );
		\add_post_meta( $original->ID, 'kept_key', 'value' );

		$callback = static function ( $excludelist ) {
			$excludelist[] = 'filtered_out';
			return $excludelist;
		};
		\add_filter( 'duplicate_post_excludelist_filter', $callback );

		$new_id = \duplicate_post_create_duplicate( $original );

		\remove_filter( 'duplicate_post_excludelist_filter', $callback );

		$this->assertSame( '', \get_post_meta( $new_id, 'filtered_out', true ) );
		$this->assertSame( 'value', \get_post_meta( $new_id, 'kept_key', true ) );
	}

	/**
	 * Tests that the duplicate_post_meta_keys_filter receives the filtered keys and can drop keys.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_post_meta_info
	 *
	 * @return void
	 */
	public function test_meta_keys_filter_can_drop_keys() {
		$original = $this->create_original_post();
		\add_post_meta( $original->ID, 'dropped_by_filter', 'value' );
		\add_post_meta( $original->ID, 'kept_key', 'value' );

		$received = null;
		$callback = static function ( $meta_keys ) use ( &$received ) {
			$received = $meta_keys;
			return \array_values( \array_diff( $meta_keys, [ 'dropped_by_filter' ] ) );
		};
		\add_filter( 'duplicate_post_meta_keys_filter', $callback );

		$new_id = \duplicate_post_create_duplicate( $original );

		\remove_filter( 'duplicate_post_meta_keys_filter', $callback );

		$this->assertIsArray( $received );
		$this->assertContains( 'kept_key', $received );
		$this->assertSame( '', \get_post_meta( $new_id, 'dropped_by_filter', true ) );
		$this->assertSame( 'value', \get_post_meta( $new_id, 'kept_key', true ) );
	}

	/**
	 * Tests that the duplicate_post_taxonomies_excludelist_filter can exclude taxonomies.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_post_taxonomies
	 *
	 * @return void
	 */
	public function test_taxonomies_excludelist_filter_excludes_taxonomy() {
		$original = $this->create_original_post();
		\wp_set_post_tags( $original->ID, [ 'characterization-tag' ] );

		$callback = static function ( $excludelist ) {
			$excludelist[] = 'post_tag';
			return $excludelist;
		};
		\add_filter( 'duplicate_post_taxonomies_excludelist_filter', $callback );

		$new_id = \duplicate_post_create_duplicate( $original );

		\remove_filter( 'duplicate_post_taxonomies_excludelist_filter', $callback );

		$this->assertSame( [], \wp_get_post_tags( $new_id ) );
	}

	/**
	 * Tests that disabling copyformat keeps the post format off the copy.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_post_taxonomies
	 *
	 * @return void
	 */
	public function test_does_not_copy_post_format_when_disabled() {
		\update_option( 'duplicate_post_copyformat', '0' );

		$original = $this->create_original_post();
		\set_post_format( $original->ID, 'aside' );

		$new_id = \duplicate_post_create_duplicate( $original );

		$this->assertFalse( \get_post_format( $new_id ) );
	}

	/**
	 * Tests that the deprecated dp_duplicate_post hook still fires for posts.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_fires_deprecated_dp_duplicate_post_hook() {
		$this->setExpectedDeprecated( 'dp_duplicate_post' );

		$captured = [];
		$callback = static function ( $new_id, $post, $status ) use ( &$captured ) {
			$captured = [ $new_id, $post, $status ];
		};
		\add_action( 'dp_duplicate_post', $callback, 10, 3 );

		$original = $this->create_original_post();
		$new_id   = \duplicate_post_create_duplicate( $original, 'draft' );

		\remove_action( 'dp_duplicate_post', $callback, 10 );

		$this->assertSame( $new_id, $captured[0] );
		$this->assertSame( $original->ID, $captured[1]->ID );
		$this->assertSame( 'draft', $captured[2] );
	}

	/**
	 * Tests that the deprecated dp_duplicate_page hook still fires for pages.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_fires_deprecated_dp_duplicate_page_hook() {
		$this->setExpectedDeprecated( 'dp_duplicate_page' );

		$captured = [];
		$callback = static function ( $new_id ) use ( &$captured ) {
			$captured[] = $new_id;
		};
		\add_action( 'dp_duplicate_page', $callback );

		$original = $this->factory->post->create_and_get(
			[
				'post_type'   => 'page',
				'post_title'  => 'Characterization page',
				'post_status' => 'publish',
			],
		);
		$new_id   = \duplicate_post_create_duplicate( $original );

		\remove_action( 'dp_duplicate_page', $callback );

		$this->assertSame( [ $new_id ], $captured );
	}

	/**
	 * Tests that comment_status and ping_status are always copied.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_copies_comment_and_ping_status() {
		$original = $this->create_original_post(
			[
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			],
		);

		$new_id     = \duplicate_post_create_duplicate( $original );
		$duplicated = \get_post( $new_id );

		$this->assertSame( 'closed', $duplicated->comment_status );
		$this->assertSame( 'closed', $duplicated->ping_status );
	}

	/**
	 * Tests that an attachment can be duplicated even when its post type is not enabled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_duplicates_attachment_even_when_type_not_enabled() {
		\update_option( 'duplicate_post_types_enabled', [ 'post', 'page' ] );

		$original      = $this->create_original_post();
		$attachment_id = $this->create_real_attachment( $original->ID );

		$new_id = \duplicate_post_create_duplicate( \get_post( $attachment_id ) );

		$this->assertIsInt( $new_id );
		$duplicated = \get_post( $new_id );
		$this->assertSame( 'attachment', $duplicated->post_type );
		$this->assertSame( 'image/png', $duplicated->post_mime_type );
		$this->assertSame( $attachment_id, (int) \get_post_meta( $new_id, '_dp_original', true ) );
	}

	/**
	 * Tests that cloning a clone points _dp_original at the direct source, not the first original.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_clone_of_clone_points_to_direct_source() {
		$original = $this->create_original_post();

		$first_copy_id = \duplicate_post_create_duplicate( $original );
		$this->assertIsInt( $first_copy_id );

		$second_copy_id = \duplicate_post_create_duplicate( \get_post( $first_copy_id ) );
		$this->assertIsInt( $second_copy_id );

		$this->assertSame( $first_copy_id, (int) \get_post_meta( $second_copy_id, '_dp_original', true ) );
	}

	/**
	 * Tests that a disabled copystatus setting forces draft even when a status is passed.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_disabled_copystatus_overrides_status_parameter() {
		\update_option( 'duplicate_post_copystatus', '0' );

		$original = $this->create_original_post( [ 'post_status' => 'publish' ] );
		$new_id   = \duplicate_post_create_duplicate( $original, 'pending' );

		$this->assertIsInt( $new_id );
		$this->assertSame( 'draft', \get_post( $new_id )->post_status );
	}

	/**
	 * Tests that empty entries and stray spaces in the meta excludelist are handled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_post_meta_info
	 *
	 * @return void
	 */
	public function test_meta_excludelist_ignores_empty_entries() {
		\update_option( 'duplicate_post_blacklist', 'first_key,, second_key, ' );

		$original = $this->create_original_post();
		\add_post_meta( $original->ID, 'first_key', 'excluded' );
		\add_post_meta( $original->ID, 'second_key', 'excluded' );
		\add_post_meta( $original->ID, 'third_key', 'kept' );

		$new_id = \duplicate_post_create_duplicate( $original );

		$this->assertSame( '', \get_post_meta( $new_id, 'first_key', true ) );
		$this->assertSame( '', \get_post_meta( $new_id, 'second_key', true ) );
		$this->assertSame( 'kept', \get_post_meta( $new_id, 'third_key', true ) );
	}

	/**
	 * Tests that the internal Rewrite & Republish bookkeeping meta is copied to clones.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_post_meta_info
	 *
	 * @return void
	 */
	public function test_copies_republish_bookkeeping_meta() {
		$original = $this->create_original_post();
		\add_post_meta( $original->ID, '_dp_has_been_republished', '1' );
		\add_post_meta( $original->ID, '_dp_creation_date_gmt', '2026-01-01 10:00:00' );

		$new_id = \duplicate_post_create_duplicate( $original );

		$this->assertSame( '1', \get_post_meta( $new_id, '_dp_has_been_republished', true ) );
		$this->assertSame( '2026-01-01 10:00:00', \get_post_meta( $new_id, '_dp_creation_date_gmt', true ) );
	}

	/**
	 * Tests calling duplicate_post_copy_post_taxonomies() directly, respecting the excludelist option.
	 *
	 * @covers ::duplicate_post_copy_post_taxonomies
	 *
	 * @return void
	 */
	public function test_copy_post_taxonomies_direct_call() {
		\update_option( 'duplicate_post_taxonomies_blacklist', [ 'post_tag' ] );

		$original    = $this->create_original_post();
		$category_id = $this->factory->category->create( [ 'name' => 'Direct call category' ] );
		\wp_set_post_categories( $original->ID, [ $category_id ] );
		\wp_set_post_tags( $original->ID, [ 'direct-call-tag' ] );

		$target_id = $this->factory->post->create();

		\duplicate_post_copy_post_taxonomies( $target_id, $original );

		$this->assertContains( $category_id, \wp_get_post_categories( $target_id ) );
		$this->assertSame( [], \wp_get_post_tags( $target_id ) );
	}

	/**
	 * Tests calling duplicate_post_copy_post_meta_info() directly, respecting the excludelist option.
	 *
	 * @covers ::duplicate_post_copy_post_meta_info
	 *
	 * @return void
	 */
	public function test_copy_post_meta_info_direct_call() {
		\update_option( 'duplicate_post_blacklist', 'excluded_key' );

		$original = $this->create_original_post();
		\add_post_meta( $original->ID, 'excluded_key', 'value' );
		\add_post_meta( $original->ID, 'included_key', 'value' );

		$target_id = $this->factory->post->create();

		\duplicate_post_copy_post_meta_info( $target_id, $original );

		$this->assertSame( '', \get_post_meta( $target_id, 'excluded_key', true ) );
		$this->assertSame( 'value', \get_post_meta( $target_id, 'included_key', true ) );
	}
}
