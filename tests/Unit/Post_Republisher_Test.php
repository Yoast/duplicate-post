<?php

namespace Yoast\WP\Duplicate_Post\Tests\Unit;

use Brain\Monkey;
use Mockery;
use WP_Post;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Post_Duplicator;
use Yoast\WP\Duplicate_Post\Post_Republisher;

/**
 * Test the Post_Republisher class.
 */
final class Post_Republisher_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Post_Republisher|Mockery\Mock
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
	 * @var Permissions_Helper|Mockery\Mock
	 */
	protected $permissions_helper;

	/**
	 * Sets the instance.
	 *
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		$this->post_duplicator    = Mockery::mock( Post_Duplicator::class );
		$this->permissions_helper = Mockery::mock( Permissions_Helper::class );

		$this->instance = Mockery::mock(
			Post_Republisher::class,
			[ $this->post_duplicator, $this->permissions_helper ]
		)->makePartial();
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::__construct
	 *
	 * @return void
	 */
	public function test_constructor() {
		$this->assertInstanceOf(
			Post_Duplicator::class,
			$this->getPropertyValue( $this->instance, 'post_duplicator' )
		);

		$this->assertInstanceOf(
			Permissions_Helper::class,
			$this->getPropertyValue( $this->instance, 'permissions_helper' )
		);
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::register_hooks
	 *
	 * @return void
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

		Monkey\Actions\expectAdded( 'before_delete_post' )
			->with( [ $this->instance, 'clean_up_when_copy_manually_deleted' ] );

		$this->instance->register_hooks();
	}

	/**
	 * Tests is_classic_editor_post_request when the request is the Block Editor REST API request.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::is_classic_editor_post_request
	 * @runInSeparateProcess
	 *
	 * @return void
	 */
	public function test_is_classic_editor_post_request_when_rest_request() {
		\define( 'REST_REQUEST', true );
		$this->assertFalse( $this->instance->is_classic_editor_post_request() );
	}

	/**
	 * Tests is_classic_editor_post_request when the request is the Block Editor POST request to save custom meta.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::is_classic_editor_post_request
	 *
	 * @return void
	 */
	public function test_is_classic_editor_post_request_when_block_editor_saving_custom_meta_boxes() {
		$_GET['meta-box-loader'] = '1';

		Monkey\Functions\expect( '\wp_doing_ajax' )
			->andReturnFalse();

		$this->assertFalse( $this->instance->is_classic_editor_post_request() );

		// Clean up after the test.
		unset( $_GET['meta-box-loader'] );
	}

	/**
	 * Tests is_classic_editor_post_request when the request is the Classic Editor POST request.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::is_classic_editor_post_request
	 *
	 * @return void
	 */
	public function test_is_classic_editor_post_request() {
		Monkey\Functions\expect( '\wp_doing_ajax' )
			->andReturnFalse();

		$this->assertTrue( $this->instance->is_classic_editor_post_request() );
	}

	/**
	 * Tests register_post_statuses is called with expected arguments.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::register_post_statuses
	 *
	 * @return void
	 */
	public function test_register_post_statuses() {
		$this->stubTranslationFunctions();

		$options = [
			'label'                     => 'Republish',
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => false,
		];

		Monkey\Functions\expect( '\register_post_status' )
			->once()
			->with( 'dp-rewrite-republish', $options );

		$this->instance->register_post_statuses();
	}

	/**
	 * Tests the change_post_copy_status function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::change_post_copy_status
	 * @dataProvider change_post_copy_status_provider
	 *
	 * @param array<string, string|bool> $input    Input values.
	 * @param array<string, string>      $expected Expected output.
	 *
	 * @return void
	 */
	public function test_change_post_copy_status( $input, $expected ) {
		$post              = Mockery::mock( WP_Post::class );
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
		$this->assertSame( $expected['post_status'], $returned_post_data['post_status'] );
	}

	/**
	 * Data provider for test_change_post_copy_status.
	 *
	 * @return array<array<array<string, string|bool>>>
	 */
	public static function change_post_copy_status_provider() {
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
		];
	}

	/**
	 * Tests the republish_scheduled_post function when a valid copy is passed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::republish_scheduled_post
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_republish_scheduled_post() {
		$original              = Mockery::mock( WP_Post::class );
		$original->ID          = 1;
		$original->post_status = 'publish';

		$copy              = Mockery::mock( WP_Post::class );
		$copy->ID          = 123;
		$copy->post_status = 'future';

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $copy )
			->once()
			->andReturnTrue();

		$utils = Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$utils
			->expects( 'get_original' )
			->with( $copy->ID )
			->once()
			->andReturn( $original );

		Monkey\Functions\expect( 'kses_remove_filters' );
		Monkey\Functions\expect( 'kses_init_filters' );

		$this->instance->expects( 'republish' )->with( $copy, $original )->once();
		$this->instance->expects( 'delete_copy' )->with( $copy->ID, $original->ID )->once();

		$this->instance->republish_scheduled_post( $copy );
	}

	/**
	 * Tests the republish_scheduled_post function when an invalid copy is passed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::republish_scheduled_post
	 *
	 * @return void
	 */
	public function test_republish_scheduled_post_invalid_copy() {
		$copy              = Mockery::mock( WP_Post::class );
		$copy->ID          = 123;
		$copy->post_status = 'publish';

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $copy )
			->once()
			->andReturnFalse();

		$this->instance->expects( 'republish' )->never();
		$this->instance->expects( 'delete_copy' )->never();

		$this->instance->republish_scheduled_post( $copy );
	}

	/**
	 * Tests the republish_scheduled_post function when the original copy has been permanently deleted.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Post_Republisher::republish_scheduled_post
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_republish_scheduled_post_original_deleted() {
		$copy              = Mockery::mock( WP_Post::class );
		$copy->ID          = 123;
		$copy->post_status = 'publish';

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $copy )
			->once()
			->andReturnTrue();

		$utils = Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$utils
			->expects( 'get_original' )
			->with( $copy->ID )
			->once()
			->andReturnNull();

		$this->instance
			->expects( 'republish' )
			->never();

		$this->instance
			->expects( 'delete_copy' )
			->with( $copy->ID, null, false )
			->once();

		$this->instance->republish_scheduled_post( $copy );
	}
}
