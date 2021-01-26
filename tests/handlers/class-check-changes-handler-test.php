<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests\Handlers;

use Brain\Monkey;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Tests\TestCase;
use Yoast\WP\Duplicate_Post\Handlers\Check_Changes_Handler;

/**
 * Test the Check_Changes_Handler class.
 */
class Check_Changes_Handler_Test extends TestCase {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * The instance.
	 *
	 * @var Check_Changes_Handler
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->permissions_helper = \Mockery::mock( Permissions_Helper::class );

		$this->instance = \Mockery::mock( Check_Changes_Handler::class, [ $this->permissions_helper ] )->makePartial();
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Check_Changes_Handler::__construct
	 */
	public function test_constructor() {
		$this->assertAttributeInstanceOf( Permissions_Helper::class, 'permissions_helper', $this->instance );
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Check_Changes_Handler::register_hooks
	 */
	public function test_register_hooks() {
		$this->instance->register_hooks();

		$this->assertNotFalse( \has_action( 'admin_action_duplicate_post_check_changes', [ $this->instance, 'check_changes_action_handler' ] ), 'Does not have expected admin_action_duplicate_post_check_changes action' );
	}

	/**
	 * Tests the successful check_changes_action_handler function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Check_Changes_Handler::check_changes_action_handler
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_check_changes_action_handler_successful() {
		$utils                  = \Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$_GET['post']           = '123';
		$_REQUEST['action']     = 'duplicate_post_check_changes';
		$post                   = \Mockery::mock( \WP_Post::class );
		$post->ID               = 123;
		$post->post_title       = 'Unchanged Title';
		$post->post_content     = 'Updated content';
		$post->post_excerpt     = 'Updated excerpt';
		$original               = \Mockery::mock( \WP_Post::class );
		$original->ID           = 100;
		$original->post_title   = 'Unchanged Title';
		$original->post_content = 'Original content';
		$original->post_excerpt = 'Original excerpt';
		$post_link              = 'https://yoa.st/wp-admin/post.php?id=123';
		$original_link          = '<a href="https://yoa.st/wp-admin/post.php?id=100">Unchanged Title</a>';

		Monkey\Functions\expect( '\check_admin_referer' )
			->with( 'duplicate_post_check_changes_123' );

		Monkey\Functions\expect( '\get_post' )
			->with( 123 )
			->andReturn( $post );

		$utils->expects( 'get_original' )
			->with( $post )
			->andReturn( $original );

		Monkey\Functions\expect( 'get_edit_post_link' )
			->with( $post->ID )
			->andReturn( $post_link );

		$utils->expects( 'get_edit_or_view_link' )
			->with( $original )
			->andReturn( $original_link );

		$this->instance->expects( 'require_wordpress_header' );

		Monkey\Functions\expect( '\get_post' )
			->with( $post, \ARRAY_A );

		Monkey\Functions\expect( '\wp_text_diff' )
			->with( $original->post_title, $post->post_title )
			->andReturn( null, 'diff-content', 'diff-excerpt' );

		$this->instance->expects( 'require_wordpress_footer' );

		$this->expectOutputString(
			'		<div class="wrap">
			<h1 class="long-header">
			Compare changes of duplicated post with the original (&#8220;<a href="https://yoa.st/wp-admin/post.php?id=100">Unchanged Title</a>&#8221;)				</h1>
			<a href="https://yoa.st/wp-admin/post.php?id=123"></a>
			<div class="revisions">
				<div class="revisions-control-frame">
					<div class="revisions-controls"></div>
				</div>
				<div class="revisions-diff-frame">
					<div class="revisions-diff">
						<div class="diff">
														<h3>Title</h3>
								<table class="diff"><colgroup><col class="content diffsplit left"><col class="content diffsplit middle"><col class="content diffsplit right"></colgroup><tbody><tr><td>Unchanged Title</td><td></td><td>Unchanged Title</td></tr></tbody></table>								<h3>Content</h3>
								diff-content								<h3>Excerpt</h3>
								diff-excerpt
						</div>
					</div>
				</div>
			</div>
		</div>
		'
		);
		$this->instance->check_changes_action_handler();
	}

	/**
	 * Tests the check_changes_action_handler function when no ID has been passed in the query.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Check_Changes_Handler::check_changes_action_handler
	 */
	public function test_check_changes_action_handler_no_id_in_query() {
		Monkey\Functions\expect( '\wp_die' )
			->with( 'No post has been supplied!' );

		$this->instance->check_changes_action_handler();
	}


	/**
	 * Tests the check_changes_action_handler function when there is no post.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Check_Changes_Handler::check_changes_action_handler
	 */
	public function test_check_changes_action_handler_no_post() {
		$_GET['post']       = '123';
		$_REQUEST['action'] = 'duplicate_post_check_changes';

		Monkey\Functions\expect( '\check_admin_referer' )
			->with( 'duplicate_post_check_changes_123' );

		Monkey\Functions\expect( '\get_post' )
			->with( 123 )
			->andReturnNull();

		Monkey\Functions\expect( '\wp_die' )
			->with( 'Changes overview failed, could not find post with ID 123.' );

		$this->instance->check_changes_action_handler();
	}

	/**
	 * Tests the check_changes_action_handler function when there is no original post.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Handlers\Check_Changes_Handler::check_changes_action_handler
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_check_changes_action_handler_no_original() {
		$utils              = \Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$_GET['post']       = '123';
		$_REQUEST['action'] = 'duplicate_post_check_changes';
		$post               = \Mockery::mock( \WP_Post::class );

		Monkey\Functions\expect( '\check_admin_referer' )
			->with( 'duplicate_post_check_changes_123' );

		Monkey\Functions\expect( '\get_post' )
			->with( 123 )
			->andReturn( $post );

		$utils->expects( 'get_original' )
			->with( $post )
			->andReturnNull();

		Monkey\Functions\expect( '\wp_die' )
			->with( 'Changes overview failed, could not find original post.' );

		$this->instance->check_changes_action_handler();
	}
}
