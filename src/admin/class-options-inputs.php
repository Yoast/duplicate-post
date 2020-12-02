<?php

class Duplicate_Post_Options_Inputs {
	public static function input( $type, $name, $value, $id, $attributes = '' ) {
		return sprintf(
			'<input type="%s" name="%s" id="%s" value="%s" %s />',
			$type,
			$name,
			$id,
			$value,
			$attributes
		);
	}

	public static function checkbox( $name, $value, $id ) {
		$checked = get_option( $name ) === 1 ? 'checked="checked"' : '';

		return self::input( 'checkbox', $name, $value, $id, $checked );
	}

	public static function text( $name, $value, $id ) {
		return self::input( 'text', $name, $value, $id );
	}

	public static function number( $name, $value, $id ) {
		return self::input( 'number', $name, $value, $id, 'min="0" step="1"' );
	}
}
