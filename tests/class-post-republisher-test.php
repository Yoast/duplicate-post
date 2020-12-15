<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests;

use Brain\Monkey;
use Mockery;
use Yoast\WP\Duplicate_Post\Tests\TestCase;
use Yoast\WP\Duplicate_Post\Post_Republisher;
use Yoast\WP\Duplicate_Post\Post_Duplicator;
use Yoast\WP\Duplicate_Post\Permissions_Helper;

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
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->post_duplicator    = Mockery::mock( Post_Duplicator::class );
		$this->permissions_helper = Mockery::mock( Permissions_Helper::class );

		$this->instance = Mockery::mock(
			Post_Republisher::class
		)->makePartial();

		$enabled_post_types = [ 'post', 'page' ];

		$this->permissions_helper
			->expects( 'get_enabled_post_types' )
			->andReturn( $enabled_post_types );

		$this->instance->__construct( $this->post_duplicator, $this->permissions_helper );
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::__construct
	 */
	public function test_constructor() {
		$this->assertAttributeInstanceOf( Post_Duplicator::class, 'post_duplicator', $this->instance );
		$this->assertAttributeInstanceOf( Permissions_Helper::class, 'permissions_helper', $this->instance );

		$this->instance->expects( 'register_hooks' )->once();
		$this->instance->__construct( $this->post_duplicator, $this->permissions_helper );
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::register_hooks
	 */
	public function test_register_hooks() {
		$enabled_post_types = [ 'post', 'page' ];

		$this->permissions_helper
			->expects( 'get_enabled_post_types' )
			->andReturn( $enabled_post_types );

		Monkey\Functions\expect( 'get_option' )
			->with( 'duplicate_post_types_enabled' )
			->andReturn( $enabled_post_types );

		Monkey\Filters\expectAdded( 'wp_insert_post_data' )
			->with( [ $this->instance, 'change_post_copy_status' ], 1, 2 );

		Monkey\Actions\expectAdded( 'init' )
			->with( [ $this->instance, 'register_post_statuses' ] );

		Monkey\Actions\expectAdded( 'rest_after_insert_post' )
			->with( [ $this->instance, 'republish_after_rest_api_request' ] );

		Monkey\Actions\expectAdded( 'rest_after_insert_page' )
			->with( [ $this->instance, 'republish_after_rest_api_request' ] );

		Monkey\Actions\expectAdded( 'wp_insert_post' )
			->with( [ $this->instance, 'republish_after_post_request' ], \PHP_INT_MAX, 2 );

		Monkey\Actions\expectAdded( 'load-post.php' )
			->with( [ $this->instance, 'clean_up_after_redirect' ] );

		$this->instance->register_hooks();
	}

	/**
	 * Tests is_classic_editor_post_request when the request is the Block Editor REST API request.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::is_classic_editor_post_request
	 * @runInSeparateProcess
	 */
	public function test_is_classic_editor_post_request_when_rest_request() {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress constant used in a test.
		define( 'REST_REQUEST', true );
		$this->assertFalse( $this->instance->is_classic_editor_post_request() );
	}

	/**
	 * Tests is_classic_editor_post_request when the request is the Block Editor POST request to save custom meta.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::is_classic_editor_post_request
	 */
	public function test_is_classic_editor_post_request_when_block_editor_saving_custom_meta_boxes() {
		$_GET['meta-box-loader'] = '1';
		$this->assertFalse( $this->instance->is_classic_editor_post_request() );
		unset( $_GET['meta-box-loader'] ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Tests is_classic_editor_post_request when the request is the Classic Editor POST request.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::is_classic_editor_post_request
	 */
	public function test_is_classic_editor_post_request() {
		$this->assertTrue( $this->instance->is_classic_editor_post_request() );
	}

	/**
	 * Tests register_post_statuses is called with expected arguments.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::register_post_statuses
	 */
	public function test_register_post_statuses() {
		$statuses = [
			'dp-rewrite-republish' => [
				'label'                     => 'Republish',
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
			],
			'dp-rewrite-schedule'  => [
				'label'                     => 'Future Republish',
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
			],
		];

		Monkey\Functions\expect( '\register_post_status' )
			->once()
			->with( 'dp-rewrite-republish', $statuses['dp-rewrite-republish'] );

		Monkey\Functions\expect( '\register_post_status' )
			->once()
			->with( 'dp-rewrite-schedule', $statuses['dp-rewrite-schedule'] );

		$this->instance->register_post_statuses();
	}

	/**
	 * Tests the change_post_copy_status function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::change_post_copy_status
	 * @dataProvider change_post_copy_status_provider
	 *
	 * @param mixed $input    Input values.
	 * @param mixed $expected Expected output.
	 */
	public function test_change_post_copy_status( $input, $expected ) {
		$post              = Mockery::mock( \WP_Post::class );
		$post->ID          = 123;
		$post->post_status = $input['post_status'];
		$postarr           = [];
		$postarr['ID']     = 123;

		Monkey\Functions\expect( '\get_post' )
			->with( $post->ID )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturn( $input['is_copy'] );

		$returned_post_data = $this->instance->change_post_copy_status( (array) $post, $postarr );
		$this->assertEquals( $expected['post_status'], $returned_post_data['post_status'] );
	}

	/**
	 * Data provider for test_change_post_copy_status.
	 *
	 * @return array
	 */
	public function change_post_copy_status_provider() {
		return [
			[
				[
					'post_status' => 'publish',
					'is_copy'     => false,
				],
				[
					'post_status' => 'publish',
				],
			],
			[
				[
					'post_status' => 'publish',
					'is_copy'     => true,
				],
				[
					'post_status' => 'dp-rewrite-republish',
				],
			],
			[
				[
					'post_status' => 'future',
					'is_copy'     => true,
				],
				[
					'post_status' => 'dp-rewrite-schedule',
				],
			],
		];
	}
}