<?php

/**
 * Test if the user is allowed to copy posts
 */
function duplicate_post_is_current_user_allowed_to_copy() {
	return current_user_can("level_" . duplicate_post_get_copy_user_level());
}

/**
 * Wrapper for the option 'duplicate_post_create_user_level'
 */
function duplicate_post_get_copy_user_level() {
	return get_option( 'duplicate_post_copy_user_level' );
}

// Template tag
/**
 * Retrieve duplicate post link for post.
 *
 * Can be used within the WordPress loop or outside of it. Can be used with
 * pages, posts, attachments, and revisions.
 *
 *
 * @param int $id Optional. Post ID.
 * @param string $context Optional, default to display. How to write the '&', defaults to '&amp;'.
 * @return string
 */
function duplicate_post_get_clone_post_link( $id = 0, $context = 'display' ) {
	if ( !duplicate_post_is_current_user_allowed_to_copy() )
	return;

	if ( !$post = &get_post( $id ) )
	return;

	if ( 'display' == $context )
	$action = '?action=duplicate_post_save_as_new_post_draft&amp;post='.$post->ID;
	else
	$action = '?action=duplicate_post_save_as_new_post_draft&post='.$post->ID;

	$post_type_object = get_post_type_object( $post->post_type );
	if ( !$post_type_object )
	return;

	return apply_filters( 'duplicate_post_get_clone_post_link', admin_url( "admin.php". $action ), $post->ID, $context );
}
/**
 * Display duplicate post link for post.
 *
 * @param string $link Optional. Anchor text.
 * @param string $before Optional. Display before edit link.
 * @param string $after Optional. Display after edit link.
 * @param int $id Optional. Post ID.
 */
function duplicate_post_clone_post_link( $link = null, $before = '', $after = '', $id = 0 ) {
	if ( !$post = &get_post( $id ) )
	return;

	if ( !$url = duplicate_post_get_clone_post_link( $post->ID ) )
	return;

	if ( null === $link )
	$link = __('Copy to a new draft', DUPLICATE_POST_I18N_DOMAIN);

	$post_type_obj = get_post_type_object( $post->post_type );
	$link = '<a class="post-clone-link" href="' . $url . '" title="'
	. esc_attr(__("Copy to a new draft", DUPLICATE_POST_I18N_DOMAIN))
	.'">' . $link . '</a>';
	echo $before . apply_filters( 'duplicate_post_clone_post_link', $link, $post->ID ) . $after;
}

?>