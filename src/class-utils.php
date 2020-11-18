<?php
/**
 * Utility methods for Duplicate Post
 *
 * @package Duplicate_Post
 * @since 4.0
 */

namespace Yoast\WP\Duplicate_Post;

/**
 * Represents the Utils class.
 */
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
		return \map_deep( $value, [ self::class, 'addslashes_to_strings_only' ] );
	}

	/**
	 * Returns the array of the enabled post types.
	 *
	 * @return array The array of post types.
	 */
	public static function get_enabled_post_types() {
		$duplicate_post_types_enabled = \get_option( 'duplicate_post_types_enabled', array( 'post', 'page' ) );
		if ( ! is_array( $duplicate_post_types_enabled ) ) {
			$duplicate_post_types_enabled = array( $duplicate_post_types_enabled );
		}
		return $duplicate_post_types_enabled;
	}

}
