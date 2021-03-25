<?php
/**
 * Common functions.
 *
 * @package Yoast\WP\Duplicate_Post
 * @since   2.0
 */

use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\UI\Link_Builder;
use Yoast\WP\Duplicate_Post\Utils;

/**
 * Tests if post type is enabled to be copied.
 *
 * @param string $post_type The post type to check.
 * @return bool
 */
function duplicate_post_is_post_type_enabled( $post_type ) {
	$duplicate_post_types_enabled = get_option( 'duplicate_post_types_enabled', [ 'post', 'page' ] );
	if ( ! is_array( $duplicate_post_types_enabled ) ) {
		$duplicate_post_types_enabled = [ $duplicate_post_types_enabled ];
	}

	/** This filter is documented in src/permissions-helper.php */
	$duplicate_post_types_enabled = apply_filters( 'duplicate_post_enabled_post_types', $duplicate_post_types_enabled );
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
	$post = get_post( $id );
	if ( ! $post ) {
		return '';
	}

	$link_builder       = new Link_Builder();
	$permissions_helper = new Permissions_Helper();

	if ( ! $permissions_helper->should_links_be_displayed( $post ) ) {
		return '';
	}

	if ( $draft ) {
		return $link_builder->build_new_draft_link( $post, $context );
	}
	else {
		return $link_builder->build_clone_link( $post, $context );
	}
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

	if ( $link === null ) {
		$link = __( 'Copy to a new draft', 'duplicate-post' );
	}

	$link = '<a class="post-clone-link" href="' . esc_url( $url ) . '">' . esc_html( $link ) . '</a>';

	/**
	 * Filter on the clone link HTML.
	 *
	 * @param string $link The full HTML tag of the link.
	 * @param int    $ID   The ID of the post.
	 *
	 * @return string
	 */
	echo $before . apply_filters( 'duplicate_post_clone_post_link', $link, $post->ID ) . $after; // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Gets the original post.
 *
 * @param int|null $post   Optional. Post ID or Post object.
 * @param string   $output Optional, default is Object. Either OBJECT, ARRAY_A, or ARRAY_N.
 * @return mixed Post data.
 */
function duplicate_post_get_original( $post = null, $output = OBJECT ) {
	return Utils::get_original( $post, $output );
}
