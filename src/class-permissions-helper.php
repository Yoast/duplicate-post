<?php
/**
 * Permissions helper for Duplicate Post.
 *
 * @package Duplicate_Post
 * @since 4.0
 */

namespace Yoast\WP\Duplicate_Post;

/**
 * Represents the Permissions_Helper class.
 */
class Permissions_Helper {

	/**
	 * Returns the array of the enabled post types.
	 *
	 * @return array The array of post types.
	 */
	public function get_enabled_post_types() {
		$duplicate_post_types_enabled = \get_option( 'duplicate_post_types_enabled', [ 'post', 'page' ] );
		if ( ! \is_array( $duplicate_post_types_enabled ) ) {
			$duplicate_post_types_enabled = [ $duplicate_post_types_enabled ];
		}
		return $duplicate_post_types_enabled;
	}

	/**
	 * Tests if post type is enable to be copied.
	 *
	 * @param string $post_type The post type to check.
	 * @return bool
	 */
	public function is_post_type_enabled( $post_type ) {
		return \in_array( $post_type, $this->get_enabled_post_types(), true );
	}

	/**
	 * Test if the current user can copy posts.
	 *
	 * @return bool Whether the current user can copy posts.
	 */
	public function is_current_user_allowed_to_copy() {
		return \current_user_can( 'copy_posts' );
	}

	/**
	 * Tests if the post is a copy intended for Rewrite & Republish.
	 *
	 * @param \WP_Post $post The post object.
	 *
	 * @return bool Whether the post is a copy intended for Rewrite & Republish.
	 */
	public function is_rewrite_and_republish_copy( \WP_Post $post ) {
		return ( \intval( \get_post_meta( $post->ID, '_dp_is_rewrite_republish_copy', true ) ) === 1 );
	}

	/**
	 * Tests if duplicate links for the post can be displayed.
	 *
	 * @param \WP_Post $post The post object.
	 *
	 * @return bool Whether the links can be displayed.
	 */
	public function should_link_be_displayed( \WP_Post $post ) {
		return ! $this->is_rewrite_and_republish_copy( $post )
			&& $this->is_current_user_allowed_to_copy()
			&& $this->is_post_type_enabled( $post->post_type );
	}

	/**
	 * Determines whether the current screen is a valid edit post screen.
	 *
	 * @return bool Whether or not the current screen is considered valid.
	 */
	public function is_valid_post_edit_screen() {
		if ( ! \is_admin() ) {
			return true;
		}

		$current_screen = \get_current_screen();

		return $current_screen->base === 'post' && $current_screen->action !== 'add';
	}

	/**
	 * Checks whether the passed post type is public and shows an admin bar.
	 *
	 * @param string $post_type The post_type to copy.
	 *
	 * @return bool Whether or not the post can be copied to a new draft.
	 */
	public function post_type_has_admin_bar( $post_type ) {
		$post_type_object = \get_post_type_object( $post_type );

		if ( empty( $post_type_object ) ) {
			return false;
		}

		return $post_type_object->public && $post_type_object->show_in_admin_bar;
	}
}
