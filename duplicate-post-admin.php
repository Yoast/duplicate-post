<?php
// Added by WarmStal
if(!is_admin())
	return;

require_once (dirname(__FILE__).'/duplicate-post-options.php');

/**
 * Wrapper for the option 'duplicate_post_version'
*/
function duplicate_post_get_installed_version() {
	return get_site_option( 'duplicate_post_version' );
}

/**
 * Wrapper for the defined constant DUPLICATE_POST_CURRENT_VERSION
 */
function duplicate_post_get_current_version() {
	return DUPLICATE_POST_CURRENT_VERSION;
}

/**
 * Plugin upgrade
 */
add_action('admin_init','duplicate_post_plugin_upgrade');

function duplicate_post_plugin_upgrade() {
	$installed_version = duplicate_post_get_installed_version();

	if (empty($installed_version)) { // first install

		// Add capability to admin and editors

		// Get default roles
		$default_roles = array(
				3 => 'editor',
				8 => 'administrator',
		);

		// Cycle all roles and assign capability if its level >= duplicate_post_copy_user_level
		foreach ($default_roles as $level => $name){
			$role = get_role($name);
			if(!empty($role)) $role->add_cap( 'copy_posts' );
		}
			
		add_option('duplicate_post_copytitle','1');
		add_option('duplicate_post_copydate','0');
		add_option('duplicate_post_copystatus','0');
		add_option('duplicate_post_copyslug','1');
		add_option('duplicate_post_copyexcerpt','1');
		add_option('duplicate_post_copycontent','1');
		add_option('duplicate_post_copypassword','0');
		add_option('duplicate_post_copyattachments','0');
		add_option('duplicate_post_copychildren','0');
		add_option('duplicate_post_copycomments','0');
		add_option('duplicate_post_taxonomies_blacklist',array());
		add_option('duplicate_post_blacklist','');
		add_option('duplicate_post_types_enabled',array('post', 'page'));
		add_option('duplicate_post_show_row','1');
		add_option('duplicate_post_show_adminbar','1');
		add_option('duplicate_post_show_submitbox','1');
	} else if ( $installed_version==duplicate_post_get_current_version() ) { //re-install
		// do nothing
	} else { //upgrade form previous version
		// delete old, obsolete options
		delete_option('duplicate_post_admin_user_level');
		delete_option('duplicate_post_create_user_level');
		delete_option('duplicate_post_view_user_level');
		delete_option('dp_notice');

		$installed_version_numbers = explode('.', $installed_version);
		
		if($installed_version_numbers[0] == 2){ // upgrading from 2.*
			/*
			 * Convert old userlevel option to new capability scheme
			 */
			
			// Get old duplicate_post_copy_user_level option
			$min_user_level = get_option('duplicate_post_copy_user_level');
			
			if (!empty($min_user_level)){
				// Get default roles
				$default_roles = array(
						1 => 'contributor',
						2 => 'author',
						3 => 'editor',
						8 => 'administrator',
				);
			
				// Cycle all roles and assign capability if its level >= duplicate_post_copy_user_level
				foreach ($default_roles as $level => $name){
					$role = get_role($name);
					if ($role && $min_user_level <= $level)
						$role->add_cap( 'copy_posts' );
				}
			
				// delete old option
				delete_option('duplicate_post_copy_user_level');
			}
			
			add_option('duplicate_post_copytitle','1');
			add_option('duplicate_post_copydate','0');
			add_option('duplicate_post_copystatus','0');
			add_option('duplicate_post_copyslug','1');
			add_option('duplicate_post_copyexcerpt','1');
			add_option('duplicate_post_copycontent','1');
			add_option('duplicate_post_copypassword','0');
			add_option('duplicate_post_copyattachments','0');
			add_option('duplicate_post_copychildren','0');
			add_option('duplicate_post_copycomments','0');
			add_option('duplicate_post_taxonomies_blacklist',array());
			add_option('duplicate_post_blacklist','');
			add_option('duplicate_post_types_enabled',array('post', 'page'));
			add_option('duplicate_post_show_row','1');
			add_option('duplicate_post_show_adminbar','1');
			add_option('duplicate_post_show_submitbox','1');
			
			// show notice about new features
			add_site_option('duplicate_post_show_notice','1');
			
		} else if($installed_version_numbers[0] == 3){ // upgrading from 3.*		
			// hide notice, we assume people already know of new features
			delete_option('duplicate_post_show_notice', 0);
			update_site_option('duplicate_post_show_notice', 0);
		}
		
		
	}
	// Update version number
	delete_option('duplicate_post_version');
	update_site_option( 'duplicate_post_version', duplicate_post_get_current_version() );

}

