<?php
/*
 Plugin Name: Duplicate Post WPML Plugin
 Version: 0.1
 Author: Enrico Battocchi
 Author URI: http://lopo.it
 */


function duplicate_post_wpml_plugin($new_id, $old){
	global $wpdb;
	// copy the language from WPML custom table:
	// 1. get the language of the original
	$old_id = $old->ID;
	$old_lang = $wpdb->get_var( $wpdb->prepare( "SELECT `language_code` FROM `".$wpdb->prefix."icl_translations` WHERE `element_id` = '".$old_id."';" ) );

	// 2. set the language of the copy
	$wpdb->update($wpdb->prefix."icl_translations", array('language_code' => $old_lang), array('element_id' => $new_id));

	// clone all the translation group
	// 1. check if the old post is a translation group original
	$orig_lang = $wpdb->get_var( $wpdb->prepare( "SELECT `source_language_code` FROM `".$wpdb->prefix."icl_translations` WHERE `element_id` = '".$old_id."';" ) );
	if (!empty($orig_lang)) return;

	// 2. if so, get all the posts of the translation group

	$trid = $wpdb->get_var( $wpdb->prepare( "SELECT `trid` FROM `".$wpdb->prefix."icl_translations` WHERE `element_id` = '".$old_id."';" ) );
	$old_group = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `".$wpdb->prefix."icl_translations` WHERE `element_id` <> '".$old_id."' AND `trid` = '".$trid."';" ) );

	// 3. clone each post of the translation group
	$clonedids = array();
	foreach ($old_group as $obj){
		$objpost = get_post($obj->element_id);
		$clonedid = duplicate_post_create_duplicate($objpost, "");
		$orig_lang = $wpdb->get_var( $wpdb->prepare( "SELECT `source_language_code` FROM `".$wpdb->prefix."icl_translations` WHERE `element_id` = '".$obj->element_id."';" ) );
		$wpdb->update($wpdb->prefix."icl_translations", array('source_language_code' => $orig_lang), array('element_id' => $clonedid));
		$clonedids[] = $clonedid;
	}

	// 4. assign each new copy to the new translation group
	$new_trid = $wpdb->get_var( $wpdb->prepare( "SELECT `trid` FROM `".$wpdb->prefix."icl_translations` WHERE `element_id` = '".$new_id."';" ) );
	foreach ($clonedids as $clonedid){
		$wpdb->update($wpdb->prefix."icl_translations", array('trid' => $new_trid), array('element_id' => $clonedid));
	}
}

add_action( "dp_duplicate_post", "duplicate_post_wpml_plugin", 10, 2);
add_action( "dp_duplicate_page", "duplicate_post_wpml_plugin", 10, 2);

?>