<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests\UI;

use Brain\Monkey;
use Mockery;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Tests\TestCase;
use Yoast\WP\Duplicate_Post\UI\Column;

/**
 * Test the Column class.
 */
class Column_Test extends TestCase {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * The instance.
	 *
	 * @var Column
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->permissions_helper = Mockery::mock( Permissions_Helper::class );

		$this->instance = Mockery::mock(
			Column::class
		)->makePartial();

		$enabled_post_types = [ 'post', 'page' ];

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_original_column' )
			->andReturn( '1' );

		$this->permissions_helper
			->expects( 'get_enabled_post_types' )
			->andReturn( $enabled_post_types );
		$this->instance->__construct( $this->permissions_helper );
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Column::register_hooks
	 */
	public function test_register_hooks() {
		$enabled_post_types = [ 'post', 'page' ];

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_original_column' )
			->andReturn( '1' );

		$this->permissions_helper
			->expects( 'get_enabled_post_types' )
			->andReturn( $enabled_post_types );

		$this->instance->register_hooks();

		$this->assertNotFalse( \has_filter( 'manage_post_posts_columns', [ $this->instance, 'add_original_column' ] ), 'Does not have expected manage_post_posts_columns filter' );
		$this->assertNotFalse( \has_action( 'manage_post_posts_custom_column', [ $this->instance, 'show_original_item' ] ), 'Does not have expected manage_post_posts_custom_column action' );
		$this->assertNotFalse( \has_filter( 'manage_page_posts_columns', [ $this->instance, 'add_original_column' ] ), 'Does not have expected manage_page_posts_columns filter' );
		$this->assertNotFalse( \has_action( 'manage_page_posts_custom_column', [ $this->instance, 'show_original_item' ] ), 'Does not have expected manage_page_posts_custom_column action' );

		$this->assertNotFalse( \has_action( 'quick_edit_custom_box', [ $this->instance, 'quick_edit_remove_original' ] ), 'Does not have expected quick_edit_custom_box action' );
		$this->assertNotFalse( \has_action( 'admin_enqueue_scripts', [ $this->instance, 'admin_enqueue_scripts' ] ), 'Does not have expected admin_enqueue_scripts action' );
		$this->assertNotFalse( \has_action( 'admin_enqueue_scripts', [ $this->instance, 'admin_enqueue_scripts' ] ), 'Does not have expected admin_enqueue_scripts action' );
	}

	/**
	 * Tests the add_original_column function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Column::add_original_column
	 */
	public function test_add_original_column_action() {
		$array = [
			'cb'         => '<input type="checkbox" />',
			'title'      => 'Title',
			'author'     => 'Author',
			'categories' => 'Categories',
			'tags'       => 'Tags',
			'comments'   => '<span class="vers comment-grey-bubble" title="Comments"><span class="screen-reader-text">Comments</span></span>',
			'date'       => 'Date',
		];

		$this->assertEquals(
			[
				'cb'                           => '<input type="checkbox" />',
				'title'                        => 'Title',
				'author'                       => 'Author',
				'categories'                   => 'Categories',
				'tags'                         => 'Tags',
				'comments'                     => '<span class="vers comment-grey-bubble" title="Comments"><span class="screen-reader-text">Comments</span></span>',
				'date'                         => 'Date',
				'duplicate_post_original_item' => 'Original item',
			],
			$this->instance->add_original_column( $array )
		);
	}
}
