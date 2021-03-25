<?php

namespace Yoast\WP\Duplicate_Post\Handlers;

use Yoast\WP\Duplicate_Post\Permissions_Helper;

/**
 * Duplicate Post handler class for save_post action.
 *
 * @since 4.0
 */
class Save_Post_Handler {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * Initializes the class.
	 *
	 * @param Permissions_Helper $permissions_helper The Permissions Helper object.
	 */
	public function __construct( Permissions_Helper $permissions_helper ) {
		$this->permissions_helper = $permissions_helper;
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
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
		if ( ( \defined( 'DOING_AUTOSAVE' ) && \DOING_AUTOSAVE )
			|| empty( $_POST['duplicate_post_remove_original'] )
			|| ! \current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$post = \get_post( $post_id );
		if ( ! $post ) {
			return;
		}
		if ( ! $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			\delete_post_meta( $post_id, '_dp_original' );
		}
	}
}
