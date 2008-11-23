<?php 
	if (!isset( $_GET['post'] ) && !isset( $_POST['post'] )) {
?>
<div class="wrap">
	<p><strong><?php _e('No page to duplicate has been supplied!', DUPLICATE_POST_I18N_DOMAIN) ?></strong></p>
</div>
<?php
	} else {
		// Get the original page
		$id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);	
		$post = duplicate_post_get_page($id);
		
		// Copy the page and insert it
		if (isset($post) && $post!=null) {
			$new_id = duplicate_post_create_duplicate_from_page($post);
		
			// Show the page edit
			echo '<meta content="0; URL=page.php?action=edit&post=' . $new_id . '" http-equiv="Refresh" />';
			exit;
		} else {
?>
<div class="wrap">
	<p><strong><?php _e('Post creation failed, could not find original post:', DUPLICATE_POST_I18N_DOMAIN) . ' ' . $id ?></strong></p>
</div>
<?php
		}
	}
?>