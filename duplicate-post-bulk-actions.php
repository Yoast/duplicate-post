<?php

/*** BULK ACTIONS ***/

add_action('admin_init', 'duplicate_post_add_bulk_filters_for_enabled_post_types');

function duplicate_post_add_bulk_filters_for_enabled_post_types(){
	$duplicate_post_types_enabled = get_option('duplicate_post_types_enabled', array ('post', 'page'));
	foreach($duplicate_post_types_enabled as $duplicate_post_type_enabled){
		add_filter( "bulk_actions-edit-{$duplicate_post_type_enabled}", 'duplicate_post_register_bulk_action' );
		add_filter( "handle_bulk_actions-edit-{$duplicate_post_type_enabled}", 'duplicate_post_action_handler', 10, 3 );
	}
}

function duplicate_post_register_bulk_action($bulk_actions) {
	$bulk_actions['duplicate_post_clone'] = __( 'Clone', 'duplicate-post');
	return $bulk_actions;
}

function duplicate_post_action_handler( $redirect_to, $doaction, $post_ids ) {
	if ( $doaction !== 'duplicate_post_clone' ) {
		return $redirect_to;
	}
	$counter = 0;
	foreach ( $post_ids as $post_id ) {
		$post = get_post($post_id);
		if(!empty($post)){
			if( get_option('duplicate_post_copychildren') != 1
					|| !is_post_type_hierarchical( $post->post_type )
					|| (is_post_type_hierarchical( $post->post_type ) && !duplicate_post_has_ancestors_marked($post, $post_ids))){
						if(duplicate_post_create_duplicate($post)){
							$counter++;
						}
			}
		}
	}
	$redirect_to = add_query_arg( 'cloned', $counter, $redirect_to );
	return $redirect_to;
}

function duplicate_post_has_ancestors_marked($post, $post_ids){
	$ancestors_in_array = 0;
	$parent = $post->ID;
	while ($parent = wp_get_post_parent_id($parent)){
		if(in_array($parent, $post_ids)){
			$ancestors_in_array++;
		}
	}
	return ($ancestors_in_array !== 0);
}
