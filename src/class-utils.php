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
	 * @param int|\WP_Post|null $post   Optional. Post ID or Post object.
	 * @param string            $output Optional, default is Object. Either OBJECT, ARRAY_A, or ARRAY_N.
	 *
	 * @return \WP_Post|null Post data if successful, null otherwise.
	 */
	public static function get_original( $post = null, $output = \OBJECT ) {
		$post = \get_post( $post );
		if ( ! $post ) {
			return null;
		}

		$original_id = self::get_original_post_id( $post->ID );

		if ( empty( $original_id ) ) {
			return null;
		}

		return \get_post( $original_id, $output );
	}

	/**
	 * Determines if the post has ancestors marked for copy.
	 *
	 * If we are copying children, and the post has already an ancestor marked for copy, we have to filter it out.
	 *
	 * @param \WP_Post $post     The post object.
	 * @param array    $post_ids The array of marked post IDs.
	 *
	 * @return bool Whether the post has ancestors marked for copy.
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
		return ( $ancestors_in_array !== 0 );
	}

	/**
	 * Returns a link to edit, preview or view a post, in accordance to user capabilities.
	 *
	 * @param \WP_Post $post Post ID or Post object.
	 *
	 * @return string|null The link to edit, preview or view a post.
	 */
	public static function get_edit_or_view_link( $post ) {
		$post = \get_post( $post );
		if ( ! $post ) {
			return null;
		}

		$can_edit_post    = \current_user_can( 'edit_post', $post->ID );
		$title            = \_draft_or_post_title( $post );
		$post_type_object = \get_post_type_object( $post->post_type );

		if ( $can_edit_post && $post->post_status !== 'trash' ) {
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
			} elseif ( $post->post_status !== 'trash' ) {
				return \sprintf(
					'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
					\get_permalink( $post->ID ),
					/* translators: %s: post title */
					\esc_attr( \sprintf( \__( 'View &#8220;%s&#8221;', 'default' ), $title ) ),
					$title
				);
			}
		}
		return $title;
	}

	/**
	 * Gets the ID of the original post intended to be rewritten with the copy for Rewrite & Republish.
	 *
	 * @param int $post_id The copy post ID.
	 *
	 * @return int The original post id of a copy for Rewrite & Republish.
	 */
	public static function get_original_post_id( $post_id ) {
		return (int) \get_post_meta( $post_id, '_dp_original', true );
	}

	/**
	 * Gets the registered WordPress roles.
	 *
	 * @return array The roles.
	 * @codeCoverageIgnore As this is a simple wrapper method for a built-in WordPress method, we don't have to test it.
	 */
	public static function get_roles() {
		global $wp_roles;

		return $wp_roles->get_names();
	}

	/**
	 * Gets the default meta field names to be filtered out.
	 *
	 * @return array The names of the meta fields to filter out by default.
	 */
	public static function get_default_filtered_meta_names() {
		return [
			'_edit_lock',
			'_edit_last',
			'_dp_original',
			'_dp_is_rewrite_republish_copy',
			'_dp_has_rewrite_republish_copy',
			'_dp_has_been_republished',
			'_dp_creation_date_gmt',
		];
	}

	/**
	 * Gets a Duplicate Post option from the database.
	 *
	 * @param string $option The option to get.
	 * @param string $key    The key to retrieve, if the option is an array.
	 *
	 * @return mixed The option.
	 */
	public static function get_option( $option, $key = '' ) {
		$option = \get_option( $option );

		if ( ! \is_array( $option ) || empty( $key ) ) {
			return $option;
		}

		if ( ! \array_key_exists( $key, $option ) ) {
			return '';
		}

		return $option[ $key ];
	}

	/**
	 * Determines if a plugin is active.
	 *
	 * We can't use is_plugin_active because this must work on the frontend too.
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins directory.
	 *
	 * @return bool Whether a plugin is currently active.
	 */
	public static function is_plugin_active( $plugin ) {
		if ( \in_array( $plugin, (array) \get_option( 'active_plugins', [] ), true ) ) {
			return true;
		}

		if ( ! \is_multisite() ) {
			return false;
		}

		$plugins = \get_site_option( 'active_sitewide_plugins' );
		return isset( $plugins[ $plugin ] );
	}
}