if (get_option('duplicate_post_show_row') == 1){
	add_filter('post_row_actions', 'duplicate_post_make_duplicate_link_row',10,2);
	add_filter('page_row_actions', 'duplicate_post_make_duplicate_link_row',10,2);
}


if (get_site_option('duplicate_post_show_notice') == 1){
	/**
	 * Shows the update notice
	 */
	function duplicate_post_show_update_notice() {
		if(!current_user_can( 'manage_options')) return;
		$class = 'notice is-dismissible';
		$message = sprintf(__('<strong>Duplicate Post has been greatly redesigned in its options page.</strong> Please <a href="%s">review the settings</a> to make sure it works as you expect.', 'duplicate-post'), admin_url('options-general.php?page=duplicatepost'));
		$message .= '<br/>';
		$message .= '<a href="http://lopo.it/duplicate-post-plugin">'.__('Donate', 'duplicate-post').' (10¢) </a> | <a id="duplicate-post-dismiss-notice" href="javascript:duplicate_post_dismiss_notice();">'.__('Dismiss this notice.').'</a>';
		echo '<div id="duplicate-post-notice" class="'.$class.'"><p>'.$message.'</p></div>';
		echo "<script>
				function duplicate_post_dismiss_notice(){
					var data = {
					'action': 'duplicate_post_dismiss_notice',
					};
	
					jQuery.post(ajaxurl, data, function(response) {
						jQuery('#duplicate-post-notice').hide();
					});
				}
	
				jQuery(document).ready(function(){
					jQuery('.notice-dismiss').click(function(){
						duplicate_post_dismiss_notice();
					});
				});
				</script>";
	}

	add_action( 'admin_notices', 'duplicate_post_show_update_notice' );
	add_action( 'wp_ajax_duplicate_post_dismiss_notice', 'duplicate_post_dismiss_notice' );
	
	function duplicate_post_dismiss_notice() {
		$result = update_site_option('duplicate_post_show_notice', 0);
		return $result;
		wp_die();
	}
}

/**
 * Add the link to action list for post_row_actions
 */
function duplicate_post_make_duplicate_link_row($actions, $post) {
	if (duplicate_post_is_current_user_allowed_to_copy() && duplicate_post_is_post_type_enabled($post->post_type)) {
		$actions['clone'] = '<a href="'.duplicate_post_get_clone_post_link( $post->ID , 'display', false).'" title="'
				. esc_attr(__("Clone this item", 'duplicate-post'))
				. '">' .  __('Clone', 'duplicate-post') . '</a>';
		$actions['edit_as_new_draft'] = '<a href="'. duplicate_post_get_clone_post_link( $post->ID ) .'" title="'
				. esc_attr(__('Copy to a new draft', 'duplicate-post'))
				. '">' .  __('New Draft', 'duplicate-post') . '</a>';
	}
	return $actions;
}

/**
 * Add a button in the post/page edit screen to create a clone
 */
if (get_option('duplicate_post_show_submitbox') == 1){
	add_action( 'post_submitbox_start', 'duplicate_post_add_duplicate_post_button' );
}

function duplicate_post_add_duplicate_post_button() {
	if ( isset( $_GET['post'] )){
		$id = $_GET['post'];
		$post = get_post($id);
		if(duplicate_post_is_current_user_allowed_to_copy() && duplicate_post_is_post_type_enabled($post->post_type)) {
	 	?>
<div id="duplicate-action">
	<a class="submitduplicate duplication"
		href="<?php echo duplicate_post_get_clone_post_link( $_GET['post'] ) ?>"><?php _e('Copy to a new draft', 'duplicate-post'); ?>
	</a>
</div>
<?php
		}
	}
}

/**
 * Connect actions to functions
 */
add_action('admin_action_duplicate_post_save_as_new_post', 'duplicate_post_save_as_new_post');
add_action('admin_action_duplicate_post_save_as_new_post_draft', 'duplicate_post_save_as_new_post_draft');

/*
 * This function calls the creation of a new copy of the selected post (as a draft)
* then redirects to the edit post screen
*/
function duplicate_post_save_as_new_post_draft(){
	duplicate_post_save_as_new_post('draft');
}

add_filter('removable_query_args', 'duplicate_post_add_removable_query_arg', 10, 1);

