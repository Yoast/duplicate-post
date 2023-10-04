<?php

namespace Yoast\WP\Duplicate_Post\Tests\Unit\UI;

use Brain\Monkey;
use Mockery;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Tests\Unit\TestCase;
use Yoast\WP\Duplicate_Post\UI\Asset_Manager;
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
	 * Holds the asset manager.
	 *
	 * @var Asset_Manager
	 */
	protected $asset_manager;

	/**
	 * The instance.
	 *
	 * @var Column
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	protected function set_up() {
		parent::set_up();

		$this->permissions_helper = Mockery::mock( Permissions_Helper::class );
		$this->asset_manager      = Mockery::mock( Asset_Manager::class );

		$this->instance = new Column( $this->permissions_helper, $this->asset_manager );
	}

	/**
	 * Tests if the needed attributes are set correctly.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Column::__construct
	 */
	public function test_constructor() {
		$this->assertInstanceOf(
			Permissions_Helper::class,
			$this->getPropertyValue( $this->instance, 'permissions_helper' )
		);

		$this->assertInstanceOf(
			Asset_Manager::class,
			$this->getPropertyValue( $this->instance, 'asset_manager' )
		);
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
		$this->stubTranslationFunctions();

		$array = [
			'cb'         => '<input type="checkbox" />',
			'title'      => 'Title',
			'author'     => 'Author',
			'categories' => 'Categories',
			'tags'       => 'Tags',
			'comments'   => '<span class="vers comment-grey-bubble" title="Comments"><span class="screen-reader-text">Comments</span></span>',
			'date'       => 'Date',
		];

		$this->assertSame(
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
