<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests;

use Mockery;
use Yoast\WP\Duplicate_Post\Handler;
use Yoast\WP\Duplicate_Post\User_Interface;

/**
 * Test the User Interface class.
 */
class User_Interface_Test extends TestCase {

	/**
	 * Holds the handler.
	 *
	 * @var Handler|Mockery\MockInterface
	 */
	protected $handler;

	/**
	 * The instance.
	 *
	 * @var User_Interface
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->handler  = Mockery::mock( Handler::class );
		$this->instance = new User_Interface( $this->handler );
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers ::register_hooks
	 */
	public function test_register_hooks() {
		$this->instance->register_hooks();

		$this->assertNotFalse( \has_action( 'enqueue_block_editor_assets', [ $this->instance, 'enqueue_block_editor_scripts' ] ), 'Does not have expected enqueue_block_editor_assets action' );
		$this->assertNotFalse( \has_action( 'admin_enqueue_scripts', [ $this->instance, 'should_previously_used_keyword_assessment_run' ] ), 'Does not have expected admin_enqueue_scripts action' );
		$this->assertNotFalse( \has_action( 'admin_init', [ $this->instance, 'add_bulk_filters' ] ), 'Does not have expected admin_init action' );
		$this->assertNotFalse( \has_action( 'post_submitbox_start', [ $this->instance, 'add_rewrite_and_republish_post_button' ] ), 'Does not have expected post_submitbox_start action' );
		$this->assertNotFalse( \has_filter( 'removable_query_args', [ $this->instance, 'add_removable_query_args' ] ), 'Does not have expected removable_query_args filter' );
		$this->assertNotFalse( \has_action( 'admin_notices', [ $this->instance, 'single_action_admin_notice' ] ), 'Does not have expected admin_notices action' );
		$this->assertNotFalse( \has_action( 'admin_notices', [ $this->instance, 'bulk_action_admin_notice' ] ), 'Does not have expected admin_notices action' );
		$this->assertNotFalse( \has_action( 'wp_before_admin_bar_render', [ $this->instance, 'admin_bar_render' ] ), 'Does not have expected wp_before_admin_bar_render action' );
	}

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
			'cloned',
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
				'bulk_rewriting'
			],
			$this->instance->add_removable_query_args( $array )
		);
	}
}
