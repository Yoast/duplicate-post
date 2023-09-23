<?php

namespace Yoast\WP\Duplicate_Post\Tests\Unit\UI;

use Brain\Monkey;
use Mockery;
use Yoast\WP\Duplicate_Post\Tests\Unit\TestCase;
use Yoast\WP\Duplicate_Post\UI\Asset_Manager;

/**
 * Test the Asset_Manager class.
 */
final class Asset_Manager_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Asset_Manager
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	protected function set_up() {
		parent::set_up();

		$this->instance = Mockery::mock( Asset_Manager::class )->makePartial();
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Asset_Manager::register_hooks
	 */
	public function test_register_hooks() {
		$this->instance->register_hooks();

		$this->assertNotFalse( \has_action( 'init', [ $this->instance, 'register_styles' ] ), 'Does not have expected init action (styles)' );
		$this->assertNotFalse( \has_action( 'init', [ $this->instance, 'register_scripts' ] ), 'Does not have expected init action (scripts)' );
	}

	/**
	 * Tests the register_styles function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Asset_Manager::register_styles
	 */
	public function test_register_styles() {
		$styles_url         = 'http://basic.wordpress.test/wp-content/plugins/duplicate-post/css/duplicate-post.css';
		$options_styles_url = 'http://basic.wordpress.test/wp-content/plugins/duplicate-post/css/duplicate-post-options.css';

		Monkey\Functions\expect( '\plugins_url' )
			->twice()
			->andReturn( $styles_url, $options_styles_url );

		Monkey\Functions\expect( '\wp_register_style' )
			->with(
				'duplicate-post',
				$styles_url,
				[],
				\DUPLICATE_POST_CURRENT_VERSION
			);

		Monkey\Functions\expect( '\wp_register_style' )
			->with(
				'duplicate-post-options',
				$options_styles_url,
				[],
				\DUPLICATE_POST_CURRENT_VERSION
			);

		$this->instance->register_styles();
	}

	/**
	 * Tests the register_scripts function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Asset_Manager::register_scripts
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_register_scripts() {
		$utils                 = Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$flattened_version     = '40';
		$edit_script_url       = 'http://basic.wordpress.test/wp-content/plugins/duplicate-post/js/dist/duplicate-post-edit-40.js';
		$strings_script_url    = 'http://basic.wordpress.test/wp-content/plugins/duplicate-post/js/dist/duplicate-post-strings-40.js';
		$quick_edit_script_url = 'http://basic.wordpress.test/wp-content/plugins/duplicate-post/js/dist/duplicate-post-quick-edit-40.js';
		$options_script_url    = 'http://basic.wordpress.test/wp-content/plugins/duplicate-post/js/dist/duplicate-post-options-40.js';

		$utils->expects( 'flatten_version' )
			->with( \DUPLICATE_POST_CURRENT_VERSION )
			->andReturn( $flattened_version );

		Monkey\Functions\expect( '\plugins_url' )
			->andReturn( $edit_script_url, $strings_script_url, $quick_edit_script_url, $options_script_url );

		Monkey\Functions\expect( '\wp_register_script' )
			->with(
				'duplicate_post_edit_script',
				$edit_script_url,
				[
					'wp-components',
					'wp-element',
					'wp-i18n',
				],
				\DUPLICATE_POST_CURRENT_VERSION,
				true
			);

		Monkey\Functions\expect( '\wp_register_script' )
			->with(
				'duplicate_post_strings',
				$strings_script_url,
				[
					'wp-components',
					'wp-element',
					'wp-i18n',
				],
				\DUPLICATE_POST_CURRENT_VERSION,
				true
			);

		Monkey\Functions\expect( '\wp_register_script' )
			->with(
				'duplicate_post_quick_edit_script',
				$quick_edit_script_url,
				[ 'jquery' ],
				\DUPLICATE_POST_CURRENT_VERSION,
				true
			);

		Monkey\Functions\expect( '\wp_register_script' )
			->with(
				'duplicate_post_options_script',
				$options_script_url,
				[ 'jquery' ],
				\DUPLICATE_POST_CURRENT_VERSION,
				true
			);

		$this->instance->register_scripts();
	}

	/**
	 * Tests the enqueue_styles function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Asset_Manager::enqueue_styles
	 */
	public function test_enqueue_styles() {
		Monkey\Functions\expect( '\wp_enqueue_style' )
			->with( 'duplicate-post' );

		$this->instance->enqueue_styles();
	}

	/**
	 * Tests the enqueue_edit_script function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Asset_Manager::enqueue_edit_script
	 */
	public function test_enqueue_edit_script() {
		Monkey\Functions\expect( '\wp_enqueue_script' )
			->with( 'duplicate_post_edit_script' );

		Monkey\Functions\expect( '\wp_add_inline_script' )
			->with( 'duplicate_post_edit_script', 'let duplicatePostNotices = {};', 'before' );

		Monkey\Functions\expect( '\wp_localize_script' )
			->with( 'duplicate_post_edit_script', 'duplicatePost', [] );

		$this->instance->enqueue_edit_script();
	}

	/**
	 * Tests the enqueue_strings_script function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Asset_Manager::enqueue_strings_script
	 */
	public function test_enqueue_strings_script() {
		Monkey\Functions\expect( '\wp_enqueue_script' )
			->with( 'duplicate_post_strings' );

		Monkey\Functions\expect( '\wp_localize_script' )
			->with( 'duplicate_post_strings', 'duplicatePostStrings', [] );

		$this->instance->enqueue_strings_script();
	}

	/**
	 * Tests the enqueue_quick_edit_script function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Asset_Manager::enqueue_quick_edit_script
	 */
	public function test_enqueue_quick_edit_script() {
		Monkey\Functions\expect( '\wp_enqueue_script' )
			->with( 'duplicate_post_quick_edit_script' );

		$this->instance->enqueue_quick_edit_script();
	}
}
