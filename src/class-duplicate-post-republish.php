<?php
/**
 * Duplicate Post republish class.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post;

/**
 * Represents the Duplicate Post Republish class.
 */
class Duplicate_Post_Republish {

	/**
	 * Initializes the class.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	private function register_hooks() {
		\add_action( 'post_updated', array( $this, 'duplicate_post_republish' ), 10, 3 );
	}

	/**
	 * Handles the republishing flow.
	 *
	 * @param int      $post_ID     Post ID.
	 * @param \WP_Post $post_after  Post object following the update.
	 * @param \WP_Post $post_before Post object before the update.
	 *
	 * @return void
	 */
	public function duplicate_post_republish( $post_ID, $post_after, $post_before ) {
		$this->republish_post_elements( $post_ID, $post_after, $post_before );
	}

	/**
	 * Republishes the post elements overwriting the original post.
	 *
	 * @param int      $post_copy_id     Post ID.
	 * @param \WP_Post $post_copy_after  Post object following the update.
	 * @param \WP_Post $post_copy_before Post object before the update.
	 *
	 * @return void
	 */
	private function republish_post_elements( $post_copy_id, $post_copy_after, $post_copy_before ) {

		$original_post_id = \get_post_meta( $post_copy_id, '_dp_is_rewrite_republish_copy', true );
		if ( ! $original_post_id ) {
			return;
		}

		$post              = $post_copy_after;
		$post->ID          = $original_post_id;
		$post->post_status = 'publish';

		$rewritten_post_id = \wp_update_post( \wp_slash( (array) $post ), true );

		if ( 0 === $rewritten_post_id || is_wp_error( $rewritten_post_id ) ) {
			// Error handling here.
			die( 'An error occurred.' );
		}

		\wp_delete_post( $post_copy_id, true );

		// Add nonce verification here.
		\wp_safe_redirect(
			\add_query_arg(
				array(
					'republished' => 1,
					'ids'         => $post->ID,
				),
				\admin_url( 'post.php?action=edit&post=' . $post->ID . ( isset( $_GET['classic-editor'] ) ? '&classic-editor' : '' ) )
			)
		);
		exit();
	}
}
