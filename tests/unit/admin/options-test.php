<?php

namespace Yoast\WP\Duplicate_Post\Tests\Unit\Admin;

use Brain\Monkey;
use Mockery;
use Yoast\WP\Duplicate_Post\Admin\Options;
use Yoast\WP\Duplicate_Post\Tests\Unit\TestCase;

/**
 * Test the Options class.
 */
class Options_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Options
	 */
	protected $instance;

	/**
	 * The fake options array.
	 *
	 * @var array[]
	 */
	protected $fake_options;

	/**
	 * Sets the instance.
	 */
	protected function set_up() {
		parent::set_up();

		$this->fake_options = [
			'option_1' => [
				'tab'      => 'tab1',
				'fieldset' => 'fieldset1',
				'type'     => 'checkbox',
				'label'    => 'Title',
				'value'    => 1,
			],
			'option_2' => [
				'tab'      => 'tab1',
				'type'     => 'checkbox',
				'label'    => 'Title',
				'value'    => 1,
			],
			'option_3' => [
				'tab'      => 'tab2',
				'type'     => 'checkbox',
				'label'    => 'Title',
				'value'    => 1,
			],
		];

		$this->instance = Mockery::mock( Options::class )->makePartial();
		$this->instance->allows()->get_options()->andReturn( $this->fake_options );
	}

	/**
	 * Tests the registration of the settings.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options::register_settings
	 */
	public function test_register_settings() {
		foreach ( \array_keys( $this->fake_options ) as $fake_option ) {
			Monkey\Functions\expect( '\register_setting' )
				->once()
				->with( 'duplicate_post_group', $fake_option );
		}

		$this->instance->register_settings();
	}

	/**
	 * Tests that only options for the first tab are retrieved.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options::get_options_for_tab
	 */
	public function test_get_options_for_first_tab() {
		$options = $this->instance->get_options_for_tab( 'tab1' );

		$this->assertSame(
			[
				'option_1' => [
					'tab'      => 'tab1',
					'fieldset' => 'fieldset1',
					'type'     => 'checkbox',
					'label'    => 'Title',
					'value'    => 1,
				],
				'option_2' => [
					'tab'      => 'tab1',
					'type'     => 'checkbox',
					'label'    => 'Title',
					'value'    => 1,
				],
			],
			$options
		);
	}

	/**
	 * Tests that only options for the first tab and fieldset are retrieved.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options::get_options_for_tab
	 */
	public function test_get_options_for_first_tab_and_fieldset() {
		$options = $this->instance->get_options_for_tab( 'tab1', 'fieldset1' );

		$this->assertSame(
			[
				'option_1' => [
					'tab'      => 'tab1',
					'fieldset' => 'fieldset1',
					'type'     => 'checkbox',
					'label'    => 'Title',
					'value'    => 1,
				],
			],
			$options
		);
	}

	/**
	 * Tests that no options are retrieved for a non-existent tab.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options::get_options_for_tab
	 */
	public function test_get_options_for_non_existing_tab() {
		$options = $this->instance->get_options_for_tab( 'tab3' );

		$this->assertEmpty( $options );
	}

	/**
	 * Tests that no options are retrieved for a non-existent fieldset.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options::get_options_for_tab
	 */
	public function test_get_options_for_non_existing_fieldset() {
		$options = $this->instance->get_options_for_tab( 'tab1', 'fieldset2' );

		$this->assertEmpty( $options );
	}

	/**
	 * Tests that retrieving a single, existing option, returns the option.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options::get_option
	 */
	public function test_get_valid_option() {
		$options = $this->instance->get_option( 'option_1' );

		$this->assertSame(
			[
				'option_1' => [
					'tab'      => 'tab1',
					'fieldset' => 'fieldset1',
					'type'     => 'checkbox',
					'label'    => 'Title',
					'value'    => 1,
				],
			],
			$options
		);
	}

	/**
	 * Tests that retrieving a single, non-existent option, returns an empty array.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options::get_option
	 */
	public function test_get_invalid_option() {
		$options = $this->instance->get_option( 'option_4' );

		$this->assertEmpty( $options );
	}
}