function duplicate_post_add_removable_query_arg( $removable_query_args ){
	$removable_query_args[] = 'cloned';
	return $removable_query_args;
}

/*
 * This function calls the creation of a new copy of the selected post (by default preserving the original publish status)
* then redirects to the post list
*/
function duplicate_post_save_as_new_post($status = ''){
	if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'duplicate_post_save_as_new_post' == $_REQUEST['action'] ) ) ) {
		wp_die(__('No post to duplicate has been supplied!', 'duplicate-post'));
	}

	// Get the original post
	$id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
	$post = get_post($id);

	// Copy the post and insert it
	if (isset($post) && $post!=null) {
		$new_id = duplicate_post_create_duplicate($post, $status);

		if ($status == ''){
			$sendback = remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'cloned', 'ids'), wp_get_referer() );
			// Redirect to the post list screen
			wp_redirect( add_query_arg( array( 'cloned' => 1, 'ids' => $post->ID), $sendback ) );
		} else {
			// Redirect to the edit screen for the new draft post
			wp_redirect( add_query_arg( array( 'cloned' => 1, 'ids' => $post->ID), admin_url( 'post.php?action=edit&post=' . $new_id ) ) );
		}
		exit;

	} else {
		wp_die(__('Copy creation failed, could not find original:', 'duplicate-post') . ' ' . htmlspecialchars($id));
	}
}

/**
 * Copy the taxonomies of a post to another post
 */
function duplicate_post_copy_post_taxonomies($new_id, $post) {
	global $wpdb;
	if (isset($wpdb->terms)) {
		// Clear default category (added by wp_insert_post)
		wp_set_object_terms( $new_id, NULL, 'category' );

		$post_taxonomies = get_object_taxonomies($post->post_type);
		$taxonomies_blacklist = get_option('duplicate_post_taxonomies_blacklist');
		if ($taxonomies_blacklist == "") $taxonomies_blacklist = array();
		$taxonomies = array_diff($post_taxonomies, $taxonomies_blacklist);
		foreach ($taxonomies as $taxonomy) {
			$post_terms = wp_get_object_terms($post->ID, $taxonomy, array( 'orderby' => 'term_order' ));
			$terms = array();
			for ($i=0; $i<count($post_terms); $i++) {
				$terms[] = $post_terms[$i]->slug;
			}
			wp_set_object_terms($new_id, $terms, $taxonomy);
		}
	}
}

/**
 * Copy the meta information of a post to another post
*/
function duplicate_post_copy_post_meta_info($new_id, $post) {
	$post_meta_keys = get_post_custom_keys($post->ID);
	if (empty($post_meta_keys)) return;
	$meta_blacklist = explode(",",get_option('duplicate_post_blacklist'));
	if ($meta_blacklist == "") $meta_blacklist = array();
	$meta_blacklist = array_map('trim', $meta_blacklist);
	$meta_blacklist[] = '_wpas_done_all'; //Jetpack Publicize
	$meta_blacklist[] = '_wpas_done_'; //Jetpack Publicize
	$meta_blacklist[] = '_wpas_mess'; //Jetpack Publicize
	$meta_blacklist[] = '_edit_lock'; // edit lock
	$meta_blacklist[] = '_edit_last'; // edit lock
	$meta_keys = array_diff($post_meta_keys, $meta_blacklist);

	foreach ($meta_keys as $meta_key) {
		$meta_values = get_post_custom_values($meta_key, $post->ID);
		foreach ($meta_values as $meta_value) {
			$meta_value = maybe_unserialize($meta_value);
			add_post_meta($new_id, $meta_key, $meta_value);
		}
	}
}

