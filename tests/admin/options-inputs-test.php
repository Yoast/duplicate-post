<?php

namespace Yoast\WP\Duplicate_Post\Tests\Admin;

use Brain\Monkey\Functions;
use Mockery;
use Yoast\WP\Duplicate_Post\Admin\Options_Inputs;
use Yoast\WP\Duplicate_Post\Tests\TestCase;

/**
 * Test the Options_Inputs_Test class.
 */
class Options_Inputs_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Options_Inputs
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	protected function set_up() {
		parent::set_up();

		$this->instance = Mockery::mock( Options_Inputs::class )->makePartial();
	}

	/**
	 * Tests the creation of a checkbox input.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Inputs::checkbox
	 */
	public function test_checkbox() {
		Functions\expect( 'checked' )
			->twice()
			->andReturnUsing(
				static function( $checked, $current = true ) {
					return ( (string) $checked === (string) $current ) ? " checked='checked'" : '';
				}
			);

		$this->assertSame(
			'<input type="checkbox" name="test_checkbox" id="test-checkbox-id" value="1"  />',
			$this->instance->checkbox( 'test_checkbox', 1, 'test-checkbox-id', false )
		);

		$this->assertSame(
			'<input type="checkbox" name="test_checkbox2" id="test-checkbox-id2" value="1"  checked=\'checked\' />',
			$this->instance->checkbox( 'test_checkbox2', 1, 'test-checkbox-id2', true )
		);
	}

	/**
	 * Tests the creation of a checkbox input.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Inputs::text
	 */
	public function test_text() {
		$this->assertSame(
			'<input type="text" name="test_text" id="test-text-id" value="Hello world"  />',
			$this->instance->text( 'test_text', 'Hello world', 'test-text-id' )
		);
	}

	/**
	 * Tests the creation of a checkbox input.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Inputs::number
	 */
	public function test_number() {
		$this->assertSame(
			'<input type="number" name="test_number" id="test-number-id" value="1" min="0" step="1" />',
			$this->instance->number( 'test_number', '1', 'test-number-id' )
		);
	}
}
