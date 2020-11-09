<?php
/**
 * Gutenberg (Block editor)/Classic Editor compatibility functions
 *
 * @package Duplicate Post
 * @since 4.0
 */

add_filter( 'duplicate_post_get_clone_post_link', 'duplicate_post_classic_editor_clone_link', 10, 4 );

/**
 * Edits the clone link URL to enforce Classic Editor legacy support.
 *
 * @see duplicate_post_get_clone_post_link()
 *
 * @param string $url     The duplicate post link URL.
 * @param int    $post_id The original post ID.
 * @param string $context The context in which the URL is used.
 * @param bool   $draft   Whether the link is "New Draft" or "Clone".
 *
 * @return string
 */
function duplicate_post_classic_editor_clone_link( $url, $post_id, $context, $draft ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return $url;
	}

	if ( isset( $_GET['classic-editor'] ) // phpcs:ignore WordPress.Security.NonceVerification
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
