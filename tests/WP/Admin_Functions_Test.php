<?php

namespace Yoast\WP\Duplicate_Post\Tests\WP;

use WP_Post;
use WPDieException;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Integration tests for the duplicate_post_create_duplicate() function.
 */
final class Admin_Functions_Test extends TestCase {

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

		// Now we need to load the admin functions file.
		// The file has a `if ( ! is_admin() ) { return; }` guard, but set_current_screen makes is_admin() return true.
		if ( ! \function_exists( 'duplicate_post_create_duplicate' ) ) {
			require_once \DUPLICATE_POST_PATH . 'admin-functions.php';
		}

		// Register the hooks that are normally registered in admin_init.
		// These are always needed for duplication to work properly.
		if ( ! \has_action( 'dp_duplicate_post', 'duplicate_post_copy_post_meta_info' ) ) {
			\add_action( 'dp_duplicate_post', 'duplicate_post_copy_post_meta_info', 10, 2 );
		}
		if ( ! \has_action( 'dp_duplicate_page', 'duplicate_post_copy_post_meta_info' ) ) {
			\add_action( 'dp_duplicate_page', 'duplicate_post_copy_post_meta_info', 10, 2 );
		}
		if ( ! \has_action( 'dp_duplicate_post', 'duplicate_post_copy_post_taxonomies' ) ) {
			\add_action( 'dp_duplicate_post', 'duplicate_post_copy_post_taxonomies', 50, 2 );
		}
		if ( ! \has_action( 'dp_duplicate_page', 'duplicate_post_copy_post_taxonomies' ) ) {
			\add_action( 'dp_duplicate_page', 'duplicate_post_copy_post_taxonomies', 50, 2 );
		}
	}

	/**
	 * Setting up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		// Store original options.
		foreach ( self::$options_to_reset as $option ) {
			$this->original_options[ $option ] = \get_option( $option );
		}

		// Set default options for testing.
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
		\update_option( 'duplicate_post_copycomments', '0' );
		\update_option( 'duplicate_post_copythumbnail', '1' );
		\update_option( 'duplicate_post_copytemplate', '1' );
		\update_option( 'duplicate_post_copyformat', '1' );
		\update_option( 'duplicate_post_blacklist', '' );
		\update_option( 'duplicate_post_taxonomies_blacklist', [] );

		// Set current user as administrator to have all capabilities.
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		\wp_set_current_user( $admin_id );
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		// Restore original options.
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
			'post_title'    => 'Original Title',
			'post_content'  => 'Original content.',
			'post_excerpt'  => 'Original excerpt.',
			'post_status'   => 'publish',
			'post_type'     => 'post',
			'post_password' => '',
			'menu_order'    => 5,
			'post_name'     => 'original-slug',
		];

		$post_id = $this->factory->post->create( \array_merge( $defaults, $args ) );

		return \get_post( $post_id );
	}

	/**
	 * Data provider for title copy tests.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function data_provider_title_settings() {
		return [
			'copy title enabled' => [
				'settings'       => [
					'duplicate_post_copytitle'    => '1',
					'duplicate_post_title_prefix' => '',
					'duplicate_post_title_suffix' => '',
				],
				'expected_title' => 'Original Title',
			],
			'copy title disabled' => [
				'settings'       => [
					'duplicate_post_copytitle'    => '0',
					'duplicate_post_title_prefix' => '',
					'duplicate_post_title_suffix' => '',
				],
				'expected_title' => '',
			],
			'copy title with prefix' => [
				'settings'       => [
					'duplicate_post_copytitle'    => '1',
					'duplicate_post_title_prefix' => 'Copy of',
					'duplicate_post_title_suffix' => '',
				],
				'expected_title' => 'Copy of Original Title',
			],
			'copy title with suffix' => [
				'settings'       => [
					'duplicate_post_copytitle'    => '1',
					'duplicate_post_title_prefix' => '',
					'duplicate_post_title_suffix' => '(duplicate)',
				],
				'expected_title' => 'Original Title (duplicate)',
			],
			'copy title with prefix and suffix' => [
				'settings'       => [
					'duplicate_post_copytitle'    => '1',
					'duplicate_post_title_prefix' => 'Copy:',
					'duplicate_post_title_suffix' => '- v2',
				],
				'expected_title' => 'Copy: Original Title - v2',
			],
			'title disabled with prefix still shows prefix' => [
				'settings'       => [
					'duplicate_post_copytitle'    => '0',
					'duplicate_post_title_prefix' => 'Copy',
					'duplicate_post_title_suffix' => '',
				],
				'expected_title' => 'Copy',
			],
		];
	}

	/**
	 * Tests duplicate_post_create_duplicate with different title settings.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @dataProvider data_provider_title_settings
	 *
	 * @param array<string, string> $settings       The options to set.
	 * @param string                $expected_title The expected title of the duplicate.
	 *
	 * @return void
	 */
	public function test_create_duplicate_title_settings( $settings, $expected_title ) {
		foreach ( $settings as $option => $value ) {
			\update_option( $option, $value );
		}

		$original   = $this->create_original_post();
		$new_id     = \duplicate_post_create_duplicate( $original );
		$duplicated = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		$this->assertEquals( $expected_title, $duplicated->post_title );
	}

	/**
	 * Data provider for content and excerpt copy tests.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function data_provider_content_excerpt_settings() {
		return [
			'copy both content and excerpt'    => [
				'settings'         => [
					'duplicate_post_copycontent' => '1',
					'duplicate_post_copyexcerpt' => '1',
				],
				'expected_content' => 'Original content.',
				'expected_excerpt' => 'Original excerpt.',
			],
			'copy content only'                => [
				'settings'         => [
					'duplicate_post_copycontent' => '1',
					'duplicate_post_copyexcerpt' => '0',
				],
				'expected_content' => 'Original content.',
				'expected_excerpt' => '',
			],
			'copy excerpt only'                => [
				'settings'         => [
					'duplicate_post_copycontent' => '0',
					'duplicate_post_copyexcerpt' => '1',
				],
				'expected_content' => '',
				'expected_excerpt' => 'Original excerpt.',
			],
			'copy neither content nor excerpt' => [
				'settings'         => [
					'duplicate_post_copycontent' => '0',
					'duplicate_post_copyexcerpt' => '0',
				],
				'expected_content' => '',
				'expected_excerpt' => '',
			],
		];
	}

	/**
	 * Tests duplicate_post_create_duplicate with different content and excerpt settings.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @dataProvider data_provider_content_excerpt_settings
	 *
	 * @param array<string, string> $settings         The options to set.
	 * @param string                $expected_content The expected content of the duplicate.
	 * @param string                $expected_excerpt The expected excerpt of the duplicate.
	 *
	 * @return void
	 */
	public function test_create_duplicate_content_excerpt_settings( $settings, $expected_content, $expected_excerpt ) {
		foreach ( $settings as $option => $value ) {
			\update_option( $option, $value );
		}

		$original   = $this->create_original_post();
		$new_id     = \duplicate_post_create_duplicate( $original );
		$duplicated = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		$this->assertEquals( $expected_content, $duplicated->post_content );
		$this->assertEquals( $expected_excerpt, $duplicated->post_excerpt );
	}

	/**
	 * Tests that duplicate_post_create_duplicate copies the date when enabled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_copies_date_when_enabled() {
		\update_option( 'duplicate_post_copydate', '1' );

		$original_date = '2025-06-15 10:30:00';
		$original      = $this->create_original_post( [ 'post_date' => $original_date ] );
		$new_id        = \duplicate_post_create_duplicate( $original );
		$duplicated    = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		$this->assertEquals( $original_date, $duplicated->post_date );
	}

	/**
	 * Tests that duplicate_post_create_duplicate does not copy the date when disabled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_does_not_copy_date_when_disabled() {
		\update_option( 'duplicate_post_copydate', '0' );

		$original_date = '2020-01-01 10:00:00';
		$original      = $this->create_original_post( [ 'post_date' => $original_date ] );
		$new_id        = \duplicate_post_create_duplicate( $original );
		$duplicated    = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		$this->assertNotEquals( $original_date, $duplicated->post_date );
	}

	/**
	 * Data provider for status copy tests.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function data_provider_status_settings() {
		return [
			'copy status disabled - always draft' => [
				'copy_status'     => '0',
				'original_status' => 'publish',
				'expected_status' => 'draft',
			],
			'copy status enabled - publish'       => [
				'copy_status'     => '1',
				'original_status' => 'publish',
				'expected_status' => 'publish',
			],
			'copy status enabled - draft'         => [
				'copy_status'     => '1',
				'original_status' => 'draft',
				'expected_status' => 'draft',
			],
			'copy status enabled - pending'       => [
				'copy_status'     => '1',
				'original_status' => 'pending',
				'expected_status' => 'pending',
			],
			'copy status enabled - private'       => [
				'copy_status'     => '1',
				'original_status' => 'private',
				'expected_status' => 'private',
			],
		];
	}

	/**
	 * Tests duplicate_post_create_duplicate with different status settings.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @dataProvider data_provider_status_settings
	 *
	 * @param string $copy_status     Whether to copy status.
	 * @param string $original_status The original post status.
	 * @param string $expected_status The expected status of the duplicate.
	 *
	 * @return void
	 */
	public function test_create_duplicate_status_settings( $copy_status, $original_status, $expected_status ) {
		\update_option( 'duplicate_post_copystatus', $copy_status );

		$original   = $this->create_original_post( [ 'post_status' => $original_status ] );
		$new_id     = \duplicate_post_create_duplicate( $original );
		$duplicated = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		$this->assertEquals( $expected_status, $duplicated->post_status );
	}

	/**
	 * Tests that duplicate_post_create_duplicate copies the slug when enabled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_copies_slug_when_enabled() {
		\update_option( 'duplicate_post_copyslug', '1' );

		$original   = $this->create_original_post( [ 'post_name' => 'my-custom-slug' ] );
		$new_id     = \duplicate_post_create_duplicate( $original );
		$duplicated = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		// WordPress may append a number to make the slug unique.
		$this->assertStringStartsWith( 'my-custom-slug', $duplicated->post_name );
	}

	/**
	 * Tests that duplicate_post_create_duplicate does not copy the slug when disabled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_does_not_copy_slug_when_disabled() {
		\update_option( 'duplicate_post_copyslug', '0' );

		$original   = $this->create_original_post( [ 'post_name' => 'my-custom-slug' ] );
		$new_id     = \duplicate_post_create_duplicate( $original );
		$duplicated = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		// When slug is not copied, WP generates a new one based on the title.
		$this->assertNotEquals( 'my-custom-slug', $duplicated->post_name );
	}

	/**
	 * Tests that duplicate_post_create_duplicate copies the author when enabled (admin user).
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_copies_author_when_enabled_as_admin() {
		\update_option( 'duplicate_post_copyauthor', '1' );

		// Create original post with a different author.
		$other_author_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		$original        = $this->create_original_post( [ 'post_author' => $other_author_id ] );

		$new_id     = \duplicate_post_create_duplicate( $original );
		$duplicated = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		$this->assertEquals( $other_author_id, (int) $duplicated->post_author );
	}

	/**
	 * Tests that duplicate_post_create_duplicate assigns current user as author when disabled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_uses_current_user_as_author_when_disabled() {
		\update_option( 'duplicate_post_copyauthor', '0' );

		$current_user_id = \get_current_user_id();
		$other_author_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		$original        = $this->create_original_post( [ 'post_author' => $other_author_id ] );

		$new_id     = \duplicate_post_create_duplicate( $original );
		$duplicated = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		$this->assertEquals( $current_user_id, (int) $duplicated->post_author );
	}

	/**
	 * Tests that editor cannot copy author without edit_others_posts capability.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_editor_cannot_copy_author_of_others_posts() {
		\update_option( 'duplicate_post_copyauthor', '1' );

		// Create a contributor user (no edit_others_posts capability).
		$contributor_id = $this->factory->user->create( [ 'role' => 'contributor' ] );
		\wp_set_current_user( $contributor_id );

		// Create original post with a different author.
		$other_author_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		$original        = $this->create_original_post( [ 'post_author' => $other_author_id ] );

		$new_id     = \duplicate_post_create_duplicate( $original );
		$duplicated = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		// Should use current user (contributor), not original author.
		$this->assertEquals( $contributor_id, (int) $duplicated->post_author );
	}

	/**
	 * Tests that contributor gets pending status when copying published post with copystatus enabled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_contributor_cannot_publish() {
		\update_option( 'duplicate_post_copystatus', '1' );

		// Create a contributor user (no publish_posts capability).
		$contributor_id = $this->factory->user->create( [ 'role' => 'contributor' ] );
		\wp_set_current_user( $contributor_id );

		$original   = $this->create_original_post( [ 'post_status' => 'publish' ] );
		$new_id     = \duplicate_post_create_duplicate( $original );
		$duplicated = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		// Should be pending because contributor cannot publish.
		$this->assertEquals( 'pending', $duplicated->post_status );
	}

	/**
	 * Tests that duplicate_post_create_duplicate copies the password when enabled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_copies_password_when_enabled() {
		\update_option( 'duplicate_post_copypassword', '1' );

		$original   = $this->create_original_post( [ 'post_password' => 'secret123' ] );
		$new_id     = \duplicate_post_create_duplicate( $original );
		$duplicated = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		$this->assertEquals( 'secret123', $duplicated->post_password );
	}

	/**
	 * Tests that duplicate_post_create_duplicate does not copy the password when disabled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_does_not_copy_password_when_disabled() {
		\update_option( 'duplicate_post_copypassword', '0' );

		$original   = $this->create_original_post( [ 'post_password' => 'secret123' ] );
		$new_id     = \duplicate_post_create_duplicate( $original );
		$duplicated = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		$this->assertEquals( '', $duplicated->post_password );
	}

	/**
	 * Data provider for menu order copy tests.
	 *
	 * @return array<string, array<string, int|string>>
	 */
	public static function data_provider_menu_order_settings() {
		return [
			'copy menu order enabled'           => [
				'copy_menu_order' => '1',
				'increase_by'     => '',
				'original_order'  => 10,
				'expected_order'  => 10,
			],
			'copy menu order disabled'          => [
				'copy_menu_order' => '0',
				'increase_by'     => '',
				'original_order'  => 10,
				'expected_order'  => 0,
			],
			'copy menu order with increase'     => [
				'copy_menu_order' => '1',
				'increase_by'     => '5',
				'original_order'  => 10,
				'expected_order'  => 15,
			],
			'menu order disabled with increase' => [
				'copy_menu_order' => '0',
				'increase_by'     => '5',
				'original_order'  => 10,
				'expected_order'  => 5,
			],
		];
	}

	/**
	 * Tests duplicate_post_create_duplicate with different menu order settings.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @dataProvider data_provider_menu_order_settings
	 *
	 * @param string $copy_menu_order Whether to copy menu order.
	 * @param string $increase_by     Amount to increase menu order by.
	 * @param int    $original_order  The original menu order.
	 * @param int    $expected_order  The expected menu order of the duplicate.
	 *
	 * @return void
	 */
	public function test_create_duplicate_menu_order_settings( $copy_menu_order, $increase_by, $original_order, $expected_order ) {
		\update_option( 'duplicate_post_copymenuorder', $copy_menu_order );
		\update_option( 'duplicate_post_increase_menu_order_by', $increase_by );

		$original   = $this->create_original_post( [ 'menu_order' => $original_order ] );
		$new_id     = \duplicate_post_create_duplicate( $original );
		$duplicated = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		$this->assertEquals( $expected_order, $duplicated->menu_order );
	}

	/**
	 * Tests that duplicate_post_create_duplicate sets the _dp_original meta.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_sets_original_meta() {
		$original = $this->create_original_post();
		$new_id   = \duplicate_post_create_duplicate( $original );

		$this->assertIsInt( $new_id );
		$this->assertEquals( $original->ID, (int) \get_post_meta( $new_id, '_dp_original', true ) );
	}

	/**
	 * Tests that duplicate_post_create_duplicate respects the status parameter.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_respects_status_parameter() {
		// copystatus must be enabled for the status parameter to be respected.
		\update_option( 'duplicate_post_copystatus', '1' );

		$original   = $this->create_original_post( [ 'post_status' => 'publish' ] );
		$new_id     = \duplicate_post_create_duplicate( $original, 'pending' );
		$duplicated = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		$this->assertEquals( 'pending', $duplicated->post_status );
	}

	/**
	 * Tests that duplicate_post_create_duplicate respects the parent_id parameter.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_respects_parent_id_parameter() {
		$parent   = $this->create_original_post( [ 'post_title' => 'Parent Post' ] );
		$original = $this->create_original_post( [ 'post_title' => 'Child Post' ] );

		$new_id     = \duplicate_post_create_duplicate( $original, '', $parent->ID );
		$duplicated = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		$this->assertEquals( $parent->ID, $duplicated->post_parent );
	}

	/**
	 * Tests that duplicate_post_create_duplicate fails for disabled post types.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_fails_for_disabled_post_type() {
		// Register a custom post type.
		\register_post_type( 'custom_type', [ 'public' => true ] );

		// Only enable 'post' and 'page'.
		\update_option( 'duplicate_post_types_enabled', [ 'post', 'page' ] );

		$original = $this->factory->post->create_and_get( [ 'post_type' => 'custom_type' ] );

		// This should call wp_die(), we catch it.
		$this->expectException( WPDieException::class );

		\duplicate_post_create_duplicate( $original );
	}

	/**
	 * Tests that duplicate_post_create_duplicate copies taxonomies via hook.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_post_taxonomies
	 *
	 * @return void
	 */
	public function test_create_duplicate_copies_taxonomies() {
		$category_id = $this->factory->category->create( [ 'name' => 'Test Category' ] );
		$this->factory->tag->create( [ 'name' => 'Test Tag' ] );

		$original = $this->create_original_post();
		\wp_set_post_categories( $original->ID, [ $category_id ] );
		\wp_set_post_tags( $original->ID, [ 'Test Tag' ] );

		$new_id = \duplicate_post_create_duplicate( $original );

		$new_categories = \wp_get_post_categories( $new_id );
		$new_tags       = \wp_get_post_tags( $new_id );

		$this->assertContains( $category_id, $new_categories );
		$this->assertEquals( 'Test Tag', $new_tags[0]->name );
	}

	/**
	 * Tests that duplicate_post_create_duplicate copies post meta via hook.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_post_meta_info
	 *
	 * @return void
	 */
	public function test_create_duplicate_copies_post_meta() {
		$original = $this->create_original_post();
		\add_post_meta( $original->ID, 'custom_meta_key', 'custom_meta_value' );

		$new_id = \duplicate_post_create_duplicate( $original );

		$this->assertEquals( 'custom_meta_value', \get_post_meta( $new_id, 'custom_meta_key', true ) );
	}

	/**
	 * Tests that duplicate_post_create_duplicate respects meta blocklist.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_post_meta_info
	 *
	 * @return void
	 */
	public function test_create_duplicate_respects_meta_blocklist() {
		\update_option( 'duplicate_post_blacklist', 'blocked_meta_key' );

		$original = $this->create_original_post();
		\add_post_meta( $original->ID, 'blocked_meta_key', 'blocked_value' );
		\add_post_meta( $original->ID, 'allowed_meta_key', 'allowed_value' );

		$new_id = \duplicate_post_create_duplicate( $original );

		$this->assertEquals( '', \get_post_meta( $new_id, 'blocked_meta_key', true ) );
		$this->assertEquals( 'allowed_value', \get_post_meta( $new_id, 'allowed_meta_key', true ) );
	}

	/**
	 * Tests that duplicate_post_create_duplicate copies children when enabled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_children
	 *
	 * @return void
	 */
	public function test_create_duplicate_copies_children_when_enabled() {
		\update_option( 'duplicate_post_copychildren', '1' );
		\update_option( 'duplicate_post_types_enabled', [ 'post', 'page' ] );

		// The hook is registered in admin_init based on the option value at that time.
		// Since we changed the option after admin_init, we need to add the hook manually.
		\add_action( 'dp_duplicate_page', 'duplicate_post_copy_children', 20, 3 );

		$parent = $this->factory->post->create_and_get(
			[
				'post_type'   => 'page',
				'post_title'  => 'Parent Page',
				'post_status' => 'publish',
			]
		);

		$this->factory->post->create_and_get(
			[
				'post_type'   => 'page',
				'post_title'  => 'Child Page',
				'post_parent' => $parent->ID,
				'post_status' => 'publish',
			]
		);

		$new_parent_id = \duplicate_post_create_duplicate( $parent );

		// Remove the hook to avoid affecting other tests.
		\remove_action( 'dp_duplicate_page', 'duplicate_post_copy_children', 20 );

		// Get children of the new parent.
		$new_children = \get_children(
			[
				'post_parent' => $new_parent_id,
				'post_type'   => 'page',
			]
		);

		$this->assertCount( 1, $new_children );
		$new_child = \array_shift( $new_children );
		$this->assertEquals( 'Child Page', $new_child->post_title );
	}

	/**
	 * Tests that duplicate_post_create_duplicate does not copy children when disabled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_does_not_copy_children_when_disabled() {
		\update_option( 'duplicate_post_copychildren', '0' );

		$parent = $this->factory->post->create_and_get(
			[
				'post_type'   => 'page',
				'post_title'  => 'Parent Page',
				'post_status' => 'publish',
			]
		);

		$this->factory->post->create_and_get(
			[
				'post_type'   => 'page',
				'post_title'  => 'Child Page',
				'post_parent' => $parent->ID,
				'post_status' => 'publish',
			]
		);

		$new_parent_id = \duplicate_post_create_duplicate( $parent );

		// Get children of the new parent.
		$new_children = \get_children(
			[
				'post_parent' => $new_parent_id,
				'post_type'   => 'page',
			]
		);

		$this->assertCount( 0, $new_children );
	}

	/**
	 * Tests that duplicate_post_create_duplicate copies comments when enabled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_comments
	 *
	 * @return void
	 */
	public function test_create_duplicate_copies_comments_when_enabled() {
		\update_option( 'duplicate_post_copycomments', '1' );

		// The hook is registered in admin_init based on the option value at that time.
		// Since we changed the option after admin_init, we need to add the hook manually.
		\add_action( 'dp_duplicate_post', 'duplicate_post_copy_comments', 40, 2 );

		$original = $this->create_original_post();

		$this->factory->comment->create(
			[
				'comment_post_ID' => $original->ID,
				'comment_content' => 'Test comment content',
			]
		);

		$new_id = \duplicate_post_create_duplicate( $original );

		// Remove the hook to avoid affecting other tests.
		\remove_action( 'dp_duplicate_post', 'duplicate_post_copy_comments', 40 );

		$new_comments = \get_comments( [ 'post_id' => $new_id ] );

		$this->assertCount( 1, $new_comments );
		$this->assertEquals( 'Test comment content', $new_comments[0]->comment_content );
	}

	/**
	 * Tests that duplicate_post_create_duplicate does not copy comments when disabled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_does_not_copy_comments_when_disabled() {
		\update_option( 'duplicate_post_copycomments', '0' );

		$original = $this->create_original_post();

		$this->factory->comment->create(
			[
				'comment_post_ID' => $original->ID,
				'comment_content' => 'Test comment content',
			]
		);

		$new_id = \duplicate_post_create_duplicate( $original );

		$new_comments = \get_comments( [ 'post_id' => $new_id ] );

		$this->assertCount( 0, $new_comments );
	}

	/**
	 * Tests that duplicate_post_create_duplicate fires the pre_copy action.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_fires_pre_copy_action() {
		$action_fired = false;
		$callback     = static function () use ( &$action_fired ) {
			$action_fired = true;
		};

		\add_action( 'duplicate_post_pre_copy', $callback );

		$original = $this->create_original_post();
		\duplicate_post_create_duplicate( $original );

		\remove_action( 'duplicate_post_pre_copy', $callback );

		$this->assertTrue( $action_fired );
	}

	/**
	 * Tests that duplicate_post_create_duplicate fires the post_copy action.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_fires_post_copy_action() {
		$captured_new_id = null;
		$callback        = static function ( $new_id ) use ( &$captured_new_id ) {
			$captured_new_id = $new_id;
		};

		\add_action( 'duplicate_post_post_copy', $callback );

		$original = $this->create_original_post();
		$new_id   = \duplicate_post_create_duplicate( $original );

		\remove_action( 'duplicate_post_post_copy', $callback );

		$this->assertEquals( $new_id, $captured_new_id );
	}

	/**
	 * Tests that duplicate_post_create_duplicate fires the dp_duplicate_post action.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_fires_dp_duplicate_post_action() {
		$captured_data = [];
		$callback      = static function ( $new_id, $post, $status ) use ( &$captured_data ) {
			$captured_data = [
				'new_id' => $new_id,
				'post'   => $post,
				'status' => $status,
			];
		};

		\add_action( 'dp_duplicate_post', $callback, 10, 3 );

		$original = $this->create_original_post();
		$new_id   = \duplicate_post_create_duplicate( $original );

		\remove_action( 'dp_duplicate_post', $callback, 10 );

		$this->assertEquals( $new_id, $captured_data['new_id'] );
		$this->assertEquals( $original->ID, $captured_data['post']->ID );
	}

	/**
	 * Tests that duplicate_post_create_duplicate works with pages.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_works_with_pages() {
		$original = $this->factory->post->create_and_get(
			[
				'post_type'    => 'page',
				'post_title'   => 'Original Page',
				'post_content' => 'Page content.',
				'post_status'  => 'publish',
			]
		);

		$new_id     = \duplicate_post_create_duplicate( $original );
		$duplicated = \get_post( $new_id );

		$this->assertIsInt( $new_id );
		$this->assertEquals( 'page', $duplicated->post_type );
		$this->assertEquals( 'Original Page', $duplicated->post_title );
		$this->assertEquals( 'Page content.', $duplicated->post_content );
	}

	/**
	 * Tests that the duplicate_post_new_post filter can modify the new post data.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_applies_new_post_filter() {

		$callback = static function ( $new_post ) {
			$new_post['post_title'] = 'Modified by filter';
			return $new_post;
		};

		\add_filter( 'duplicate_post_new_post', $callback, 10, 2 );

		$original   = $this->create_original_post();
		$new_id     = \duplicate_post_create_duplicate( $original );
		$duplicated = \get_post( $new_id );

		\remove_filter( 'duplicate_post_new_post', $callback, 10 );

		$this->assertEquals( 'Modified by filter', $duplicated->post_title );
	}

	/**
	 * Tests that the duplicate_post_allow filter can prevent duplication.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 *
	 * @return void
	 */
	public function test_create_duplicate_can_be_prevented_by_filter() {
		$callback = static function () {
			return false;
		};

		\add_filter( 'duplicate_post_allow', $callback );

		$original = $this->create_original_post();

		$this->expectException( WPDieException::class );

		try {
			\duplicate_post_create_duplicate( $original );
		}
		finally {
			\remove_filter( 'duplicate_post_allow', $callback );
		}
	}

	/**
	 * Tests that duplicate_post_create_duplicate respects taxonomies blacklist.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_post_taxonomies
	 *
	 * @return void
	 */
	public function test_create_duplicate_respects_taxonomies_blacklist() {
		\update_option( 'duplicate_post_taxonomies_blacklist', [ 'post_tag' ] );

		$category_id = $this->factory->category->create( [ 'name' => 'Allowed Category' ] );
		$original    = $this->create_original_post();
		\wp_set_post_categories( $original->ID, [ $category_id ] );
		\wp_set_post_tags( $original->ID, [ 'Blocked Tag' ] );

		$new_id = \duplicate_post_create_duplicate( $original );

		$new_categories = \wp_get_post_categories( $new_id );
		$new_tags       = \wp_get_post_tags( $new_id );

		$this->assertContains( $category_id, $new_categories );
		$this->assertEmpty( $new_tags );
	}

	/**
	 * Tests that duplicate_post_create_duplicate does not copy thumbnail when disabled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_post_meta_info
	 *
	 * @return void
	 */
	public function test_create_duplicate_does_not_copy_thumbnail_when_disabled() {
		\update_option( 'duplicate_post_copythumbnail', '0' );

		$original = $this->create_original_post();

		// Create an attachment.
		$attachment_id = $this->factory->attachment->create_object(
			[
				'post_mime_type' => 'image/png',
				'post_type'      => 'attachment',
				'post_title'     => 'Test Image',
			]
		);

		// Set thumbnail via meta (more reliable in tests than set_post_thumbnail).
		\add_post_meta( $original->ID, '_thumbnail_id', $attachment_id );

		// Verify thumbnail meta is set on original.
		$this->assertEquals( $attachment_id, \get_post_meta( $original->ID, '_thumbnail_id', true ), 'Original post should have thumbnail meta' );

		$new_id = \duplicate_post_create_duplicate( $original );

		// Thumbnail should NOT be copied when disabled.
		$this->assertEmpty( \get_post_meta( $new_id, '_thumbnail_id', true ) );
	}

	/**
	 * Tests that duplicate_post_create_duplicate copies thumbnail when enabled.
	 *
	 * @covers ::duplicate_post_create_duplicate
	 * @covers ::duplicate_post_copy_post_meta_info
	 *
	 * @return void
	 */
	public function test_create_duplicate_copies_thumbnail_when_enabled() {
		\update_option( 'duplicate_post_copythumbnail', '1' );

		$original = $this->create_original_post();

		// Create an attachment.
		$attachment_id = $this->factory->attachment->create_object(
			[
				'post_mime_type' => 'image/png',
				'post_type'      => 'attachment',
				'post_title'     => 'Test Image',
			]
		);

		// Set thumbnail via meta (more reliable in tests than set_post_thumbnail).
		\add_post_meta( $original->ID, '_thumbnail_id', $attachment_id );

		// Verify thumbnail meta is set on original.
		$this->assertEquals( $attachment_id, \get_post_meta( $original->ID, '_thumbnail_id', true ), 'Original post should have thumbnail meta' );

		$new_id = \duplicate_post_create_duplicate( $original );

		// Thumbnail SHOULD be copied when enabled.
		$this->assertEquals( $attachment_id, \get_post_meta( $new_id, '_thumbnail_id', true ) );
	}
}
