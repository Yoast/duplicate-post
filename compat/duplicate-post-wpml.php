<?php
add_action( 'admin_init', 'duplicate_post_wpml_init' );

function duplicate_post_wpml_init() {
	if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
		add_action('dp_duplicate_page', 'duplicate_post_wpml_copy_translations', 10, 3);
		add_action('dp_duplicate_post', 'duplicate_post_wpml_copy_translations', 10, 3);
	}
}

function duplicate_post_wpml_copy_translations($post_id, $post, $status = '') {
	global $sitepress;

	remove_action('dp_duplicate_page', 'duplicate_post_wpml_copy_translations', 10);
	remove_action('dp_duplicate_post', 'duplicate_post_wpml_copy_translations', 10);

	$current_language = $sitepress->get_current_language();
	$trid = $sitepress->get_element_trid($post->ID);
	if (!empty($trid)) {
		$translations = $sitepress->get_element_translations($trid);
		$new_trid = $sitepress->get_element_trid($post_id);
		foreach ($translations as $code => $details) {
			if ($code != $current_language) {
				$translation = get_post($details->element_id);
				$new_post_id = duplicate_post_create_duplicate($translation, $status);
				$sitepress->set_element_language_details($new_post_id, 'post_' . $translation->post_type, $new_trid, $code, $current_language);
			}
		}
	}
}
?>