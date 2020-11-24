<?php
/**
 * Utility methods for Duplicate Post.
 *
 * @package Duplicate_Post
 * @since 4.0
 */

namespace Yoast\WP\Duplicate_Post;

/**
 * Represents the Utils class.
 */
class Utils {

	/**
	 * Flattens a version number for use in a filename.
	 *
	 * @param string $version The original version number.
	 *
	 * @return string The flattened version number.
	 */
	public static function flatten_version( $version ) {
		$parts = \explode( '.', $version );

		if ( \count( $parts ) === 2 && \preg_match( '/^\d+$/', $parts[1] ) === 1 ) {
			$parts[] = '0';
		}

		return \implode( '', $parts );
	}

	/**
	 * Adds slashes only to strings.
	 *
	 * @param mixed $value Value to slash only if string.
	 *
	 * @return string|mixed
	 */
	public static function addslashes_to_strings_only( $value ) {
		return \is_string( $value ) ? \addslashes( $value ) : $value;
	}

	/**
	 * Replaces faulty core wp_slash().
	 *
	 * Until WP 5.5 wp_slash() recursively added slashes not just to strings in array/objects, leading to errors.
	 *
	 * @param mixed $value What to add slashes to.
	 *
	 * @return mixed
	 */
	public static function recursively_slash_strings( $value ) {
		return \map_deep( $value, [ self::class, 'addslashes_to_strings_only' ] );
	}

	/**
	 * Gets the original post.
	 *
	 * @param int|null $post   Optional. Post ID or Post object.
	 * @param string   $output Optional, default is Object. Either OBJECT, ARRAY_A, or ARRAY_N.
	 * @return \WP_Post|null Post data if successful, null otherwise.
	 */
	public static function get_original( $post = null, $output = OBJECT ) {
		$post = \get_post( $post );
		if ( ! $post ) {
			return null;
		}

		$original_id = \get_post_meta( $post->ID, '_dp_original' );
		if ( empty( $original_id ) ) {
			return null;
		}

		return \get_post( $original_id[0], $output );
	}

	/**
	 * Returns the array of the enabled post types.
	 *
	 * @return array The array of post types.
	 */
	public static function get_enabled_post_types() {
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
	public static function is_post_type_enabled( $post_type ) {
		return \in_array( $post_type, self::get_enabled_post_types(), true );
	}

	/**
	 * Test if the current user can copy posts.
	 *
	 * @return bool Whether the current user can copy posts.
	 */
	public static function is_current_user_allowed_to_copy() {
		return current_user_can( 'copy_posts' );
	}

	/**
	 * Tests if the post is a copy intended for Rewrite & Republish.
	 *
	 * @param \WP_Post $post The post object.
	 *
	 * @return bool Whethere the post is a copy intended for Rewrite & Republish.
	 */
	public static function is_rewrite_and_republish_copy( \WP_Post $post ) {
		return ( \intval( \get_post_meta( $post->ID, '_dp_is_rewrite_republish_copy', true ) ) === 1 );
	}

	/**
	 * Checks if the post has ancestors marked for copy.
	 *
	 * If we are copying children, and the post has already an ancestor marked for copy, we have to filter it out.
	 *
	 * @param \WP_Post $post     The post object.
	 * @param array    $post_ids The array of marked post IDs.
	 *
	 * @return bool
	 */
	public static function has_ancestors_marked( $post, $post_ids ) {
		$ancestors_in_array = 0;
		$parent             = \wp_get_post_parent_id( $post->ID );
		while ( $parent ) {
			if ( \in_array( $parent, $post_ids, true ) ) {
				$ancestors_in_array++;
			}
			$parent = \wp_get_post_parent_id( $parent );
		}
		return ( 0 !== $ancestors_in_array );
	}

	/**
	 * Determines whether the current screen is a valid edit post screen.
	 *
	 * @return bool Whether or not the current screen is considered valid.
	 */
	public static function is_valid_post_edit_screen() {
		if ( ! \is_admin() ) {
			return true;
		}

		$current_screen = \get_current_screen();

		return $current_screen->base === 'post' && $current_screen->action !== 'add';
	}

	/**
	 * Checks whether the passed post can be copied to a new draft.
	 *
	 * @param \WP_Post $post The post to copy.
	 *
	 * @return bool Whether or not the post can be copied to a new draft.
	 */
	public static function can_copy_to_draft( $post ) {
		if ( empty( $post->post_type ) ) {
			return false;
		}

		$post_type_object = \get_post_type_object( $post->post_type );

		if ( empty( $post_type_object ) ) {
			return false;
		}

		$is_public = true;
		if ( \property_exists( $post_type_object, 'public' ) ) {
			$is_public = $post_type_object->public;
		}

		return self::is_current_user_allowed_to_copy()
			&& $is_public
			&& $post_type_object->show_in_admin_bar
			&& self::is_post_type_enabled( $post->post_type );
	}


	/**
	 * Returns a link to edit, preview or view a post, in accordance to user capabilities.
	 *
	 * @param \WP_Post $post                              Post ID or Post object.
	 *
	 * @return string|null
	 */
	public static function get_edit_or_view_link( $post ) {
		$post = \get_post( $post );
		if ( ! $post ) {
			return null;
		}

		$can_edit_post    = \current_user_can( 'edit_post', $post->ID );
		$title            = \_draft_or_post_title( $post );
		$post_type_object = \get_post_type_object( $post->post_type );

		if ( $can_edit_post && 'trash' !== $post->post_status ) {
			return \sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				\get_edit_post_link( $post->ID ),
				/* translators: %s: post title */
				\esc_attr( \sprintf( \__( 'Edit &#8220;%s&#8221;', 'default' ), $title ) ),
				$title
			);
		} elseif ( \is_post_type_viewable( $post_type_object ) ) {
			if ( \in_array( $post->post_status, [ 'pending', 'draft', 'future' ], true ) ) {
				if ( $can_edit_post ) {
					$preview_link = \get_preview_post_link( $post );
					return \sprintf(
						'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
						\esc_url( $preview_link ),
						/* translators: %s: post title */
						\esc_attr( \sprintf( \__( 'Preview &#8220;%s&#8221;', 'default' ), $title ) ),
						$title
					);
				}
			} elseif ( 'trash' !== $post->post_status ) {
				return \sprintf(
					'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
					\get_permalink( $post->ID ),
					/* translators: %s: post title */
					\esc_attr( \sprintf( __( 'View &#8220;%s&#8221;', 'default' ), $title ) ),
					$title
				);
			}
		}
		return $title;
	}
}
