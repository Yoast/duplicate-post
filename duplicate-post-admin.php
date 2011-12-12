<?php
// Added by WarmStal
if(!is_admin())
return;

require_once (dirname(__FILE__).'/duplicate-post-options.php');

// Version of the plugin
define('DUPLICATE_POST_CURRENT_VERSION', '2.0.2' );

/**
 * Wrapper for the option 'duplicate_post_version'
 */
function duplicate_post_get_installed_version() {
	return get_option( 'duplicate_post_version' );
}

/**
 * Wrapper for the defined constant DUPLICATE_POST_CURRENT_VERSION
 */
function duplicate_post_get_current_version() {
	return DUPLICATE_POST_CURRENT_VERSION;
}

/**
 * Plugin activation
 */
add_action('activate_duplicate-post/duplicate-post.php','duplicate_post_plugin_activation');

function duplicate_post_plugin_activation() {
	$installed_version = duplicate_post_get_installed_version();

	if ( $installed_version==duplicate_post_get_current_version() ) {
		// do nothing
	} else {
		// delete old, obsolete options
		delete_option('duplicate_post_admin_user_level');
		delete_option('duplicate_post_create_user_level');
		delete_option('duplicate_post_view_user_level');
		// Add all options, nothing already installed
		add_option(
		'duplicate_post_copy_user_level',
			'5',
			'Default user level to copy posts' );
		add_option(
		'duplicate_post_copyexcerpt',
			'1',
			'Copy the excerpt from the original post/page' );
		add_option(
		'duplicate_post_copystatus',
			'0',
			'Copy the status (draft, published, pending) from the original post/page' );
		add_option(
		'duplicate_post_taxonomies_blacklist',
		array(),
			'List of the taxonomies that mustn\'t be copied' );
	}
	// Update version number
	update_option( 'duplicate_post_version', duplicate_post_get_current_version() );
	
	// enable notice
	update_option('dp_notice', 1);
}


function dp_admin_notice(){
    echo '<div class="updated">
       <p>'.sprintf(__('<strong>Duplicate Post</strong> now has two different ways to work: you can clone immediately or you can copy to a new draft to edit.<br/>
       Learn more on the <a href="%s">plugin page</a>.', DUPLICATE_POST_I18N_DOMAIN), "http://wordpress.org/extend/plugins/duplicate-post/").'</p>
    </div>';
	update_option('dp_notice', 0);
}

if(get_option('dp_notice') != 0) add_action('admin_notices', 'dp_admin_notice');

add_filter('post_row_actions', 'duplicate_post_make_duplicate_link_row',10,2);
add_filter('page_row_actions', 'duplicate_post_make_duplicate_link_row',10,2);

/**
 * Add the link to action list for post_row_actions
 */
function duplicate_post_make_duplicate_link_row($actions, $post) {
	if (duplicate_post_is_current_user_allowed_to_copy()) {
		$theUrl = admin_url('admin.php?action=duplicate_post_save_as_new_post&amp;post=' . $post->ID);
		$theUrlDraft = admin_url('admin.php?action=duplicate_post_save_as_new_post_draft&amp;post=' . $post->ID);
		$post_type_obj = get_post_type_object( $post->post_type );
		$actions['duplicate'] = '<a href="'.$theUrl.'" title="'
		. esc_attr(__("Clone this item", DUPLICATE_POST_I18N_DOMAIN))
		. '" rel="permalink">' .  __('Clone', DUPLICATE_POST_I18N_DOMAIN) . '</a>';
		$actions['edit_as_new_draft'] = '<a href="'.$theUrlDraft.'" title="'
		. esc_attr(__('Copy to a new draft', DUPLICATE_POST_I18N_DOMAIN))
		. '" rel="permalink">' .  __('New Draft', DUPLICATE_POST_I18N_DOMAIN) . '</a>';
	}
	return $actions;
}

/**
 * Add a button in the post/page edit screen to create a clone
 */
add_action( 'post_submitbox_start', 'duplicate_post_add_duplicate_post_button' );

