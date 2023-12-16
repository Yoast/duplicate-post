<?php

namespace Yoast\WP\Duplicate_Post\Tests\Unit\Watchers;

use Brain\Monkey;
use Mockery;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Tests\Unit\TestCase;
use Yoast\WP\Duplicate_Post\Watchers\Link_Actions_Watcher;

/**
 * Test the Link_Actions_Watcher class.
 */
final class Link_Actions_Watcher_Test extends TestCase {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * The instance.
	 *
	 * @var Link_Actions_Watcher
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	protected function set_up() {
		parent::set_up();

		$this->permissions_helper = Mockery::mock( Permissions_Helper::class );

		$this->instance = Mockery::mock(
			Link_Actions_Watcher::class
		)->makePartial();
		$this->instance->__construct( $this->permissions_helper );
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Link_Actions_Watcher::__construct
	 */
	public function test_constructor() {
		$this->assertInstanceOf(
			Permissions_Helper::class,
			$this->getPropertyValue( $this->instance, 'permissions_helper' )
		);
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Link_Actions_Watcher::register_hooks
	 */
	public function test_register_hooks() {
		$this->instance->register_hooks();

		$this->assertNotFalse( \has_filter( 'removable_query_args', [ $this->instance, 'add_removable_query_args' ] ), 'Does not have expected removable_query_args filter' );
		$this->assertNotFalse( \has_action( 'admin_notices', [ $this->instance, 'add_clone_admin_notice' ] ), 'Does not have expected admin_notices action' );
		$this->assertNotFalse( \has_action( 'admin_notices', [ $this->instance, 'add_rewrite_and_republish_admin_notice' ] ), 'Does not have expected admin_notices action' );
		$this->assertNotFalse( \has_action( 'enqueue_block_editor_assets', [ $this->instance, 'add_rewrite_and_republish_block_editor_notice' ] ), 'Does not have expected enqueue_block_editor_assets action' );
	}

	/**
	 * Tests the add_removable_query_args function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Link_Actions_Watcher::add_removable_query_args
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

		$this->assertSame(
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
			],
			$this->instance->add_removable_query_args( $array )
		);
	}

	/**
	 * Tests the add_clone_admin_notice function on the Classic Editor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Link_Actions_Watcher::add_clone_admin_notice
	 */
	public function test_add_clone_admin_notice_classic() {
		$this->stubEscapeFunctions();
		$this->stubTranslationFunctions();

		$_REQUEST['cloned'] = '1';

		$this->permissions_helper
			->expects( 'is_classic_editor' )
			->andReturnTrue();

		$this->instance->add_clone_admin_notice();

		$this->expectOutputString( '<div id="message" class="notice notice-success fade"><p>1 item copied.</p></div>' );

		// Clean up after the test.
		unset( $_REQUEST['cloned'] );
	}

	/**
	 * Tests the add_clone_admin_notice function when not on the Classic editor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Link_Actions_Watcher::add_clone_admin_notice
	 */
	public function test_add_clone_admin_notice_not_classic() {
		$_REQUEST['cloned'] = '1';

		$this->permissions_helper
			->expects( 'is_classic_editor' )
			->andReturnFalse();

		$this->instance->add_clone_admin_notice();

		$this->expectOutputString( '' );

		// Clean up after the test.
		unset( $_REQUEST['cloned'] );
	}

	/**
	 * Tests the add_rewrite_and_republish_admin_notice function on the Classic editor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Link_Actions_Watcher::add_rewrite_and_republish_admin_notice
	 */
	public function test_add_rewrite_and_republish_admin_notice_classic() {
		$this->stubTranslationFunctions();

		$_REQUEST['rewriting'] = '1';

		$this->permissions_helper
			->expects( 'is_classic_editor' )
			->andReturnTrue();

		$this->instance->add_rewrite_and_republish_admin_notice();

		$this->expectOutputString( '<div id="message" class="notice notice-warning is-dismissible fade"><p>You can now start rewriting your post in this duplicate of the original post. If you click "Republish", your changes will be merged into the original post and you’ll be redirected there.</p></div>' );

		// Clean up after the test.
		unset( $_REQUEST['rewriting'] );
	}

	/**
	 * Tests the add_rewrite_and_republish_admin_notice function when not on the Classic editor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Link_Actions_Watcher::add_rewrite_and_republish_admin_notice
	 */
	public function test_add_rewrite_and_republish_admin_notice_not_classic() {
		$_REQUEST['rewriting'] = '1';

		$this->permissions_helper
			->expects( 'is_classic_editor' )
			->andReturnFalse();

		$this->instance->add_rewrite_and_republish_admin_notice();

		$this->expectOutputString( '' );

		// Clean up after the test.
		unset( $_REQUEST['rewriting'] );
	}

	/**
	 * Tests the add_rewrite_and_republish_block_editor_notice function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Watchers\Link_Actions_Watcher::add_rewrite_and_republish_block_editor_notice
	 */
	public function test_add_rewrite_and_republish_block_editor_notice() {
		$this->stubTranslationFunctions();

		$_REQUEST['rewriting'] = '1';

		$notice = [
			'text'          => 'You can now start rewriting your post in this duplicate of the original post. If you click "Republish", this rewritten post will replace the original post.',
			'status'        => 'warning',
			'isDismissible' => true,
		];

		Monkey\Functions\expect( '\wp_json_encode' )
			->with( $notice )
			->andReturn( '{"text":"You can now start rewriting your post in this duplicate of the original post. If you click \"Republish\", this rewritten post will replace the original post.","status":"warning","isDismissible":true}' );

		Monkey\Functions\expect( '\wp_add_inline_script' )
			->with(
				'duplicate_post_edit_script',
				"duplicatePostNotices.rewriting_notice = '{\"text\":\"You can now start rewriting your post in this duplicate of the original post. If you click \\\"Republish\\\", this rewritten post will replace the original post.\",\"status\":\"warning\",\"isDismissible\":true}';",
				'before'
			);

		$this->instance->add_rewrite_and_republish_block_editor_notice();

		// Clean up after the test.
		unset( $_REQUEST['rewriting'] );
	}
}
