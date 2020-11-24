<?php
/**
 * Duplicate Post handler class for save_post action.
 *
 * @package Duplicate_Post
 * @since 4.0
 */

namespace Yoast\WP\Duplicate_Post\Handlers;

use Yoast\WP\Duplicate_Post\Utils;

/**
 * Represents the handler for save_post action.
 */
class Save_Post_Handler {

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
		if ( \intval( \get_option( 'duplicate_post_show_original_meta_box' ) ) === 1
			|| \intval( \get_option( 'duplicate_post_show_original_column' ) ) === 1 ) {
			\add_action( 'save_post', [ $this, 'delete_on_save_post' ] );
		}
	}

	/**
	 * Deletes the custom field with the ID of the original post.
	 *
	 * @param int $post_id The current post ID.
	 *
	 * @return void
	 */
	public function delete_on_save_post( $post_id ) {
		if ( ( \defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| empty( $_POST['duplicate_post_remove_original'] ) // phpcs:ignore WordPress.Security.NonceVerification
			|| ! \current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$post = \get_post( $post_id );
		if ( ! $post ) {
			return;
		}
		if ( ! Utils::is_rewrite_and_republish_copy( $post ) ) {
			\delete_post_meta( $post_id, '_dp_original' );
		}
	}
}
