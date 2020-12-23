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
 * @return bool
 */
function duplicate_post_is_current_user_allowed_to_copy() {
	return current_user_can( 'copy_posts' );
}

/**
 * Tests if post type is enable to be copied.
 *
 * @param string $post_type The post type to check.
 * @return bool
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
 * @param int    $id      Optional. Post ID.
 * @param string $context Optional, default to display. How to write the '&', defaults to '&amp;'.
 * @param bool   $draft   Optional, default to true.
 * @return string
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
		/**
		 * Filter on the URL of the clone link
		 *
		 * @param string $url     The URL of the clone link.
		 * @param int    $ID      The ID of the post
		 * @param string $context The context in which the URL is used.
		 * @param bool   $draft   Whether to clone to a new draft.
		 *
		 * @return string
		 */
		apply_filters( 'duplicate_post_get_clone_post_link', admin_url( 'admin.php' . $action ), $post->ID, $context, $draft ),
		'duplicate-post_' . $post->ID
	);
}

/**
 * Displays duplicate post link for post.
 *
 * @param string|null $link   Optional. Anchor text.
 * @param string      $before Optional. Display before edit link.
 * @param string      $after  Optional. Display after edit link.
 * @param int         $id     Optional. Post ID.
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

	/**
	 * Filter on the clone link HTML
	 *
	 * @param string $link The full HTML tag of the link.
	 * @param int    $ID   The ID of the post
	 *
	 * @return string
	 */
	echo esc_html( $before . apply_filters( 'duplicate_post_clone_post_link', $link, $post->ID ) . $after );
}

/**
 * Gets the original post.
 *
 * @param int|null $post   Optional. Post ID or Post object.
 * @param string   $output Optional, default is Object. Either OBJECT, ARRAY_A, or ARRAY_N.
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

