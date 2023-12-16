<?php

namespace Yoast\WP\Duplicate_Post\Tests\Unit\Admin;

use Brain\Monkey;
use Mockery;
use Yoast\WP\Duplicate_Post\Admin\Options;
use Yoast\WP\Duplicate_Post\Admin\Options_Form_Generator;
use Yoast\WP\Duplicate_Post\Admin\Options_Page;
use Yoast\WP\Duplicate_Post\Tests\Unit\TestCase;
use Yoast\WP\Duplicate_Post\UI\Asset_Manager;

/**
 * Test the Options_Page class.
 */
final class Options_Page_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Options_Page
	 */
	protected $instance;

	/**
	 * The Options instance.
	 *
	 * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Options
	 */
	protected $options;

	/**
	 * The Options_Form_Generator instance.
	 *
	 * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Options_Form_Generator
	 */
	protected $form_generator;

	/**
	 * The Asset_Manager instance.
	 *
	 * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Asset_Manager
	 */
	protected $asset_manager;

	/**
	 * Sets the instance.
	 *
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		$this->options        = Mockery::mock( Options::class )->makePartial();
		$this->form_generator = Mockery::mock( Options_Form_Generator::class )->makePartial();
		$this->asset_manager  = Mockery::mock( Asset_Manager::class );

		$parameters     = [ $this->options, $this->form_generator, $this->asset_manager ];
		$this->instance = Mockery::mock( Options_Page::class, $parameters )
			->makePartial()
			->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests the constructor of the class.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Page::__construct
	 *
	 * @return void
	 */
	public function test_constructor() {
		$this->instance->__construct( $this->options, $this->form_generator, $this->asset_manager );

		$this->assertInstanceOf(
			Options::class,
			$this->getPropertyValue( $this->instance, 'options' )
		);

		$this->assertInstanceOf(
			Options_Form_Generator::class,
			$this->getPropertyValue( $this->instance, 'generator' )
		);
	}

	/**
	 * Tests the registration of the hooks when in the admin.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Page::register_hooks
	 *
	 * @return void
	 */
	public function test_register_hooks_when_in_admin() {
		Monkey\Functions\stubs( [ 'is_admin' => true ] );

		Monkey\Actions\expectAdded( 'admin_menu' )
			->with( [ $this->instance, 'register_menu' ] )
			->once();
		Monkey\Actions\expectAdded( 'admin_init' )
			->with( [ $this->options, 'register_settings' ] )
			->once();

		$this->instance->register_hooks();
	}

	/**
	 * Tests the registration of the hooks doesn't fire when not in the admin.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Page::register_hooks
	 *
	 * @return void
	 */
	public function test_no_register_hooks_when_not_in_admin() {
		Monkey\Functions\stubs( [ 'is_admin' => false ] );

		Monkey\Actions\expectAdded( 'admin_menu' )
			->with( [ $this->instance, 'register_menu' ] )
			->never();
		Monkey\Actions\expectAdded( 'admin_init' )
			->with( [ $this->options, 'register_settings' ] )
			->never();

		$this->instance->register_hooks();
	}

	/**
	 * Tests the loading of the assets.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Page::enqueue_assets
	 *
	 * @return void
	 */
	public function test_loading_of_assets() {
		$this->asset_manager
			->expects( 'enqueue_options_styles' );

		$this->asset_manager
			->expects( 'enqueue_options_script' );

		$this->instance->enqueue_assets();
	}

	/**
	 * Tests the loading of the assets.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Page::register_menu
	 *
	 * @return void
	 */
	public function test_register_menu() {
		$this->stubTranslationFunctions();

		Monkey\Functions\expect( '\add_options_page' )
			->with(
				[
					\__( 'Duplicate Post Options', 'duplicate-post' ),
					\__( 'Duplicate Post', 'duplicate-post' ),
					'manage_options',
					'duplicatepost',
					[ $this->instance, 'generate_page' ],
				]
			)
			->once()
			->andReturn( 'duplicatepost_page_hook' );

		Monkey\Actions\expectAdded( 'duplicatepost_page_hook' )
			->with( [ $this->instance, 'enqueue_assets' ] )
			->once();

		$this->instance->register_menu();
	}

	/**
	 * Tests the registering of the capabilities.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Page::register_capabilities
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_register_capabilities() {
		$this->stub_wp_roles();

		$utils = Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );

		Monkey\Functions\expect( '\current_user_can' )
			->with( 'promote_users' )
			->once()
			->andReturnTrue();

		$this->instance
			->expects( 'settings_updated' )
			->once()
			->andReturnTrue();

		$expected_roles = [
			'editor'        => 'Editor',
			'administrator' => 'Administrator',
			'subscriber'    => 'Subscriber',
		];

		$utils
			->expects( 'get_roles' )
			->once()
			->andReturn( $expected_roles );

		$this->instance->expects( 'get_duplicate_post_roles' )
			->andReturn(
				[
					'administrator',
					'editor',
				]
			);

		$this->instance->register_capabilities();
	}
}
