<?php

namespace Yoast\WP\Duplicate_Post;

use WP_Post;

/**
 * Permissions helper for Duplicate Post.
 *
 * @since 4.0
 */
class Permissions_Helper {

	/**
	 * Returns the array of the enabled post types.
	 *
	 * @return array The array of post types.
	 */
	public function get_enabled_post_types() {
		$enabled_post_types = \get_option( 'duplicate_post_types_enabled', [ 'post', 'page' ] );
		if ( ! \is_array( $enabled_post_types ) ) {
			$enabled_post_types = [ $enabled_post_types ];
		}

		if ( Utils::is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$enabled_post_types = \array_diff( $enabled_post_types, [ 'product' ] );
		}

		/**
		 * Filters the list of post types for which the plugin is enabled.
		 *
		 * @param array $enabled_post_types The array of post type names for which the plugin is enabled.
		 *
		 * @return array The filtered array of post types names.
		 */
		return \apply_filters( 'duplicate_post_enabled_post_types', $enabled_post_types );
	}

	/**
	 * Determines if post type is enabled to be copied.
	 *
	 * @param string $post_type The post type to check.
	 *
	 * @return bool Whether the post type is enabled to be copied.
	 */
	public function is_post_type_enabled( $post_type ) {
		return \in_array( $post_type, $this->get_enabled_post_types(), true );
	}

	/**
	 * Determines if the current user can copy posts.
	 *
	 * @return bool Whether the current user can copy posts.
	 */
	public function is_current_user_allowed_to_copy() {
		return \current_user_can( 'copy_posts' );
	}

	/**
	 * Determines if the post is a copy intended for Rewrite & Republish.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return bool Whether the post is a copy intended for Rewrite & Republish.
	 */
	public function is_rewrite_and_republish_copy( WP_Post $post ) {
		return ( \intval( \get_post_meta( $post->ID, '_dp_is_rewrite_republish_copy', true ) ) === 1 );
	}

	/**
	 * Gets the Rewrite & Republish copy ID for the passed post.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return int The Rewrite & Republish copy ID.
	 */
	public function get_rewrite_and_republish_copy_id( WP_Post $post ) {
		return \get_post_meta( $post->ID, '_dp_has_rewrite_republish_copy', true );
	}

	/**
	 * Gets the copy post object for the passed post.
	 *
	 * @param WP_Post $post The post to get the copy for.
	 *
	 * @return WP_Post|null The copy's post object or null if it doesn't exist.
	 */
	public function get_rewrite_and_republish_copy( WP_Post $post ) {
		$copy_id = $this->get_rewrite_and_republish_copy_id( $post );

		if ( empty( $copy_id ) ) {
			return null;
		}

		return \get_post( $copy_id );
	}

	/**
	 * Determines if the post has a copy intended for Rewrite & Republish.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return bool Whether the post has a copy intended for Rewrite & Republish.
	 */
	public function has_rewrite_and_republish_copy( WP_Post $post ) {
		return ( ! empty( $this->get_rewrite_and_republish_copy_id( $post ) ) );
	}

	/**
	 * Determines if the post has a copy intended for Rewrite & Republish which is scheduled to be published.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return bool|WP_Post The scheduled copy if present, false if the post has no scheduled copy.
	 */
	public function has_scheduled_rewrite_and_republish_copy( WP_Post $post ) {
		$copy = $this->get_rewrite_and_republish_copy( $post );

		if ( ! empty( $copy ) && $copy->post_status === 'future' ) {
			return $copy;
		}

		return false;
	}

	/**
	 * Determines whether the current screen is an edit post screen.
	 *
	 * @return bool Whether or not the current screen is editing an existing post.
	 */
	public function is_edit_post_screen() {
		if ( ! \is_admin() ) {
			return false;
		}

		$current_screen = \get_current_screen();

		return $current_screen->base === 'post' && $current_screen->action !== 'add';
	}

	/**
	 * Determines whether the current screen is an new post screen.
	 *
	 * @return bool Whether or not the current screen is editing an new post.
	 */
	public function is_new_post_screen() {
		if ( ! \is_admin() ) {
			return false;
		}

		$current_screen = \get_current_screen();

		return $current_screen->base === 'post' && $current_screen->action === 'add';
	}

	/**
	 * Determines if we are currently editing a post with Classic editor.
	 *
	 * @return bool Whether we are currently editing a post with Classic editor.
	 */
	public function is_classic_editor() {
		if ( ! $this->is_edit_post_screen() && ! $this->is_new_post_screen() ) {
			return false;
		}

		$screen = \get_current_screen();
		if ( $screen->is_block_editor() ) {
			return false;
		}

		return true;
	}

	/**
	 * Determines if the original post has changed since the creation of the copy.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return bool Whether the original post has changed since the creation of the copy.
	 */
	public function has_original_changed( WP_Post $post ) {
		if ( ! $this->is_rewrite_and_republish_copy( $post ) ) {
			return false;
		}

		$original               = Utils::get_original( $post );
		$copy_creation_date_gmt = \get_post_meta( $post->ID, '_dp_creation_date_gmt', true );

		if ( $original && $copy_creation_date_gmt ) {
			if ( \strtotime( $original->post_modified_gmt ) > \strtotime( $copy_creation_date_gmt ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines if duplicate links for the post can be displayed.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return bool Whether the links can be displayed.
	 */
	public function should_links_be_displayed( WP_Post $post ) {
		/**
		 * Filter allowing displaying duplicate post links for current post.
		 *
		 * @param bool    $display_links Whether the duplicate links will be displayed.
		 * @param WP_Post $post          The post object.
		 *
		 * @return bool Whether or not to display the duplicate post links.
		 */
		$display_links = \apply_filters( 'duplicate_post_show_link', $this->is_current_user_allowed_to_copy() && $this->is_post_type_enabled( $post->post_type ), $post );

		return ! $this->is_rewrite_and_republish_copy( $post ) && $display_links;
	}

	/**
	 * Determines if the Rewrite & Republish link for the post should be displayed.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return bool Whether the links should be displayed.
	 */
	public function should_rewrite_and_republish_be_allowed( WP_Post $post ) {
		return $post->post_status === 'publish'
			&& ! $this->is_rewrite_and_republish_copy( $post )
			&& ! $this->has_rewrite_and_republish_copy( $post );
	}

	/**
	 * Determines whether the passed post type is public and shows an admin bar.
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

	/**
	 * Determines whether a Rewrite & Republish copy can be republished.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return bool Whether the Rewrite & Republish copy can be republished.
	 */
	public function is_copy_allowed_to_be_republished( WP_Post $post ) {
		return \in_array( $post->post_status, [ 'dp-rewrite-republish', 'private' ], true );
	}

	/**
	 * Determines if the post has a trashed copy intended for Rewrite & Republish.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return bool Whether the post has a trashed copy intended for Rewrite & Republish.
	 */
	public function has_trashed_rewrite_and_republish_copy( WP_Post $post ) {
		$copy_id = \get_post_meta( $post->ID, '_dp_has_rewrite_republish_copy', true );

		if ( ! $copy_id ) {
			return false;
		}

		$copy = \get_post( $copy_id );

		return ( $copy && $copy->post_status === 'trash' );
	}
}
