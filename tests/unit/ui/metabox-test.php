<?php

namespace Yoast\WP\Duplicate_Post\Tests\Unit\UI;

use Brain\Monkey;
use Mockery;
use WP_Post;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Tests\Unit\TestCase;
use Yoast\WP\Duplicate_Post\UI\Metabox;

/**
 * Test the Metabox class.
 */
class Metabox_Test extends TestCase {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * The instance.
	 *
	 * @var Metabox
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	protected function set_up() {
		parent::set_up();

		$this->permissions_helper = Mockery::mock( Permissions_Helper::class );

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_original_meta_box' )
			->andReturn( '1' );
		$this->instance = new Metabox( $this->permissions_helper );
	}

	/**
	 * Tests if the needed attributes are set correctly.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Metabox::__construct
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
	 * @covers \Yoast\WP\Duplicate_Post\UI\Metabox::register_hooks
	 */
	public function test_register_hooks() {
		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_original_meta_box' )
			->andReturn( '1' );

		$this->instance->register_hooks();

		$this->assertNotFalse( \has_action( 'add_meta_boxes', [ $this->instance, 'add_custom_metabox' ] ), 'Does not have expected add_meta_boxes action' );
	}

	/**
	 * Tests the successfull call to the add_custom_metabox function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Metabox::add_custom_metabox
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_add_custom_metabox() {
		$this->stubTranslationFunctions();

		$utils              = Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$enabled_post_types = [ 'post', 'page' ];
		$post               = Mockery::mock( WP_Post::class );
		$original_item      = Mockery::mock( WP_Post::class );

		$this->permissions_helper->expects( 'get_enabled_post_types' )
			->andReturn( $enabled_post_types );

		$utils->expects( 'get_original' )
			->with( $post )
			->andReturn( $original_item );

		Monkey\Functions\expect( 'add_meta_box' )
			->with(
				'duplicate_post_show_original',
				'Duplicate Post',
				[ $this->instance, 'custom_metabox_html' ],
				'post',
				'side',
				'default',
				[ 'original' => $original_item ]
			);

		$this->instance->add_custom_metabox( 'post', $post );
	}

	/**
	 * Tests the call to the add_custom_metabox function when a post type is not enabled
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Metabox::add_custom_metabox
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_add_custom_metabox_post_type_not_enabled() {
		$enabled_post_types = [ 'post' ];
		$post               = Mockery::mock( WP_Post::class );

		$this->permissions_helper
			->expects( 'get_enabled_post_types' )
			->andReturn( $enabled_post_types );

		Monkey\Functions\expect( 'add_meta_box' )
			->never();

		$this->instance->add_custom_metabox( 'page', $post );
	}

	/**
	 * Tests the call to the add_custom_metabox function when the post is not a copy.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Metabox::add_custom_metabox
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_add_custom_metabox_not_copy() {
		$utils              = Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$enabled_post_types = [ 'post', 'page' ];
		$post               = Mockery::mock( WP_Post::class );

		$this->permissions_helper
			->expects( 'get_enabled_post_types' )
			->andReturn( $enabled_post_types );

		$utils->expects( 'get_original' )
			->with( $post )
			->andReturn( null );

		Monkey\Functions\expect( 'add_meta_box' )
			->never();

		$this->instance->add_custom_metabox( 'post', $post );
	}
}
