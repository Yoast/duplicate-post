<?php
add_action( 'admin_init', 'duplicate_post_jetpack_init' );

function duplicate_post_jetpack_init() {
	add_filter('duplicate_post_blacklist_filter', 'duplicate_post_jetpack_add_to_blacklist', 10, 1 );
}

function duplicate_post_jetpack_add_to_blacklist($meta_blacklist) {
	$meta_blacklist[] = '_wpas*'; //Jetpack Publicize
	$meta_blacklist[] = '_publicize*'; //Jetpack Publicize
	
	$meta_blacklist[] = '_jetpack*'; //Jetpack Subscriptions etc.
	
	return $meta_blacklist;
}