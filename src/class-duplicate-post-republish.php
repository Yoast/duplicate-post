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
		\add_action( 'publish_post', array( $this, 'duplicate_post_republish' ), 10, 2 );
		\add_action( 'publish_page', array( $this, 'duplicate_post_republish' ), 10, 2 );
	}

	/**
	 * Handles the republishing flow.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 *
	 * @return void
	 */
	public function duplicate_post_republish( $post_id, $post ) {
		// Runs also on: trash, restore, pending review...
		$this->republish_post_elements( $post_id, $post );
	}

	/**
	 * Republishes the post elements overwriting the original post.
	 *
	 * @param int      $post_copy_id Post ID.
	 * @param \WP_Post $post_copy    Post object.
	 *
	 * @return void
	 */
	private function republish_post_elements( $post_copy_id, $post_copy ) {

		$original_post_id = \get_post_meta( $post_copy_id, '_dp_is_rewrite_republish_copy', true );

		if ( ! $original_post_id ) {
			return;
		}

		$post_to_be_rewritten            = $post_copy;
		$post_to_be_rewritten->ID        = $original_post_id;
		$post_to_be_rewritten->post_name = get_post_field( 'post_name', $post_to_be_rewritten->ID );

		$rewritten_post_id = \wp_update_post( \wp_slash( (array) $post_to_be_rewritten ), true );

		if ( 0 === $rewritten_post_id || is_wp_error( $rewritten_post_id ) ) {
			// Error handling here.
			die( 'An error occurred.' );
		}

		// Deleting the copy bypassing the trash also deletes the post copy meta.
		\wp_delete_post( $post_copy_id, true );

		// Add nonce verification here.
		\wp_safe_redirect(
			\add_query_arg(
				array(
					'republished' => 1,
				),
				\admin_url( 'post.php?action=edit&post=' . $original_post_id )
			)
		);
		exit();
	}
}
