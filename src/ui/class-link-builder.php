<?php

namespace Yoast\WP\Duplicate_Post\UI;

use WP_Post;

/**
 * Duplicate Post link builder.
 */
class Link_Builder {

	/**
	 * Builds URL for duplication action for the Rewrite & Republish feature.
	 *
	 * @param int|WP_Post $post    The post object or ID.
	 * @param string      $context The context in which the URL will be used.
	 *
	 * @return string The URL for the link.
	 */
	public function build_rewrite_and_republish_link( $post, $context = 'display' ) {
		return $this->build_link( $post, $context, 'duplicate_post_rewrite' );
	}

	/**
	 * Builds URL for the "Clone" action.
	 *
	 * @param int|WP_Post $post    The post object or ID.
	 * @param string      $context The context in which the URL will be used.
	 *
	 * @return string The URL for the link.
	 */
	public function build_clone_link( $post, $context = 'display' ) {
		return $this->build_link( $post, $context, 'duplicate_post_clone' );
	}

	/**
	 * Builds URL for the "Copy to a new draft" action.
	 *
	 * @param int|WP_Post $post    The post object or ID.
	 * @param string      $context The context in which the URL will be used.
	 *
	 * @return string The URL for the link.
	 */
	public function build_new_draft_link( $post, $context = 'display' ) {
		return $this->build_link( $post, $context, 'duplicate_post_new_draft' );
	}

	/**
	 * Builds URL for the "Check Changes" action.
	 *
	 * @param int|WP_Post $post    The post object or ID.
	 * @param string      $context The context in which the URL will be used.
	 *
	 * @return string The URL for the link.
	 */
	public function build_check_link( $post, $context = 'display' ) {
		return $this->build_link( $post, $context, 'duplicate_post_check_changes' );
	}

	/**
	 * Builds URL for duplication action.
	 *
	 * @param int|WP_Post $post        The post object or ID.
	 * @param string      $context     The context in which the URL will be used.
	 * @param string      $action_name The action for the URL.
	 *
	 * @return string The URL for the link.
	 */
	public function build_link( $post, $context, $action_name ) {
		$post = \get_post( $post );
		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		if ( $context === 'display' ) {
			$action = '?action=' . $action_name . '&amp;post=' . $post->ID;
		}
		else {
			$action = '?action=' . $action_name . '&post=' . $post->ID;
		}

		return \wp_nonce_url(
			/**
			 * Filter on the URL of the clone link
			 *
			 * @param string $url           The URL of the clone link.
			 * @param int    $ID            The ID of the post
			 * @param string $context       The context in which the URL is used.
			 * @param string $action_name   The action name.
			 *
			 * @return string
			 */
			\apply_filters( 'duplicate_post_get_clone_post_link', \admin_url( 'admin.php' . $action ), $post->ID, $context, $action_name ),
			$action_name . '_' . $post->ID
		);
	}
}
