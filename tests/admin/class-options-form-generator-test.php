<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests\Admin;

use Brain\Monkey;
use Mockery;
use Yoast\WP\Duplicate_Post\Admin\Options_Form_Generator;
use Yoast\WP\Duplicate_Post\Admin\Options_Inputs;
use Yoast\WP\Duplicate_Post\Tests\TestCase;

/**
 * Test the Options_Form_Generator class.
 */
class Options_Form_Generator_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Options_Form_Generator
	 */
	protected $instance;

	/**
	 * The Options_Inputs instance.
	 *
	 * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Options_Inputs
	 */
	protected $options_inputs;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->options_inputs = Mockery::mock( Options_Inputs::class )->makePartial();

		$this->instance = Mockery::mock( Options_Form_Generator::class, [ $this->options_inputs ] )->makePartial();
	}

	/**
	 * Tests the constructor of the class.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Form_Generator::__construct
	 */
	public function test_constructor() {
		$this->instance->__construct( $this->options_inputs );

		$this->assertAttributeInstanceOf( Options_Inputs::class, 'inputs', $this->instance );
	}

	/**
	 * Tests the generation of options input elements.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Form_Generator::generate_options_input
	 */
	public function test_generate_options_input_generated_output() {
		$this->options_inputs->allows(
			[
				'checkbox' => '<input type="checkbox" />',
				'text'     => '<input type="text" />',
				'number'   => '<input type="number" />',
			]
		);

		Monkey\Functions\expect( '\get_option' )->twice();

		$options = [
			'option_1' => [
				'tab'         => 'tab1',
				'fieldset'    => 'fieldset1',
				'type'        => 'checkbox',
				'label'       => 'Show field',
				'value'       => 1,
				'description' => 'test description',
			],
			'option_2' => [
				'tab'      => 'tab1',
				'type'     => 'checkbox',
				'label'    => 'Disable editing',
				'value'    => 1,
			],
			'option_3' => [
				'tab'      => 'tab2',
				'type'     => 'text',
				'label'    => 'Title',
				'value'    => '',
			],
		];

		$output   = $this->instance->generate_options_input( $options );
		$expected = '<input type="checkbox" /><label for="option-1">Show field</label><span id="option-1-description">(test description)</span><br /><input type="checkbox" /><label for="option-2">Disable editing</label><br /><input type="text" /><br />';
		$this->assertEquals( $expected, $output );
	}

	/**
	 * Tests the calling of the correct methods when generating inputs.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Form_Generator::generate_options_input
	 */
	public function test_generate_options_input_calls_expected_methods() {
		$options = [
			'option_1' => [
				'tab'         => 'tab1',
				'fieldset'    => 'fieldset1',
				'type'        => 'checkbox',
				'label'       => 'Show field',
				'value'       => 1,
				'description' => 'test description',
			],
			'option_2' => [
				'tab'      => 'tab1',
				'type'     => 'text',
				'label'    => 'Title',
				'value'    => 1,
			],
			'option_3' => [
				'tab'      => 'tab2',
				'callback' => 'callback_function',
			],
			'option_4' => [
				'tab'      => 'tab2',
				'type'     => 'number',
				'label'    => 'Amount',
				'value'    => 1,
			],
		];

		$this->options_inputs->expects( 'checkbox' )->once();
		$this->options_inputs->expects( 'text' )->once();
		$this->options_inputs->expects( 'number' )->once();

		$this->instance->expects( 'is_checked' )->once();
		$this->instance->expects( 'callback_function' )->once();
		$this->instance->expects( 'extract_description' )->once();

		$this->instance->generate_options_input( $options );
	}

	/**
	 * Tests the skipping of generation of options input elements when the option is empty.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Form_Generator::generate_options_input
	 */
	public function test_dont_generate_options_input_from_empty_options() {
		$options = [
			'option_1' => [],
		];

		$this->assertEmpty( $this->instance->generate_options_input( $options ) );
	}

	/**
	 * Tests the skipping of generation of option input elements when the WordPress version isn't high enough.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Form_Generator::generate_options_input
	 */
	public function test_dont_generate_options_input_for_invalid_version() {
		$options = [
			'option_1' => [
				'tab'         => 'tab1',
				'fieldset'    => 'fieldset1',
				'type'        => 'checkbox',
				'label'       => 'Show field',
				'value'       => 1,
				'description' => 'test description',
				'version'     => '1.2',
			],
			'option_2' => [
				'tab'      => 'tab1',
				'type'     => 'text',
				'label'    => 'Title',
				'value'    => 1,
			],
		];

		Monkey\Functions\expect( '\get_bloginfo' )
			->with( 'version' )
			->andReturn( '1.0' );

		$this->options_inputs
			->allows()
			->text()
			->andReturns( '<input type="text" />' );

		$this->assertEquals(
			'<input type="text" name="option_2" id="option-2" value="1"  /><br />',
			$this->instance->generate_options_input( $options )
		);
	}

	/**
	 * Tests the generation of sub option input elements.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Form_Generator::generate_options_input
	 */
	public function test_generate_options_input_for_suboptions() {
		$options = [
			'option_1' => [
				'sub_options' => [
					'sub_option_1' => [
						'type'  => 'checkbox',
						'label' => 'Suboption 1',
						'value' => 1,
					],
				],
			],
		];

		$this->instance->expects( 'is_checked' )->once();

		$this->assertEquals(
			'<input type="checkbox" name="option_1[sub_option_1]" id="option-1-sub-option-1" value="1"  /><label for="option-1-sub-option-1">Suboption 1</label><br />',
			$this->instance->generate_options_input( $options )
		);
	}

	/**
	 * Tests the extraction of descriptions.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Form_Generator::extract_description
	 */
	public function test_extract_description() {
		$this->assertEquals(
			'<span id="textfield-1-description">(this is a description)</span>',
			$this->instance
				->extract_description(
					'this is a description',
					'textfield-1'
				)
		);

		$this->assertEquals(
			'<p id="textfield-1-description">this is a description<br />this is another description</p>',
			$this->instance
				->extract_description(
					[
						'this is a description',
						'this is another description',
					],
					'textfield-1'
				)
		);
	}

	/**
	 * Tests the generate_taxonomy_exclusion_list callback method.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Form_Generator::generate_taxonomy_exclusion_list
	 */
	public function test_generate_taxonomy_exclusion_list() {
		$fake_labels       = new \stdClass();
		$fake_labels->name = 'Custom Taxonomy';

		$fake_taxonomy1         = new \stdClass();
		$fake_taxonomy1->name   = 'custom_taxonomy';
		$fake_taxonomy1->public = true;
		$fake_taxonomy1->labels = $fake_labels;

		$fake_taxonomy2         = new \stdClass();
		$fake_taxonomy2->name   = 'custom_taxonomy_2';
		$fake_taxonomy2->public = false;
		$fake_taxonomy2->labels = $fake_labels;

		$fake_taxonomies = [
			$fake_taxonomy1,
			$fake_taxonomy2,
		];

		Monkey\Functions\expect( '\get_taxonomies' )
			->with( [], 'objects' )
			->andReturn( $fake_taxonomies );

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_taxonomies_blacklist' )
			->once()
			->andReturn( [ 'custom_taxonomy_2' ] );

		$this->assertEquals(
			'<div class="taxonomy_public"><input type="checkbox" name="duplicate_post_taxonomies_blacklist[]" id="duplicate-post-custom-taxonomy" value="custom_taxonomy"  /><label for="duplicate-post-custom-taxonomy">Custom Taxonomy [custom_taxonomy]</label><br /></div><div class="taxonomy_private"><input type="checkbox" name="duplicate_post_taxonomies_blacklist[]" id="duplicate-post-custom-taxonomy-2" value="custom_taxonomy_2" checked="checked" /><label for="duplicate-post-custom-taxonomy-2">Custom Taxonomy [custom_taxonomy_2]</label><br /></div>',
			$this->instance->generate_taxonomy_exclusion_list()
		);
	}

	/**
	 * Tests the generate_roles_permission_list callback method.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Form_Generator::generate_roles_permission_list
	 */
	public function test_generate_roles_permission_list() {

	}

	/**
	 * Tests the generate_post_types_list callback method.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Form_Generator::generate_post_types_list
	 */
	public function test_generate_post_types_list() {

	}

	/**
	 * Tests the is_checked helper method.
	 *
	 * @dataProvider is_checked_provider
	 * @covers       \Yoast\WP\Duplicate_Post\Admin\Options_Form_Generator::is_checked
	 *
	 * @param string $option The option name.
	 * @param array  $option_values The option values.
	 * @param string $parent_option The parent option.
	 * @param array  $assertion The assumed assertion values.
	 */
	public function test_is_checked( $option, $option_values, $parent_option, $assertion = [] ) {
		if ( $assertion['expected'] === false ) {
			$used_option     = $option;
			$returned_option = $assertion['db_value'];

			if ( ( $parent_option !== '' ) ) {
				$used_option     = $parent_option;
				$returned_option = [ $option => $returned_option ];
			}

			Monkey\Functions\expect( '\get_option' )
				->with( $used_option )
				->once()
				->andReturn( $returned_option );
		}

		$output = $this->instance->is_checked( $option, $option_values, $parent_option );

		$this->assertEquals( $assertion['expected'], $output );
	}

	/**
	 * Provides the test_is_checked test with data to use in the tests.
	 *
	 * @return array The data to run the test against.
	 */
	public function is_checked_provider() {
		return [
			[
				'test_option',
				[],
				'',
				[
					'db_value' => false,
					'expected' => false,
				],
			],
			[
				'test_option',
				[ 'checked' => true ],
				'',
				[
					'db_value' => '1',
					'expected' => true,
				],
			],
			[
				'test_option',
				[ 'checked' => true ],
				'parent_option',
				[
					'db_value' => '1',
					'expected' => true,
				],
			],
		];
	}

	/**
	 * Tests the prepare_input_id helper method.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Admin\Options_Form_Generator::prepare_input_id
	 */
	public function test_prepare_input_id() {
		$this->assertEquals( 'my-form-element-id', $this->instance->prepare_input_id( 'my_form_element_id' ) );
		$this->assertEquals( 'my-form-element-id', $this->instance->prepare_input_id( 'my_form-element-id' ) );
		$this->assertEquals( 'myFormElementId', $this->instance->prepare_input_id( 'myFormElementId' ) );
	}
}
