<?php

namespace Yoast\WP\Duplicate_Post\Tests\Watchers;

use Brain\Monkey;
use Mockery;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Tests\TestCase;
use Yoast\WP\Duplicate_Post\Watchers\Republished_Post_Watcher;

/**
 * Test the Republished_Post_Watcher class.
 */
class Republished_Post_Watcher_Test extends TestCase {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * The instance.
	 *
	 * @var Republished_Post_Watcher
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->permissions_helper = Mockery::mock( Permissions_Helper::class );

		$this->instance = Mockery::mock(
			Republished_Post_Watcher::class
		)->makePartial();
		$this->instance->__construct( $this->permissions_helper );
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Republished_Post_Watcher::__construct
	 */
	public function test_constructor() {
		$this->assertAttributeInstanceOf( Permissions_Helper::class, 'permissions_helper', $this->instance );

		$this->instance->expects( 'register_hooks' )->once();
		$this->instance->__construct( $this->permissions_helper );
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Republished_Post_Watcher::register_hooks
	 */
	public function test_register_hooks() {
		$this->instance->register_hooks();

		$this->assertNotFalse( \has_filter( 'removable_query_args', [ $this->instance, 'add_removable_query_args' ] ), 'Does not have expected removable_query_args filter' );
		$this->assertNotFalse( \has_action( 'admin_notices', [ $this->instance, 'add_admin_notice' ] ), 'Does not have expected admin_notices action' );
		$this->assertNotFalse( \has_action( 'enqueue_block_editor_assets', [ $this->instance, 'add_block_editor_notice' ] ), 'Does not have expected enqueue_block_editor_assets action' );
	}

	/**
	 * Tests the get_notice_text function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Republished_Post_Watcher::get_notice_text
	 */
	public function test_get_notice_text() {
		$this->assertSame(
			'Your original post has been replaced with the rewritten post. You are now viewing the (rewritten) original post.',
			$this->instance->get_notice_text()
		);
	}

	/**
	 * Tests the add_admin_notice function on the Classic Editor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Republished_Post_Watcher::add_admin_notice
	 */
	public function test_add_admin_notice_classic() {
		$this->permissions_helper
			->expects( 'is_classic_editor' )
			->andReturnTrue();

		$this->instance
			->expects( 'get_notice_text' )
			->andReturn( 'notice' );

		$_REQUEST['dprepublished'] = '1';

		$this->instance->add_admin_notice();

		$this->expectOutputString( '<div id="message" class="notice notice-success is-dismissible"><p>notice</p></div>' );
		unset( $_REQUEST['dprepublished'] );
	}

	/**
	 * Tests the add_admin_notice function when not on the Classic editor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Republished_Post_Watcher::add_admin_notice
	 */
	public function test_add_admin_notice_not_classic() {
		$this->permissions_helper
			->expects( 'is_classic_editor' )
			->andReturnFalse();

		$this->instance->add_admin_notice();

		$this->expectOutputString( '' );
	}

	/**
	 * Tests the add_block_editor_notice function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Republished_Post_Watcher::add_block_editor_notice
	 */
	public function test_add_block_editor_notice() {
		$this->instance
			->expects( 'get_notice_text' )
			->andReturn( 'notice' );

		$notice = [
			'text'          => 'notice',
			'status'        => 'success',
			'isDismissible' => true,
		];

		Monkey\Functions\expect( '\wp_json_encode' )
			->with( $notice )
			->andReturn( '{"text":"notice","status":"success","isDismissible":true}' );

		Monkey\Functions\expect( '\wp_add_inline_script' )
			->with(
				'duplicate_post_edit_script',
				"duplicatePostNotices.republished_notice = '{\"text\":\"notice\",\"status\":\"success\",\"isDismissible\":true}';",
				'before'
			);

		$_REQUEST['dprepublished'] = '1';

		$this->instance->add_block_editor_notice();
		unset( $_REQUEST['dprepublished'] );
	}

	/**
	 * Tests the add_removable_query_args function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Republished_Post_Watcher::add_removable_query_args
	 */
	public function test_add_removable_query_args() {
		$array = [
			'activate',
			'activated',
			'admin_email_remind_later',
			'approved',
			'deactivate',
			'delete_count',
			'deleted',
			'disabled',
			'doing_wp_cron',
			'enabled',
			'error',
			'hotkeys_highlight_first',
			'hotkeys_highlight_last',
			'locked',
			'message',
			'same',
			'saved',
			'settings-updated',
			'skipped',
			'spammed',
			'trashed',
			'unspammed',
			'untrashed',
			'update',
			'updated',
			'wp-post-new-reload',
		];

		$this->assertEquals(
			[
				'activate',
				'activated',
				'admin_email_remind_later',
				'approved',
				'deactivate',
				'delete_count',
				'deleted',
				'disabled',
				'doing_wp_cron',
				'enabled',
				'error',
				'hotkeys_highlight_first',
				'hotkeys_highlight_last',
				'locked',
				'message',
				'same',
				'saved',
				'settings-updated',
				'skipped',
				'spammed',
				'trashed',
				'unspammed',
				'untrashed',
				'update',
				'updated',
				'wp-post-new-reload',
				'dprepublished',
				'dpcopy',
				'dpnonce',
			],
			$this->instance->add_removable_query_args( $array )
		);
	}
}
