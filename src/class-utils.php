<?php
/**
 * Utility methods for Duplicate Post.
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
		return \is_string( $value ) ? \addslashes( $value ) : $value;
	}

	/**
	 * Replaces faulty core wp_slash().
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
		$duplicate_post_types_enabled = \get_option( 'duplicate_post_types_enabled', [ 'post', 'page' ] );
		if ( ! \is_array( $duplicate_post_types_enabled ) ) {
			$duplicate_post_types_enabled = [ $duplicate_post_types_enabled ];
		}
		return $duplicate_post_types_enabled;
	}

	/**
	 * Gets the ID of the original post intended to be rewritten with the copy for Rewrite & Republish.
	 *
	 * @param int $post_id The copy post ID.
	 *
	 * @return int The original post id of a copy for Rewrite & Republish.
	 */
	public static function get_original_post_id( $post_id ) {
		return (int) \get_post_meta( $post_id, '_dp_original', true );
	}

	/**
	 * Checks whether a post is a copy for Rewrite & Republish.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return bool Whether a post is a copy for Rewrite & Republish.
	 */
	public static function is_copy_for_rewrite_republish( $post_id ) {
		return (bool) \get_post_meta( $post_id, '_dp_is_rewrite_republish_copy', true );
	}

	/**
	 * Gets the text for the republished notice.
	 *
	 * @return strong The republished notice text.
	 */
	public static function get_republished_notice_text() {
		return \__(
			'Your original post has been replaced with the rewritten post. You are now viewing the (rewritten) original post.',
			'duplicate-post'
		);
	}
}