/**
 * Copy the attachments
*/
function duplicate_post_copy_attachments($new_id, $post){
	// get thumbnail ID
	$old_thumbnail_id = get_post_thumbnail_id($post->ID);
	// get children
	$children = get_posts(array( 'post_type' => 'any', 'numberposts' => -1, 'post_status' => 'any', 'post_parent' => $post->ID ));
	// clone old attachments
	foreach($children as $child){
		if ($child->post_type != 'attachment') continue;
		$url = wp_get_attachment_url($child->ID);
		// Let's copy the actual file
		$tmp = download_url( $url );
		if( is_wp_error( $tmp ) ) {
			@unlink($tmp);
			continue;
		}

		$desc = addslashes($child->post_content);

		$file_array = array();
		$file_array['name'] = basename($url);
		$file_array['tmp_name'] = $tmp;
		// "Upload" to the media collection
		$new_attachment_id = media_handle_sideload( $file_array, $new_id, $desc );

		if ( is_wp_error($new_attachment_id) ) {
			@unlink($file_array['tmp_name']);
			continue;
		}
		$new_post_author = wp_get_current_user();
		$cloned_child = array(
				'ID'           => $new_attachment_id,
				'post_title'   => addslashes($child->post_title),
				'post_exceprt' => addslashes($child->post_title),
				'post_author'  => $new_post_author->ID
		);
		wp_update_post( $cloned_child );

		$alt_title = get_post_meta($child->ID, '_wp_attachment_image_alt', true);
		if($alt_title) update_post_meta($new_attachment_id, '_wp_attachment_image_alt', $alt_title);

		// if we have cloned the post thumbnail, set the copy as the thumbnail for the new post
		if($old_thumbnail_id == $child->ID){
				set_post_thumbnail($new_id, $new_attachment_id);
		}
		
	}
}

/**
 * Copy children posts
 */
function duplicate_post_copy_children($new_id, $post){
	// get children
	$children = get_posts(array( 'post_type' => 'any', 'numberposts' => -1, 'post_status' => 'any', 'post_parent' => $post->ID ));
	// clone old attachments
	foreach($children as $child){
		if ($child->post_type == 'attachment') continue;
		duplicate_post_create_duplicate($child, '', $new_id);
	}
}

/**
 * Copy comments
 */
function duplicate_post_copy_comments($new_id, $post){
	$comments = get_comments(array(
		'post_id' => $post->ID,
		'order' => 'ASC',
		'orderby' => 'comment_date_gmt'
	));

	$old_id_to_new = array();
	foreach ($comments as $comment){
		//do not copy pingbacks or trackbacks
		if(!empty($comment->comment_type)) continue;
		$parent = ($comment->comment_parent && $old_id_to_new[$comment->comment_parent])?$old_id_to_new[$comment->comment_parent]:0;
		$commentdata = array(
			'comment_post_ID' => $new_id,
			'comment_author' => $comment->comment_author,
			'comment_author_email' => $comment->comment_author_email,
			'comment_author_url' => $comment->comment_author_url,
			'comment_content' => $comment->comment_content,
			'comment_type' => '', 
			'comment_parent' => $parent,
			'user_id' => $comment->user_id,
			'comment_author_IP' => $comment->comment_author_IP,
			'comment_agent' => $comment->comment_agent,
			'comment_karma' => $comment->comment_karma,
			'comment_approved' => $comment->comment_approved,
		);
		if(get_option('duplicate_post_copydate') == 1){
			$commentdata['comment_date'] = $comment->comment_date ;
			$commentdata['comment_date_gmt'] = get_gmt_from_date($comment->comment_date);
		}
		$new_comment_id = wp_insert_comment($commentdata);
		$old_id_to_new[$comment->comment_ID] = $new_comment_id;
	}
}

// Using our action hooks

add_action('dp_duplicate_post', 'duplicate_post_copy_post_meta_info', 10, 2);
add_action('dp_duplicate_page', 'duplicate_post_copy_post_meta_info', 10, 2);

if(get_option('duplicate_post_copychildren') == 1){
	add_action('dp_duplicate_post', 'duplicate_post_copy_children', 20, 2);
	add_action('dp_duplicate_page', 'duplicate_post_copy_children', 20, 2);
}

if(get_option('duplicate_post_copyattachments') == 1){
	add_action('dp_duplicate_post', 'duplicate_post_copy_attachments', 30, 2);
	add_action('dp_duplicate_page', 'duplicate_post_copy_attachments', 30, 2);
}

if(get_option('duplicate_post_copycomments') == 1){
	add_action('dp_duplicate_post', 'duplicate_post_copy_comments', 40, 2);
	add_action('dp_duplicate_page', 'duplicate_post_copy_comments', 40, 2);
}

add_action('dp_duplicate_post', 'duplicate_post_copy_post_taxonomies', 50, 2);
add_action('dp_duplicate_page', 'duplicate_post_copy_post_taxonomies', 50, 2);

/**
 * Create a duplicate from a post
 */
