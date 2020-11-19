<?php
/**
 * Duplicate Post link builder.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post;

/**
 * Class Rewrite_Republish_Link_Builder
 *
 * @package Yoast\WP\Duplicate_Post
 */
class Rewrite_Republish_Link_Builder implements Link_Builder {

	/**
	 * Builds URL for duplication action for the Rewrite & Republish feature.
	 *
	 * @param int|\WP_Post $post    The post object or ID.
	 * @param string       $context The context in which the URL will be used.
	 *
	 * @return string The URL for the link "Rewrite & Republish".
	 */
	public function build_link( $post, $context = 'display' ) {
		$post = \get_post( $post );
		if ( ! $post ) {
			return '';
		}

		$action_name = 'duplicate_post_copy_for_rewrite';

		if ( 'display' === $context ) {
			$action = '?action=' . $action_name . '&amp;post=' . $post->ID;
		} else {
			$action = '?action=' . $action_name . '&post=' . $post->ID;
		}

		return \wp_nonce_url(
			\admin_url( 'admin.php' . $action ),
			'duplicate-post_rewrite_' . $post->ID
		);
	}

}
