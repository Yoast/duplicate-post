<?php


namespace Yoast\WP\Duplicate_Post;


class Utils {

	/**
	 * Flattens a version number for use in a filename.
	 *
	 * @param string $version The original version number.
	 *
	 * @return string The flattened version number.
	 */
	public static function flatten_version( $version ) {
		$parts = \explode( '.', $version );

		if ( \count( $parts ) === 2 && \preg_match( '/^\d+$/', $parts[1] ) === 1 ) {
			$parts[] = '0';
		}

		return \implode( '', $parts );
	}

	/**
	 * Adds slashes only to strings.
	 *
	 * @param mixed $value Value to slash only if string.
	 *
	 * @return string|mixed
	 */
	public static function addslashes_to_strings_only( $value ) {
		return is_string( $value ) ? addslashes( $value ) : $value;
	}

	/**
	 * Replacement function for faulty core wp_slash().
	 *
	 * Until WP 5.5 wp_slash() recursively added slashes not just to strings in array/objects, leading to errors.
	 *
	 * @param mixed $value What to add slashes to.
	 *
	 * @return mixed
	 */
	public static function recursively_slash_strings( $value ) {
		return \map_deep( $value, [ Utils::class, 'addslashes_to_strings_only' ] );
	}

}