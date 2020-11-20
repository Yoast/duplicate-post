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

		$this->instance = new Admin_Notices();
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers ::register_hooks
	 */
	public function test_register_hooks() {
		$this->instance->register_hooks();

		$this->assertNotFalse( \has_filter( 'removable_query_args', [ $this->instance, 'add_removable_query_args' ] ), 'Does not have expected removable_query_args filter' );
		$this->assertNotFalse( \has_action( 'admin_notices', [ $this->instance, 'single_action_admin_notice' ] ), 'Does not have expected admin_notices action' );
		$this->assertNotFalse( \has_action( 'admin_notices', [ $this->instance, 'bulk_action_admin_notice' ] ), 'Does not have expected admin_notices action' );
	}

	/**
	 * Tests the add_removable_query_args function.
	 *
	 * @covers ::add_removable_query_args
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
}
