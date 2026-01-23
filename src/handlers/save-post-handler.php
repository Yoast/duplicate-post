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
			\add_action( 'init', [ $this, 'register_meta' ] );
		}
	}

	/**
	 * Registers the meta field for the REST API.
	 *
	 * @return void
	 */
	public function register_meta() {
		$post_types = $this->permissions_helper->get_enabled_post_types();

		foreach ( $post_types as $post_type ) {
			\register_post_meta(
				$post_type,
				'_dp_remove_original',
				[
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => 'boolean',
					'default'           => false,
					'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
						return \current_user_can( 'edit_post', $post_id );
					},
					'sanitize_callback' => 'rest_sanitize_boolean',
				]
			);
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
		if ( \defined( 'DOING_AUTOSAVE' ) && \DOING_AUTOSAVE ) {
			return;
		}

		if ( ! \current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$post = \get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		if ( $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			return;
		}

		// Check for classic editor (POST request).
		$should_remove_from_post = ! empty( $_POST['duplicate_post_remove_original'] );

		// Check for block editor (meta field).
		$should_remove_from_meta = (bool) \get_post_meta( $post_id, '_dp_remove_original', true );

		if ( $should_remove_from_post || $should_remove_from_meta ) {
			\delete_post_meta( $post_id, '_dp_original' );
			// Clean up the flag meta.
			\delete_post_meta( $post_id, '_dp_remove_original' );
		}
	}
}
