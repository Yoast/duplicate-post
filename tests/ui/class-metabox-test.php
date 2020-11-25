<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests\UI;

use Brain\Monkey;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Tests\TestCase;
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
	public function setUp() {
		parent::setUp();

		$this->permissions_helper = \Mockery::mock( Permissions_Helper::class );

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_original_meta_box' )
			->andReturn( '1' );
		$this->instance = new Metabox( $this->permissions_helper );
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
	 * Tests the add_custom_metabox functio.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Metabox::add_custom_metabox
	 */
	public function test_add_custom_metabox() {
		$duplicate_post_types_enabled = [ 'post', 'page' ];

		$this->permissions_helper->expects( 'get_enabled_post_types' )
			->andReturn( $duplicate_post_types_enabled );

		Monkey\Functions\expect( 'add_meta_box' )
			->with(
				'duplicate_post_show_original',
				'Duplicate Post',
				[ $this->instance, 'custom_metabox_html' ],
				'post',
				'side'
			);

		Monkey\Functions\expect( 'add_meta_box' )
			->with(
				'duplicate_post_show_original',
				'Duplicate Post',
				[ $this->instance, 'custom_metabox_html' ],
				'page',
				'side'
			);

		$this->instance->add_custom_metabox();
	}
}
