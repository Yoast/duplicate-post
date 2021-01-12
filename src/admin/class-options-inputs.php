<?php
/**
 * Duplicate Post plugin file.
 *
 * @package Yoast\WP\Duplicate_Post\Admin
 */

namespace Yoast\WP\Duplicate_Post\Admin;

/**
 * Class Options_Inputs
 */
class Options_Inputs {

	/**
	 * Creates a basic input based on the passed parameters.
	 *
	 * @param string $type          The type of input.
	 * @param string $name          The name of the input.
	 * @param string $value         The value of the input.
	 * @param string $id            The ID of the input.
	 * @param string $attributes    The additional attributes to use. Optional.
	 *
	 * @return string The input's HTML output.
	 */
	protected function input( $type, $name, $value, $id, $attributes = '' ) {
		return \sprintf(
			'<input type="%s" name="%s" id="%s" value="%s" %s />',
			\esc_attr( $type ),
			\esc_attr( $name ),
			\esc_attr( $id ),
			\esc_attr( $value ),
			$attributes
		);
	}

	/**
	 * Creates a checkbox input.
	 *
	 * @param string $name The name of the checkbox.
	 * @param string $value The value of the checkbox.
	 * @param string $id The ID of the checkbox.
	 * @param bool   $checked Whether or not the checkbox should be checked.
	 *
	 * @return string The checkbox' HTML output.
	 */
	public function checkbox( $name, $value, $id, $checked = false ) {
		$checked = $checked ? 'checked="checked"' : '';

		return $this->input( 'checkbox', $name, $value, $id, $checked );
	}

	/**
	 * Creates a text field input.
	 *
	 * @param string $name The name of the text field.
	 * @param string $value The value of the text field.
	 * @param string $id The ID of the text field.
	 *
	 * @return string The text field's HTML output.
	 */
	public function text( $name, $value, $id ) {
		return $this->input( 'text', $name, $value, $id );
	}

	/**
	 * Creates a number input.
	 *
	 * @param string $name The name of the number input.
	 * @param string $value The value of the number input.
	 * @param string $id The ID of the number input.
	 *
	 * @return string The number input's HTML output.
	 */
	public function number( $name, $value, $id ) {
		return $this->input( 'number', $name, $value, $id, 'min="0" step="1"' );
	}
}
