<?php
/**
 * Uninstall functions
 *
 * @package Duplicate Post
 * @since 4.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

if ( ! is_multisite() ) {
	duplicate_post_delete_options();
	delete_site_option( 'duplicate_post_show_notice' );
} else {
	$blogs = get_sites();
	foreach ( $blogs as $blog ) {
		switch_to_blog( $blog->blog_id );
		duplicate_post_delete_options();
		restore_current_blog();
	}
	delete_site_option( 'duplicate_post_show_notice' );
}

function duplicate_post_delete_options() {
	delete_option( 'duplicate_post_copytitle' );
	delete_option( 'duplicate_post_copydate' );
	delete_option( 'duplicate_post_copystatus' );
	delete_option( 'duplicate_post_copyslug' );
	delete_option( 'duplicate_post_copyexcerpt' );
	delete_option( 'duplicate_post_copycontent' );
	delete_option( 'duplicate_post_copythumbnail' );
	delete_option( 'duplicate_post_copytemplate' );
	delete_option( 'duplicate_post_copyformat' );
	delete_option( 'duplicate_post_copyauthor' );
	delete_option( 'duplicate_post_copypassword' );
	delete_option( 'duplicate_post_copyattachments' );
	delete_option( 'duplicate_post_copychildren' );
	delete_option( 'duplicate_post_copycomments' );
	delete_option( 'duplicate_post_copymenuorder' );
	delete_option( 'duplicate_post_taxonomies_blacklist' );
	delete_option( 'duplicate_post_title_prefix' );
	delete_option( 'duplicate_post_title_suffix' );
	delete_option( 'duplicate_post_increase_menu_order_by' );
	delete_option( 'duplicate_post_roles' );
	delete_option( 'duplicate_post_blacklist' );
	delete_option( 'duplicate_post_types_enabled' );
	delete_option( 'duplicate_post_show_row' );
	delete_option( 'duplicate_post_show_adminbar' );
	delete_option( 'duplicate_post_show_submitbox' );
	delete_option( 'duplicate_post_show_bulkactions' );
	delete_option( 'duplicate_post_show_original_column' );
	delete_option( 'duplicate_post_show_original_in_post_states' );
	delete_option( 'duplicate_post_show_original_meta_box' );
	delete_option( 'duplicate_post_version' );
	delete_option( 'duplicate_post_show_notice' );
}
