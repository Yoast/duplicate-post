<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests\Watchers;

use Brain\Monkey;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Tests\TestCase;
use Yoast\WP\Duplicate_Post\Watchers\Original_Post_Watcher;

/**
 * Test the Original_Post_Watcher class.
 */
class Original_Post_Watcher_Test extends TestCase {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * The instance.
	 *
	 * @var Original_Post_Watcher
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->permissions_helper = \Mockery::mock( Permissions_Helper::class );

		$this->instance = \Mockery::mock(
			Original_Post_Watcher::class
		)->makePartial();
		$this->instance->__construct( $this->permissions_helper );
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Original_Post_Watcher::__construct
	 */
	public function test_constructor() {
		$this->assertAttributeInstanceOf( Permissions_Helper::class, 'permissions_helper', $this->instance );
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Original_Post_Watcher::register_hooks
	 */
	public function test_register_hooks() {
		$this->instance->register_hooks();

		$this->assertNotFalse( \has_action( 'admin_notices', [ $this->instance, 'add_admin_notice' ] ), 'Does not have expected admin_notices action' );
		$this->assertNotFalse( \has_action( 'enqueue_block_editor_assets', [ $this->instance, 'add_block_editor_notice' ] ), 'Does not have expected enqueue_block_editor_assets action' );
	}

	/**
	 * Tests the get_notice_text function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Original_Post_Watcher::get_notice_text
	 */
	public function test_get_notice_text() {
		$this->assertSame(
			'The original post has been edited in the meantime. If you click "Republish", this rewritten post will replace the original post.',
			$this->instance->get_notice_text()
		);
	}

	/**
	 * Tests the add_admin_notice function on the Classic Editor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Original_Post_Watcher::add_admin_notice
	 */
	public function test_add_admin_notice_classic() {
		$post = \Mockery::mock( \WP_Post::class );

		$this->permissions_helper
			->expects( 'is_classic_editor' )
			->andReturnTrue();

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'has_original_changed' )
			->with( $post )
			->andReturnTrue();

		$this->instance
			->expects( 'get_notice_text' )
			->andReturn( 'notice' );

		$this->instance->add_admin_notice();

		$this->expectOutputString( '<div id="message" class="notice notice-warning is-dismissible fade"><p>notice</p></div>' );
	}

	/**
	 * Tests the add_admin_notice function when not on the Classic editor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Original_Post_Watcher::add_admin_notice
	 */
	public function test_add_admin_notice_not_classic() {
		$this->permissions_helper
			->expects( 'is_classic_editor' )
			->andReturnFalse();

		$this->instance->add_admin_notice();

		$this->expectOutputString( '' );
	}

	/**
	 *
	 * Tests the add_admin_notice function when the original has not changed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Copied_Post_Watcher::add_admin_notice
	 */
	public function test_add_admin_notice_original_not_changed() {
		$post = \Mockery::mock( \WP_Post::class );

		$this->permissions_helper
			->expects( 'is_classic_editor' )
			->andReturnTrue();

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'has_original_changed' )
			->with( $post )
			->andReturnFalse();

		$this->instance->add_admin_notice();

		$this->expectOutputString( '' );
	}

	/**
	 * Tests the add_block_editor_notice function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Copied_Post_Watcher::add_block_editor_notice
	 */
	public function test_add_block_editor_notice() {
		$post = \Mockery::mock( \WP_Post::class );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'has_original_changed' )
			->with( $post )
			->andReturnTrue();

		$this->instance
			->expects( 'get_notice_text' )
			->andReturn( 'notice' );

		$notice = [
			'text'          => 'notice',
			'status'        => 'warning',
			'isDismissible' => true,
		];

		Monkey\Functions\expect( '\wp_json_encode' )
			->with( $notice )
			->andReturn( '{"text":"notice","status":"warning","isDismissible":true}' );

		Monkey\Functions\expect( '\wp_add_inline_script' )
			->with(
				'duplicate_post_edit_script',
				"duplicatePostNotices.has_original_changed_notice = '{\"text\":\"notice\",\"status\":\"warning\",\"isDismissible\":true}';",
				'before'
			);

		$this->instance->add_block_editor_notice();
	}

	/**
	 * Tests the add_block_editor_notice function when the original has not changed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Copied_Post_Watcher::add_block_editor_notice
	 */
	public function test_add_block_editor_notice_original_not_changed() {
		$post = \Mockery::mock( \WP_Post::class );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'has_original_changed' )
			->with( $post )
			->andReturnFalse();

		$this->instance
			->expects( 'get_notice_text' )
			->never();

		Monkey\Functions\expect( '\wp_json_encode' )
			->never();

		Monkey\Functions\expect( '\wp_add_inline_script' )
			->never();

		$this->instance->add_block_editor_notice();
	}
}
