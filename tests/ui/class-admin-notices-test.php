<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests\UI;

use Yoast\WP\Duplicate_Post\Tests\TestCase;
use Yoast\WP\Duplicate_Post\UI\Admin_Notices;

/**
 * Test the Admin_Notices class.
 */
class Admin_Notices_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Admin_Notices
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->instance = \Mockery::mock( Admin_Notices::class )->makePartial();
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Admin_Notices::__construct
	 */
	public function test_constructor() {
		$this->instance->expects( 'register_hooks' )->once();
		$this->instance->__construct();
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Admin_Notices::register_hooks
	 */
	public function test_register_hooks() {
		$this->instance->register_hooks();

		$this->assertNotFalse( \has_filter( 'removable_query_args', [ $this->instance, 'add_removable_query_args' ] ), 'Does not have expected removable_query_args filter' );
		$this->assertNotFalse( \has_action( 'admin_notices', [ $this->instance, 'clone_admin_notice' ] ), 'Does not have expected admin_notices action' );
		$this->assertNotFalse( \has_action( 'admin_notices', [ $this->instance, 'rewrite_and_republish_link_admin_notice' ] ), 'Does not have expected admin_notices action' );
		$this->assertNotFalse( \has_action( 'admin_notices', [ $this->instance, 'rewrite_and_republish_bulk_admin_notice' ] ), 'Does not have expected admin_notices action' );
		$this->assertNotFalse( \has_action( 'admin_notices', [ $this->instance, 'republished_admin_notice' ] ), 'Does not have expected admin_notices action' );
	}

	/**
	 * Tests the add_removable_query_args function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Admin_Notices::add_removable_query_args
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
				'cloned',
				'rewriting',
				'bulk_rewriting',
			],
			$this->instance->add_removable_query_args( $array )
		);
	}

	/**
	 * Tests the clone_admin_notice function when 1 post is cloned.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Admin_Notices::clone_admin_notice
	 */
	public function test_clone_admin_notice_1() {
		$_REQUEST['cloned'] = '1';

		$this->instance->clone_admin_notice();

		$this->expectOutputString( '<div id="message" class="notice notice-success fade"><p>1 item copied.</p></div>' );
	}

	/**
	 * Tests the clone_admin_notice function when more than 1 post is cloned.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Admin_Notices::clone_admin_notice
	 */
	public function test_clone_admin_notice_2() {
		$_REQUEST['cloned'] = '2';

		$this->instance->clone_admin_notice();

		$this->expectOutputString( '<div id="message" class="notice notice-success fade"><p>2 items copied.</p></div>' );
	}

	/**
	 * Tests the rewrite_and_republish_link_admin_notice function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Admin_Notices::rewrite_and_republish_link_admin_notice
	 */
	public function test_rewrite_and_republish_link_admin_notice() {
		$_REQUEST['rewriting'] = '1';

		$this->instance->rewrite_and_republish_link_admin_notice();

		$this->expectOutputString( '<div id="message" class="notice notice-warning fade"><p>You can now start rewriting your post in this duplicate of the original post. If you click "Republish", your changes will be merged into the original post and you’ll be redirected there.</p></div>' );
	}

	/**
	 * Tests the rewrite_and_republish_bulk_admin_notice function when 1 post is copied.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Admin_Notices::rewrite_and_republish_bulk_admin_notice
	 */
	public function test_rewrite_and_republish_bulk_admin_notice_1() {
		$_REQUEST['bulk_rewriting'] = '1';

		$this->instance->rewrite_and_republish_bulk_admin_notice();

		$this->expectOutputString( '<div id="message" class="notice notice-success fade"><p>1 post duplicated. You can now start rewriting your post in the duplicate of the original post. Once you choose to republish it your changes will be merged back into the original post.</p></div>' );
	}

	/**
	 * Tests the rewrite_and_republish_bulk_admin_notice function when more than 1 post is copied.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Admin_Notices::rewrite_and_republish_bulk_admin_notice
	 */
	public function test_rewrite_and_republish_bulk_admin_notice_2() {
		$_REQUEST['bulk_rewriting'] = '2';

		$this->instance->rewrite_and_republish_bulk_admin_notice();

		$this->expectOutputString( '<div id="message" class="notice notice-success fade"><p>2 posts duplicated. You can now start rewriting your posts in the duplicates of the original posts. Once you choose to republish them your changes will be merged back into the original post.</p></div>' );
	}
}
