<?php
/**
 * Common functions.
 *
 * @package Duplicate Post
 * @since 2.0
 */

/**
 * Tests if the user is allowed to copy posts.
 *
 * @return boolean.
 */
function duplicate_post_is_current_user_allowed_to_copy() {
	return current_user_can( 'copy_posts' );
}

/**
 * Tests if post type is enable to be copied.
 *
 * @param string $post_type The post type to check.
 * @return boolean.
 */
function duplicate_post_is_post_type_enabled( $post_type ) {
	$duplicate_post_types_enabled = get_option( 'duplicate_post_types_enabled', array( 'post', 'page' ) );
	if ( ! is_array( $duplicate_post_types_enabled ) ) {
		$duplicate_post_types_enabled = array( $duplicate_post_types_enabled );
	}
	return in_array( $post_type, $duplicate_post_types_enabled, true );
}

/**
 * Template tag to retrieve/display duplicate post link for post.
 *
 * @param int     $id Optional. Post ID.
 * @param string  $context Optional, default to display. How to write the '&', defaults to '&amp;'.
 * @param boolean $draft Optional, default to true.
 * @return string.
 */
function duplicate_post_get_clone_post_link( $id = 0, $context = 'display', $draft = true ) {
	if ( ! duplicate_post_is_current_user_allowed_to_copy() ) {
		return '';
	}

	$post = get_post( $id );
	if ( ! $post ) {
		return '';
	}

	if ( ! duplicate_post_is_post_type_enabled( $post->post_type ) ) {
		return '';
	}

	if ( $draft ) {
		$action_name = 'duplicate_post_save_as_new_post_draft';
	} else {
		$action_name = 'duplicate_post_save_as_new_post';
	}

	if ( 'display' === $context ) {
		$action = '?action=' . $action_name . '&amp;post=' . $post->ID;
	} else {
		$action = '?action=' . $action_name . '&post=' . $post->ID;
	}

	$post_type_object = get_post_type_object( $post->post_type );
	if ( ! $post_type_object ) {
		return '';
	}

	return wp_nonce_url(
		apply_filters( 'duplicate_post_get_clone_post_link', admin_url( 'admin.php' . $action ), $post->ID, $context, $draft ),
		'duplicate-post_' . $post->ID
	);
}

/**
 * Displays duplicate post link for post.
 *
 * @param string $link Optional. Anchor text.
 * @param string $before Optional. Display before edit link.
 * @param string $after Optional. Display after edit link.
 * @param int    $id Optional. Post ID.
 */
function duplicate_post_clone_post_link( $link = null, $before = '', $after = '', $id = 0 ) {
	$post = get_post( $id );
	if ( ! $post ) {
		return;
	}

	$url = duplicate_post_get_clone_post_link( $post->ID );
	if ( ! $url ) {
		return;
	}

	if ( null === $link ) {
		$link = esc_html__( 'Copy to a new draft', 'duplicate-post' );
	}

	$link = '<a class="post-clone-link" href="' . $url . '">' . $link . '</a>';
	echo esc_html( $before . apply_filters( 'duplicate_post_clone_post_link', $link, $post->ID ) . $after );
}

/**
 * Gets the original post.
 *
 * @param int    $post Optional. Post ID or Post object.
 * @param string $output Optional, default is Object. Either OBJECT, ARRAY_A, or ARRAY_N.
 * @return mixed Post data.
 */
function duplicate_post_get_original( $post = null, $output = OBJECT ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	$original_id = get_post_meta( $post->ID, '_dp_original' );
	if ( empty( $original_id ) ) {
		return null;
	}
	$original_post = get_post( $original_id[0], $output );
	return $original_post;
}

/**
 * Returns a link to edit, preview or view a post, in accordance to user capabilities.
 *
 * @ignore
 *
 * @param WP_Post $post Post ID or Post object.
 * @return string
 */
function duplicate_post_get_edit_or_view_link( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}

	$can_edit_post    = current_user_can( 'edit_post', $post->ID );
	$title            = _draft_or_post_title( $post );
	$post_type_object = get_post_type_object( $post->post_type );

	if ( $can_edit_post && 'trash' != $post->post_status ) {
		return sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			get_edit_post_link( $post->ID ),
			/* translators: %s: post title */
			esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'default' ), $title ) ),
			$title
		);
	} elseif ( duplicate_post_is_post_type_viewable( $post_type_object ) ) {
		if ( in_array( $post->post_status, array( 'pending', 'draft', 'future' ) ) ) {
			if ( $can_edit_post ) {
				$preview_link    = get_preview_post_link( $post );
				return sprintf(
					'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
					esc_url( $preview_link ),
					/* translators: %s: post title */
					esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;', 'default' ), $title ) ),
					$title
				);
			}
		} elseif ( 'trash' != $post->post_status ) {
			return sprintf(
				'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
				get_permalink( $post->ID ),
				/* translators: %s: post title */
				esc_attr( sprintf( __( 'View &#8220;%s&#8221;', 'default' ), $title ) ),
				$title
			);
		}
	}
	return $title;
}

/**
 * Workaround for is_post_type_viewable (introduced in WP 4.4).
 *
 * @ignore
 *
 * @param mixed $post_type  The post type to check.
 * @return bool
 */
function duplicate_post_is_post_type_viewable( $post_type ) {
	if ( function_exists( 'is_post_type_viewable' ) ) {
		return is_post_type_viewable( $post_type );
	} else {
		if ( is_scalar( $post_type ) ) {
			$post_type = get_post_type_object( $post_type );
			if ( ! $post_type ) {
				return false;
			}
		}
		return $post_type->publicly_queryable || ( $post_type->_builtin && $post_type->public );
	}
}

