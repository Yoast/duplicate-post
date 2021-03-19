<?php
/**
 * JetPack compatibility functions.
 *
 * @package Yoast\WP\Duplicate_Post
 * @since   3.2
 */

add_action( 'admin_init', 'duplicate_post_jetpack_init' );

/**
 * Add handlers for JetPack compatibility.
 */
function duplicate_post_jetpack_init() {
	add_filter( 'duplicate_post_excludelist_filter', 'duplicate_post_jetpack_add_to_excludelist', 10, 1 );

	if ( class_exists( 'WPCom_Markdown' ) ) {
		add_action( 'duplicate_post_pre_copy', 'duplicate_post_jetpack_disable_markdown', 10 );
		add_action( 'duplicate_post_post_copy', 'duplicate_post_jetpack_enable_markdown', 10 );
	}
}

/**
 * Add some JetPack custom field wildcards to be filtered out when cloning.
 *
 * @param array $meta_excludelist The array containing the blacklist of custom fields.
 * @return array
 */
function duplicate_post_jetpack_add_to_excludelist( $meta_excludelist ) {
	$meta_excludelist[] = '_wpas*'; // Jetpack Publicize.
	$meta_excludelist[] = '_publicize*'; // Jetpack Publicize.

	$meta_excludelist[] = '_jetpack*'; // Jetpack Subscriptions etc.

	return $meta_excludelist;
}

/**
 * Disable Markdown.
 *
 * To be called before copy.
 */
function duplicate_post_jetpack_disable_markdown() {
	WPCom_Markdown::get_instance()->unload_markdown_for_posts();
}

/**
 * Enaable Markdown.
 *
 * To be called after copy.
 */
function duplicate_post_jetpack_enable_markdown() {
	WPCom_Markdown::get_instance()->load_markdown_for_posts();
}