function duplicate_post_create_duplicate($post, $status = '', $parent_id = '') {

	if (!duplicate_post_is_post_type_enabled($post->post_type) && $post->post_type != 'attachment')
		wp_die(__('Copy features for this post type are not enabled in options page', 'duplicate-post'));
		
	if ($post->post_type != 'attachment'){
		$prefix = sanitize_text_field(get_option('duplicate_post_title_prefix'));
		$suffix = sanitize_text_field(get_option('duplicate_post_title_suffix'));
		$title = ' ';
		if (get_option('duplicate_post_copytitle') == 1) {
			$title = $post->post_title;
			if (!empty($prefix)) $prefix.= " ";
			if (!empty($suffix)) $suffix = " ".$suffix;
			} else {
			$title = ' ';
		}
		$title = trim($prefix.$title.$suffix);

		if ($title == ''){
			// empty title
			$title = __('Untitled');
		}
			

		if (get_option('duplicate_post_copystatus') == 0) $status = 'draft';
	}
	$new_post_author = wp_get_current_user();
	
	$menu_order = $post->menu_order;
	$increase_menu_order_by = get_option('duplicate_post_increase_menu_order_by');
	if(!empty($increase_menu_order_by) && is_numeric($increase_menu_order_by)){
		$menu_order += intval($increase_menu_order_by);
	}

	$new_post = array(
	'menu_order' => $menu_order,
	'comment_status' => $post->comment_status,
	'ping_status' => $post->ping_status,
	'post_author' => $new_post_author->ID,
	'post_content' => (get_option('duplicate_post_copycontent') == '1') ? addslashes($post->post_content) : "" ,
	'post_content_filtered' => (get_option('duplicate_post_copycontent') == '1') ? addslashes($post->post_content_filtered) : "" ,			
	'post_excerpt' => (get_option('duplicate_post_copyexcerpt') == '1') ? addslashes($post->post_excerpt) : "",
	'post_mime_type' => $post->post_mime_type,
	'post_parent' => $new_post_parent = empty($parent_id)? $post->post_parent : $parent_id,
	'post_password' => (get_option('duplicate_post_copypassword') == '1') ? $post->post_password: "",
	'post_status' => $new_post_status = (empty($status))? $post->post_status: $status,
	'post_title' => addslashes($title),
	'post_type' => $post->post_type,
	);

	if(get_option('duplicate_post_copydate') == 1){
		$new_post['post_date'] = $new_post_date =  $post->post_date ;
		$new_post['post_date_gmt'] = get_gmt_from_date($new_post_date);
	}

	$new_post_id = wp_insert_post($new_post);

	// If the copy is published or scheduled, we have to set a proper slug.
	if ($new_post_status == 'publish' || $new_post_status == 'future'){
		$post_name = $post->post_name;
		if(get_option('duplicate_post_copyslug') != 1){
			$post_name = '';
		}
		$post_name = wp_unique_post_slug($post_name, $new_post_id, $new_post_status, $post->post_type, $new_post_parent);

		$new_post = array();
		$new_post['ID'] = $new_post_id;
		$new_post['post_name'] = $post_name;

		// Update the post into the database
		wp_update_post( $new_post );
	}

	// If you have written a plugin which uses non-WP database tables to save
	// information about a post you can hook this action to dupe that data.
	if ($post->post_type == 'page' || (function_exists('is_post_type_hierarchical') && is_post_type_hierarchical( $post->post_type )))
		do_action( 'dp_duplicate_page', $new_post_id, $post );
	else
		do_action( 'dp_duplicate_post', $new_post_id, $post );

	delete_post_meta($new_post_id, '_dp_original');
	add_post_meta($new_post_id, '_dp_original', $post->ID);

	return $new_post_id;
}

//Add some links on the plugin page
add_filter('plugin_row_meta', 'duplicate_post_add_plugin_links', 10, 2);

function duplicate_post_add_plugin_links($links, $file) {
	if ( $file == plugin_basename(dirname(__FILE__).'/duplicate-post.php') ) {
		$links[] = '<a href="http://lopo.it/duplicate-post-plugin">' . __('Donate', 'duplicate-post') . '</a>';
		$links[] = '<a href="https://translate.wordpress.org/projects/wp-plugins/duplicate-post">' . __('Translate', 'duplicate-post') . '</a>';
	}
	return $links;
}

add_action( 'admin_notices', 'duplicate_post_action_admin_notice' );
 
function duplicate_post_action_admin_notice() {
  if ( ! empty( $_REQUEST['cloned'] ) ) {
    $copied_posts = intval( $_REQUEST['cloned'] );
    printf( '<div id="message" class="updated fade"><p>' .
      _n( '%s item copied.',
        '%s items copied.',
        $copied_posts,
        'duplicate-post'
      ) . '</p></div>', $copied_posts );
    remove_query_arg( 'cloned' );
  }
}
