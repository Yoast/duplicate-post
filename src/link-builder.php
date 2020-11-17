<?php
/**
 * Link_Builder interface.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post;

interface Link_Builder {

	/**
	 *  Build URL for duplication action.
	 *
	 * @param int|\WP_Post $post    The post object or ID.
	 * @param string       $context The context in which the URL will be used.
	 */
	public function build_link( $post, $context );

}
