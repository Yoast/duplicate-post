<?php

add_filter( 'duplicate_post_get_clone_post_link', 'duplicate_post_classic_editor_clone_link', 10, 4 );

/**
 * Edits the clone link URL to enforce Classic Editor legacy support.
 *
 * @see duplicate_post_get_clone_post_link()
 *
 * @param string  $url The duplicate post link URL.
 * @param WP_Post $post The original post object.
 * @param string  $context The context in which the URL is used.
 * @param boolean $draft Whether the link is "New Draft" or "Clone".
 *
 * @return string $url
 */
function duplicate_post_classic_editor_clone_link( $url, $post, $context, $draft ) {
	if ( isset( $_GET['classic-editor'] )
		 || ( $draft && function_exists( 'gutenberg_post_has_blocks' ) && ! gutenberg_post_has_blocks( $post ) )
		 || ( $draft && function_exists( 'has_blocks' ) && ! has_blocks( $post ) ) ) {
		if ( 'display' === $context ) {
			$url .= '&amp;classic-editor';
		} else {
			$url .= '&classic-editor';
		}
	}
	return $url;
}