function duplicate_post_add_duplicate_post_button() {
	if ( isset( $_GET['post'] ) && duplicate_post_is_current_user_allowed_to_copy()) {
		$act = "admin.php?action=duplicate_post_save_as_new_post_draft";
		global $post;
		$notifyUrl = $act."&post=" . $_GET['post'];
		?>
<div id="duplicate-action">
	<a class="submitduplicate duplication"
		href="<?php echo admin_url($notifyUrl); ?>"><?php _e('Copy to a new draft', DUPLICATE_POST_I18N_DOMAIN); ?>
	</a>
</div>
		<?php
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

/*
 * This function calls the creation of a new copy of the selected post (preserving the original publish status)
 * then redirects to the post list
 */
function duplicate_post_save_as_new_post($status = ''){
	if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'duplicate_post_save_as_new_post' == $_REQUEST['action'] ) ) ) {
		wp_die(__('No post to duplicate has been supplied!', DUPLICATE_POST_I18N_DOMAIN));
	}

	// Get the original post
	$id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
	$post = duplicate_post_get_post($id);

	// Copy the post and insert it
	if (isset($post) && $post!=null) {
		$new_id = duplicate_post_create_duplicate($post, $status);

		if ($status == ''){
			// Redirect to the post list screen
			wp_redirect( admin_url( 'edit.php?post_type='.$post->post_type) );
		} else {
			// Redirect to the edit screen for the new draft post
			wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
		}
		exit;

	} else {
		$post_type_obj = get_post_type_object( $post->post_type );
		wp_die(esc_attr(__('Copy creation failed, could not find original:', DUPLICATE_POST_I18N_DOMAIN)) . ' ' . $id);
	}
}

/**
 * Get the currently registered user
 */
function duplicate_post_get_current_user() {
	if (function_exists('wp_get_current_user')) {
		return wp_get_current_user();
	} else if (function_exists('get_currentuserinfo')) {
		global $userdata;
		get_currentuserinfo();
		return $userdata;
	} else {
		$user_login = $_COOKIE[USER_COOKIE];
		$current_user = $wpdb->get_results("SELECT * FROM $wpdb->users WHERE user_login='$user_login'");
		return $current_user;
	}
}

/**
 * Get a post from the database
 */
function duplicate_post_get_post($id) {
	global $wpdb;
	$post = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID=$id");
	if ($post[0]->post_type == "revision"){
		$id = $post[0]->post_parent;
		$post = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID=$id");
	}
	return $post[0];
}

/**
 * Copy the taxonomies of a post to another post
 */
function duplicate_post_copy_post_taxonomies($id, $new_id, $post_type) {
	global $wpdb;
	if (isset($wpdb->terms)) {
		$taxonomies = get_object_taxonomies($post_type); //array("category", "post_tag");
		$taxonomies_blacklist = get_option('duplicate_post_taxonomies_blacklist');
		if ($taxonomies_blacklist == "") $taxonomies_blacklist = array();
		foreach ($taxonomies as $taxonomy) {
			if(!empty($taxonomies_blacklist) && in_array($taxonomy,$taxonomies_blacklist)) continue;
			$post_terms = wp_get_object_terms($id, $taxonomy);
			for ($i=0; $i<count($post_terms); $i++) {
				wp_set_object_terms($new_id, $post_terms[$i]->slug, $taxonomy, true);
			}
		}
	}
}

/**
 * Copy the meta information of a post to another post
 */
function duplicate_post_copy_post_meta_info($id, $new_id) {
	global $wpdb;
	$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$id");

	if (count($post_meta_infos)!=0) {
		$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
		$meta_no_copy = explode(",",get_option('duplicate_post_blacklist'));
		foreach ($post_meta_infos as $meta_info) {
			$meta_key = $meta_info->meta_key;
			$meta_value = addslashes($meta_info->meta_value);
			if (!in_array($meta_key,$meta_no_copy)) {
				$sql_query_sel[]= "SELECT $new_id, '$meta_key', '$meta_value'";
			}
		}
		$sql_query.= implode(" UNION ALL ", $sql_query_sel);
		$wpdb->query($sql_query);
	}
}

/**
 * Create a duplicate from a post
 */
