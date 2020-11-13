<?php
/**
 * Duplicate Post utils class.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post;

/**
 * Represents the Duplicate Post Utils class.
 */
class Duplicate_Post_Utils {

	/**
	 * Gets the post types enabled for copy.
	 *
	 * @return array The post types enabled for copy.
	 */
	public static function get_post_types_enabled_for_copy() {
		$duplicate_post_types_enabled = \get_option( 'duplicate_post_types_enabled', array( 'post', 'page' ) );

		if ( ! \is_array( $duplicate_post_types_enabled ) ) {
			$duplicate_post_types_enabled = array( $duplicate_post_types_enabled );
		}

		return $duplicate_post_types_enabled;
	}
}
