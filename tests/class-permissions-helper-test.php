<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests;

use Brain\Monkey;
use Mockery;
use Yoast\WP\Duplicate_Post\Permissions_Helper;

/**
 * Test the Permissions_Helper class.
 */
class Permissions_Helper_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Permissions_Helper
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->instance = Mockery::mock( Permissions_Helper::class )->makePartial();
	}

	/**
	 * Tests the get_enabled_post_types function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Permissions_Helper::get_enabled_post_types
	 */
	public function test_get_enabled_post_types() {
		$post_types = [ 'post', 'page', 'book', 'movie ' ];

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_types_enabled', [ 'post', 'page' ] )
			->andReturn( $post_types );

		$this->assertEquals( $post_types, $this->instance->get_enabled_post_types() );
	}

	/**
	 * Tests the is_post_type_enabled function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Permissions_Helper::is_post_type_enabled
	 */
	public function test_is_post_type_enabled() {
		$post_types = [ 'post', 'page', 'book', 'movie ' ];

		$this->instance
			->expects( 'get_enabled_post_types' )
			->twice()
			->andReturn( $post_types );

		$this->assertTrue( $this->instance->is_post_type_enabled( 'post' ) );
		$this->assertFalse( $this->instance->is_post_type_enabled( 'product' ) );
	}

	/**
	 * Tests the is_current_user_allowed_to_copy function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Permissions_Helper::is_current_user_allowed_to_copy
	 */
	public function test_is_current_user_allowed_to_copy() {
		Monkey\Functions\expect( '\current_user_can' )
			->with( 'copy_posts' )
			->twice()
			->andReturn( true, false );

		$this->assertTrue( $this->instance->is_current_user_allowed_to_copy() );
		$this->assertFalse( $this->instance->is_current_user_allowed_to_copy() );
	}

	/**
	 * Tests the successful is_rewrite_and_republish_copy function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Permissions_Helper::is_rewrite_and_republish_copy
	 */
	public function test_is_rewrite_and_republish_copy_successful() {
		$post     = Mockery::mock( \WP_Post::class );
		$post->ID = 123;

		Monkey\Functions\expect( '\get_post_meta' )
			->with( $post->ID, '_dp_is_rewrite_republish_copy', true )
			->andReturn( '1' );

		$this->assertTrue( $this->instance->is_rewrite_and_republish_copy( $post ) );
	}

	/**
	 * Tests the unsuccessful is_rewrite_and_republish_copy function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Permissions_Helper::is_rewrite_and_republish_copy
	 */
	public function test_is_rewrite_and_republish_copy_unsuccessful() {
		$post     = Mockery::mock( \WP_Post::class );
		$post->ID = 123;

		Monkey\Functions\expect( '\get_post_meta' )
			->with( $post->ID, '_dp_is_rewrite_republish_copy', true )
			->andReturn( '' );

		$this->assertFalse( $this->instance->is_rewrite_and_republish_copy( $post ) );
	}

	/**
	 * Tests the is_valid_post_edit_screen function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Permissions_Helper::is_valid_post_edit_screen
	 * @dataProvider is_valid_post_edit_screen_provider
	 *
	 * @param mixed $original Input value.
	 * @param mixed $expected Expected output.
	 */
	public function test_is_valid_post_edit_screen( $original, $expected ) {
		$screen         = Mockery::mock( \WP_Screen::class );
		$screen->base   = $original['base'];
		$screen->action = $original['action'];

		Monkey\Functions\expect( '\is_admin' )
			->andReturnTrue();

		Monkey\Functions\expect( '\get_current_screen' )
			->andReturn( $screen );

		$this->assertSame( $expected, $this->instance->is_valid_post_edit_screen() );
	}

	/**
	 * Data provider for test_is_valid_post_edit_screen_provider.
	 *
	 * @return array
	 */
	public function is_valid_post_edit_screen_provider() {
		return [
			[
				'original' => [
					'base'   => 'post',
					'action' => 'not-add',
				],
				'expected' => true,
			],
			[
				'original' => [
					'base'   => 'not-post',
					'action' => 'add',
				],
				'expected' => false,
			],
			[
				'original' => [
					'base'   => 'post',
					'action' => 'add',
				],
				'expected' => false,
			],
			[
				'original' => [
					'base'   => 'not-post',
					'action' => 'not-add',
				],
				'expected' => false,
			],
		];
	}

	/**
	 * Tests the post_type_has_admin_bar function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Permissions_Helper::post_type_has_admin_bar
	 * @dataProvider post_type_has_admin_bar_provider
	 *
	 * @param mixed $original Input value.
	 * @param mixed $expected Expected output.
	 */
	public function test_can_copy_to_draft( $original, $expected ) {
		$post_type                           = 'post';
		$post_type_object                    = Mockery::mock( \WP_Post_Type::class );
		$post_type_object->public            = $original['public'];
		$post_type_object->show_in_admin_bar = $original['show_in_admin_bar'];

		Monkey\Functions\expect( '\get_post_type_object' )
			->with( $post_type )
			->andReturn( $post_type_object );

		$this->assertSame( $expected, $this->instance->post_type_has_admin_bar( $post_type ) );
	}

	/**
	 * Data provider for test_can_copy_to_draft.
	 *
	 * @return array
	 */
	public function post_type_has_admin_bar_provider() {
		return [
			[
				'original' => [
					'public'            => true,
					'show_in_admin_bar' => true,
				],
				'expected' => true,
			],
			[
				'original' => [
					'public'            => false,
					'show_in_admin_bar' => true,
				],
				'expected' => false,
			],
			[
				'original' => [
					'public'            => true,
					'show_in_admin_bar' => false,
				],
				'expected' => false,
			],
			[
				'original' => [
					'public'            => false,
					'show_in_admin_bar' => false,
				],
				'expected' => false,
			],
		];
	}
}
