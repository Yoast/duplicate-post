<?php 
	if (!isset( $_GET['post'] ) && !isset( $_POST['post'] )) {
?>
<div class="wrap">
	<p><strong><?php _e('No post to duplicate has been supplied!', DUPLICATE_POST_I18N_DOMAIN) ?></strong></p>
</div>
<?php
	} else {
		// Get the original template
		$id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);	
		$post = duplicate_post_get_post($id);
		
		// Copy the post and insert it as a template
		if (isset($post) && $post!=null) {
			$new_id = duplicate_post_create_duplicate_from_post($post);
		
			// Show the post edit
			echo '<meta content="0; URL=post.php?action=edit&post=' . $new_id . '" http-equiv="Refresh" />';
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