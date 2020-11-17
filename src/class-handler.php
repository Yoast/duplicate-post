<?php
/**
 * Duplicate Post main class.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post;

/**
 * Represents the Duplicate Post main class.
 */
class Handler {

	/**
	 * Post_Duplicator object
	 *
	 * @var Post_Duplicator
	 */
	private $post_duplicator;

	/**
	 * Initializes the main class.
	 *
	 * @param Post_Duplicator $post_duplicator
	 */
	public function __construct( $post_duplicator ) {

		$this->post_duplicator = $post_duplicator;

		$this->register_hooks();
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 */
	private function register_hooks() {
		\add_action( 'admin_action_duplicate_post_copy_for_rewrite', [ $this, 'rewrite_link_action_handler' ] );
	}

	/**
	 *
	 */
	public function rewrite_link_action_handler() {
		if ( ! \duplicate_post_is_current_user_allowed_to_copy() ) {
			\wp_die( \esc_html__( 'Current user is not allowed to copy posts.', 'duplicate-post' ) );
		}

		if ( ! ( isset( $_GET['post'] ) || isset( $_POST['post'] ) || // Input var okay.
		         ( isset( $_REQUEST['action'] ) && 'duplicate_post_copy_for_rewrite' === $_REQUEST['action'] ) ) ) { // Input var okay.
			\wp_die( \esc_html__( 'No post to duplicate has been supplied!', 'duplicate-post' ) );
		}

		$id = ( isset( $_GET['post'] ) ? intval( \wp_unslash( $_GET['post'] ) ) : intval( \wp_unslash( $_POST['post'] ) ) ); // Input var okay.

		// Nonce check.
		\check_admin_referer( 'duplicate-post_rewrite_' . $id ); // Input var okay.

		// Get the original post.
		$post = \get_post( $id );

		// Copy the post and insert it.
		if ( ! $post ) {
			wp_die(
				esc_html(
					__( 'Copy creation failed, could not find original:', 'duplicate-post' ) . ' '
					. $id
				)
			);
		}

		$new_id    = $this->post_duplicator->create_duplicate_for_rewrite_and_republish( $post );

		// Die on insert error.
		if ( \is_wp_error( $new_id ) ) {
			\wp_die(
				\esc_html(
					__( 'Copy creation failed, could not find original:', 'duplicate-post' ) . ' '
					. $id
				)
			);
		}

		// Redirect to the edit screen for the new draft post.
		wp_safe_redirect(
			add_query_arg(
				array(
					'rewriting' => 1,
					'ids'    => $post->ID,
				),
				admin_url( 'post.php?action=edit&post=' . $new_id . ( isset( $_GET['classic-editor'] ) ? '&classic-editor' : '' ) )
			)
		);
		exit();
	}

	/**
	 * Bulk action handler.
	 *
	 * @param string $redirect_to The URL to redirect to.
	 * @param string $doaction    The action that has been called.
	 * @param array  $post_ids    The array of marked post IDs.
	 *
	 * @return string
	 */
	public function bulk_action_handler( $redirect_to, $doaction, $post_ids ) {
		if ( $doaction !== 'duplicate_post_rewrite_republish' ) {
			return $redirect_to;
		}
		$counter = 0;
		foreach ( $post_ids as $post_id ) {
			$post = \get_post( $post_id );
			if ( ! empty( $post ) ) {
				$new_post_id = $this->post_duplicator->create_duplicate_for_rewrite_and_republish( $post );
				if ( ! \is_wp_error( $new_post_id ) ) {
					$counter++;
				}
			}
		}
		$redirect_to = \add_query_arg( 'bulk_rewriting', $counter, $redirect_to );
		return $redirect_to;
	}
}
