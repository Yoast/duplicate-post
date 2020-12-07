<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests;

use Brain\Monkey;
use Mockery;
use Yoast\WP\Duplicate_Post\Post_Republisher;
use Yoast\WP\Duplicate_Post\Post_Duplicator;

/**
 * Test the Post_Republisher class.
 */
class Post_Republisher_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Post_Republisher
	 */
	protected $instance;

	/**
	 * The Post_Duplicator object.
	 *
	 * @var Post_Duplicator
	 */
	protected $post_duplicator;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->post_duplicator = Mockery::mock( Post_Duplicator::class );
		$this->instance        = new Post_Republisher( $this->post_duplicator );
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers ::register_hooks
	 */
	public function test_register_hooks() {
		Monkey\Functions\expect( '\get_option' )
			->once()
			->with( 'duplicate_post_types_enabled' )
			->andReturn( [ 'post', 'page' ] );

		$this->instance->register_hooks();

		$this->assertNotFalse( \has_filter( 'wp_insert_post_data', [ $this->instance, 'change_post_copy_status' ] ), 'Does not have expected wp_insert_post_data filter' );
		$this->assertNotFalse( \has_filter( 'removable_query_args', [ $this->instance, 'add_removable_query_args' ] ), 'Does not have expected removable_query_args filter' );

		$this->assertNotFalse( \has_action( 'init', [ $this->instance, 'register_post_statuses' ] ), 'Does not have expected init action' );
		$this->assertNotFalse( \has_action( 'rest_after_insert_post' , [ $this->instance, 'republish_after_rest_api_request' ] ), 'Does not have expected rest_after_insert_post action' );
		$this->assertNotFalse( \has_action( 'rest_after_insert_page', [ $this->instance, 'republish_after_rest_api_request' ] ), 'Does not have expected rest_after_insert_page action' );
		$this->assertNotFalse( \has_action( 'wp_insert_post', [ $this->instance, 'republish_after_post_request' ] ), 'Does not have expected wp_insert_post action' );
		$this->assertNotFalse( \has_action( 'load-post.php', [ $this->instance, 'clean_up_after_redirect' ] ), 'Does not have expected load-post.php action' );
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
				'dprepublished',
				'dpcopy',
				'nonce',
			],
			$this->instance->add_removable_query_args( $array )
		);
	}
}