/**
 * Shows link in the Toolbar.
 *
 * @global WP_Query $wp_the_query.
 *
 * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance.
 */
function duplicate_post_admin_bar_render( $wp_admin_bar ) {
	global $wp_the_query;

	if ( is_admin() ) {
		$current_screen = get_current_screen();
		$post           = get_post();

		if ( empty( $post ) ) {
			return;
		}

		/** This filter is documented in duplicate-post-admin.php */
		if ( ! apply_filters( 'duplicate_post_show_link', duplicate_post_is_current_user_allowed_to_copy(), $post ) ) {
			return;
		}

		$post_type_object = get_post_type_object( $post->post_type );

		if ( 'post' === $current_screen->base
			&& 'add' !== $current_screen->action
			&& ( $post_type_object )
			&& duplicate_post_is_current_user_allowed_to_copy()
			&& ( $post_type_object->public )
			&& ( $post_type_object->show_in_admin_bar )
			&& ( duplicate_post_is_post_type_enabled( $post->post_type ) ) ) {
				$wp_admin_bar->add_menu(
					array(
						'id'    => 'new_draft',
						'title' => esc_attr__( 'Copy to a new draft', 'duplicate-post' ),
						'href'  => duplicate_post_get_clone_post_link( $post->ID ),
					)
				);
		}
	} else {
		$current_object = $wp_the_query->get_queried_object();

		if ( empty( $current_object ) ) {
			return;
		}

		/** This filter is documented in duplicate-post-admin.php */
		if ( ! apply_filters( 'duplicate_post_show_link', duplicate_post_is_current_user_allowed_to_copy(), $current_object ) ) {
			return;
		}

		$post_type_object = get_post_type_object( $current_object->post_type );

		if ( ! empty( $current_object->post_type )
			&& ( $post_type_object )
			&& duplicate_post_is_current_user_allowed_to_copy()
			&& ( $post_type_object->show_in_admin_bar )
			&& ( duplicate_post_is_post_type_enabled( $current_object->post_type ) ) ) {
			$wp_admin_bar->add_menu(
				array(
					'id'    => 'new_draft',
					'title' => esc_attr__( 'Copy to a new draft', 'duplicate-post' ),
					'href'  => duplicate_post_get_clone_post_link( $current_object->ID ),
				)
			);
		}
	}
}

/**
 * Links stylesheet for Toolbar link.
 *
 * @global WP_Query $wp_the_query.
 */
function duplicate_post_add_css() {
	global $wp_the_query;

	if ( ! is_admin_bar_showing() ) {
		return;
	}

	if ( is_admin() ) {
		$current_screen = get_current_screen();
		$post           = get_post();

		if ( empty( $post ) ) {
			return;
		}

		/** This filter is documented in duplicate-post-admin.php */
		if ( ! apply_filters( 'duplicate_post_show_link', duplicate_post_is_current_user_allowed_to_copy(), $post ) ) {
			return;
		}

		$post_type_object = get_post_type_object( $post->post_type );

		if ( 'post' === $current_screen->base
			&& 'add' !== $current_screen->action
			&& ( $post_type_object )
			&& duplicate_post_is_current_user_allowed_to_copy()
			&& ( $post_type_object->public )
			&& ( $post_type_object->show_in_admin_bar )
			&& ( duplicate_post_is_post_type_enabled( $post->post_type ) ) ) {
			wp_enqueue_style( 'duplicate-post', plugins_url( '/duplicate-post.css', __FILE__ ), array(), DUPLICATE_POST_CURRENT_VERSION );
		}
	} else {
		$current_object = $wp_the_query->get_queried_object();

		if ( empty( $current_object ) ) {
			return;
		}

		/** This filter is documented in duplicate-post-admin.php */
		if ( ! apply_filters( 'duplicate_post_show_link', duplicate_post_is_current_user_allowed_to_copy(), $current_object ) ) {
			return;
		}

		$post_type_object = get_post_type_object( $current_object->post_type );

		if ( ! empty( $current_object->post_type )
			&& ( $post_type_object )
			&& duplicate_post_is_current_user_allowed_to_copy()
			&& ( $post_type_object->show_in_admin_bar )
			&& ( duplicate_post_is_post_type_enabled( $current_object->post_type ) ) ) {
			wp_enqueue_style( 'duplicate-post', plugins_url( '/duplicate-post.css', __FILE__ ), array(), DUPLICATE_POST_CURRENT_VERSION );
		}
	}
}

add_action( 'init', 'duplicate_post_init' );

/**
 * Adds the handlers for displaying link in Toolbar.
 */
function duplicate_post_init() {
	if ( 1 === intval( get_option( 'duplicate_post_show_adminbar' ) ) ) {
		add_action( 'wp_before_admin_bar_render', 'duplicate_post_admin_bar_render' );
		add_action( 'wp_enqueue_scripts', 'duplicate_post_add_css' );
		add_action( 'admin_enqueue_scripts', 'duplicate_post_add_css' );
	}
}

/**
 * Sorts taxonomy objects: first public, then private.
 *
 * @ignore
 * @param WP_Taxonomy $a First taxonomy object.
 * @param WP_Taxonomy $b Second taxonomy object.
 * @return boolean.
 */
function duplicate_post_tax_obj_cmp( $a, $b ) {
	return ( $a->public < $b->public );
}
