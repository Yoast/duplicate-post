<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests\Watchers;

use Yoast\WP\Duplicate_Post\Tests\TestCase;
use Yoast\WP\Duplicate_Post\Watchers\Bulk_Actions_Watcher;

/**
 * Test the Link_Actions_Watcher class.
 */
class Bulk_Actions_Watcher_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Bulk_Actions_Watcher
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->instance = \Mockery::mock( Bulk_Actions_Watcher::class )->makePartial();
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Bulk_Actions_Watcher::__construct
	 */
	public function test_constructor() {
		$this->instance->expects( 'register_hooks' )->once();
		$this->instance->__construct();
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Bulk_Actions_Watcher::register_hooks
	 */
	public function test_register_hooks() {
		$this->instance->register_hooks();

		$this->assertNotFalse( \has_filter( 'removable_query_args', [ $this->instance, 'add_removable_query_args' ] ), 'Does not have expected removable_query_args filter' );
		$this->assertNotFalse( \has_action( 'admin_notices', [ $this->instance, 'add_bulk_clone_admin_notice' ] ), 'Does not have expected admin_notices action' );
		$this->assertNotFalse( \has_action( 'admin_notices', [ $this->instance, 'add_bulk_rewrite_and_republish_admin_notice' ] ), 'Does not have expected admin_notices action' );
	}

	/**
	 * Tests the add_removable_query_args function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Bulk_Actions_Watcher::add_removable_query_args
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
				'bulk_cloned',
				'bulk_rewriting',
			],
			$this->instance->add_removable_query_args( $array )
		);
	}

	/**
	 * Tests the add_bulk_clone_admin_notice function when 1 post is copied.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Bulk_Actions_Watcher::add_bulk_clone_admin_notice
	 */
	public function test_add_bulk_clone_admin_notice_1() {
		$_REQUEST['bulk_cloned'] = '1';

		$this->instance->add_bulk_clone_admin_notice();

		$this->expectOutputString( '<div id="message" class="notice notice-success fade"><p>1 item copied.</p></div>' );
	}

	/**
	 * Tests the add_bulk_clone_admin_notice function when more than 1 post is copied.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Bulk_Actions_Watcher::add_bulk_clone_admin_notice
	 */
	public function test_add_bulk_clone_admin_notice_2() {
		$_REQUEST['bulk_cloned'] = '2';

		$this->instance->add_bulk_clone_admin_notice();

		$this->expectOutputString( '<div id="message" class="notice notice-success fade"><p>2 items copied.</p></div>' );
	}

	/**
	 * Tests the add_bulk_rewrite_and_republish_admin_notice function when 1 post is copied.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Bulk_Actions_Watcher::add_bulk_rewrite_and_republish_admin_notice
	 */
	public function test_add_bulk_rewrite_and_republish_admin_notice_1() {
		$_REQUEST['bulk_rewriting'] = '1';

		$this->instance->add_bulk_rewrite_and_republish_admin_notice();

		$this->expectOutputString( '<div id="message" class="notice notice-success fade"><p>1 post duplicated. You can now start rewriting your post in the duplicate of the original post. Once you choose to republish it your changes will be merged back into the original post.</p></div>' );
	}

	/**
	 * Tests the add_bulk_rewrite_and_republish_admin_notice function when more than 1 post is copied.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Bulk_Actions_Watcher::add_bulk_rewrite_and_republish_admin_notice
	 */
	public function test_add_bulk_rewrite_and_republish_admin_notice_2() {
		$_REQUEST['bulk_rewriting'] = '2';

		$this->instance->add_bulk_rewrite_and_republish_admin_notice();

		$this->expectOutputString( '<div id="message" class="notice notice-success fade"><p>2 posts duplicated. You can now start rewriting your posts in the duplicates of the original posts. Once you choose to republish them your changes will be merged back into the original post.</p></div>' );
	}
}