function duplicate_post_create_duplicate($post, $status = '') {
	global $wpdb;
	//$new_post_type = 'post';
	$new_post_author = duplicate_post_get_current_user();
	$new_post_date = (get_option('duplicate_post_copydate') == 1)?  $post->post_date : current_time('mysql');
	$new_post_date_gmt = get_gmt_from_date($new_post_date);
	$prefix = get_option('duplicate_post_title_prefix');
	$suffix = get_option('duplicate_post_title_suffix');
	if (!empty($prefix)) $prefix.= " ";
	if (!empty($prefix)) $suffix = " ".$suffix;

	$new_post_type 	= $post->post_type;
	$post_content    = str_replace("'", "''", $post->post_content);
	$post_content_filtered = str_replace("'", "''", $post->post_content_filtered);
	if (get_option('duplicate_post_copyexcerpt') == '1')
	$post_excerpt = str_replace("'", "''", $post->post_excerpt);
	else
	$post_excerpt = "";
	$post_title      = $prefix.str_replace("'", "''", $post->post_title).$suffix;
	if (get_option('duplicate_post_copystatus') == 0) $status = 'draft';
	if (empty($status))
	$new_post_status  = str_replace("'", "''", $post->post_status);
	else
	$new_post_status  = $status;
	$post_name       = sanitize_title($post_title);
	$comment_status  = str_replace("'", "''", $post->comment_status);
	$ping_status     = str_replace("'", "''", $post->ping_status);

	// Insert the new template in the post table
	$wpdb->query(
			"INSERT INTO $wpdb->posts
			(post_author, post_date, post_date_gmt, post_content, post_content_filtered, post_title, post_excerpt,  post_status, post_type, comment_status, ping_status, post_password, to_ping, pinged, post_modified, post_modified_gmt, post_parent, menu_order, post_mime_type)
			VALUES
			('$new_post_author->ID', '$new_post_date', '$new_post_date_gmt', '$post_content', '$post_content_filtered', '$post_title', '$post_excerpt', '$new_post_status', '$new_post_type', '$comment_status', '$ping_status', '$post->post_password', '$post->to_ping', '$post->pinged', '$new_post_date', '$new_post_date_gmt', '$post->post_parent', '$post->menu_order', '$post->post_mime_type')");

	$new_post_id = $wpdb->insert_id;

	// Copy the taxonomies
	duplicate_post_copy_post_taxonomies($post->ID, $new_post_id, $post->post_type);

	// Copy the meta information
	duplicate_post_copy_post_meta_info($post->ID, $new_post_id);

	add_post_meta($new_post_id, '_dp_original', $post->ID);

	// If you have written a plugin which uses non-WP database tables to save
	// information about a post you can hook this action to dupe that data.
	if ($post->post_type == 'page' || (function_exists('is_post_type_hierarchical') && is_post_type_hierarchical( $post->post_type )))
	do_action( 'dp_duplicate_page', $new_post_id, $post );
	else
	do_action( 'dp_duplicate_post', $new_post_id, $post );

	// If the copy gets immediately published, we have to set a proper slug.
	if ($new_post_status == 'publish'){
		$post_name = wp_unique_post_slug($post_name, $new_post_id, $new_post_status, $new_post_type, $post->post_parent);

		$new_post = array();
		$new_post['ID'] = $new_post_id;
		$new_post['post_name'] = $post_name;

		// Update the post into the database
		wp_update_post( $new_post );
	}

	return $new_post_id;
}

//Add some links on the plugin page
add_filter('plugin_row_meta', 'duplicate_post_add_plugin_links', 10, 2);

function duplicate_post_add_plugin_links($links, $file) {
	if ( $file == plugin_basename(dirname(__FILE__).'/duplicate-post.php') ) {
		$links[] = '<a href="http://lopo.it/duplicate-post-plugin">' . __('Donate', DUPLICATE_POST_I18N_DOMAIN) . '</a>';
		$links[] = '<a href="http://lopo.it/duplicate-post-plugin">' . __('Translate', DUPLICATE_POST_I18N_DOMAIN) . '</a>';
	}
	return $links;
}
?>