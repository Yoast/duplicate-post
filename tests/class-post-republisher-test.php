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
			->andReturn( [ 'post', 'page' ] );

		Monkey\Filters\expectAdded( 'wp_insert_post_data' )
			->with( [ $this->instance, 'change_post_copy_status' ], 1, 2 );

		Monkey\Actions\expectAdded( 'init' )
			->with( [ $this->instance, 'register_post_statuses' ] );

		Monkey\Actions\expectAdded( 'rest_after_insert_post' )
			->with( [ $this->instance, 'republish_after_rest_api_request' ] );

		Monkey\Actions\expectAdded( 'rest_after_insert_page' )
			->with( [ $this->instance, 'republish_after_rest_api_request' ] );

		Monkey\Actions\expectAdded( 'wp_insert_post' )
			->with( [ $this->instance, 'republish_after_post_request' ], 9999, 2 );

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
}
