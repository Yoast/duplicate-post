<?php
/*
Plugin Name: Duplicate Post
Plugin URI: http://wordpress.org/extend/plugins/duplicate-post/
Description: Creates a copy of a post.
Version: 0.6.1
Author: Enrico Battocchi
Author URI: http://www.lopo.it
Text Domain: duplicate-post
*/

/*  Copyright 2008	Enrico Battocchi  (email : enrico.battocchi@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Added by WarmStal
if(!is_admin())
return;

/*
 * This function calls the creation of a new copy of the selected post (as a draft)
 * then redirects to the edit post screen
 */
function duplicate_post_save_as_new_post(){
	if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'duplicate_post_save_as_new_post' == $_REQUEST['action'] ) ) ) {
	   wp_die(_e('No post to duplicate has been supplied!', DUPLICATE_POST_I18N_DOMAIN));
	}
	
	// Get the original post
	$id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);	
	$post = duplicate_post_get_post($id);
	
	// Copy the post and insert it
	if (isset($post) && $post!=null) {
		$new_id = duplicate_post_create_duplicate_from_post($post);
	
		// Show the post edit
		echo '<meta content="0; URL=post.php?action=edit&post=' . $new_id . '" http-equiv="Refresh" />';
		exit;
	} else {
 		wp_die(_e('Post creation failed, could not find original post:', DUPLICATE_POST_I18N_DOMAIN) . ' ' . $id);
		}
}

/*
 * Same as above, for pages
 */
function duplicate_post_save_as_new_page(){
	if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'duplicate_post_save_as_new_post' == $_REQUEST['action'] ) ) ) {
	   wp_die(_e('No page to duplicate has been supplied!', DUPLICATE_POST_I18N_DOMAIN));
	}
	
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
 		wp_die(_e('Post creation failed, could not find original post:', DUPLICATE_POST_I18N_DOMAIN) . ' ' . $id);
		}
}

// Version of the plugin
define('DUPLICATE_POST_CURRENT_VERSION', '1.0' );
define('DUPLICATE_POST_COLUMN', 'control_duplicate_post');
define('DUPLICATE_POST_VIEW_USER_LEVEL_OPTION', 'duplicate_post_view_user_level');
define('DUPLICATE_POST_CREATE_USER_LEVEL_OPTION', 'duplicate_post_create_user_level');
define('DUPLICATE_POST_ADMIN_USER_LEVEL_OPTION', 'duplicate_post_admin_user_level');

// i18n plugin domain 
define('DUPLICATE_POST_I18N_DOMAIN', 'duplicate-post');

/**
 * Initialise the internationalisation domain
 */
load_plugin_textdomain(DUPLICATE_POST_I18N_DOMAIN, 
			'wp-content/plugins/duplicate-post/languages','duplicate-post/languages');

/**
 * Plugin activation
 */
add_action('activate_duplicate-post/duplicate-post.php','duplicate_post_plugin_activation');

function duplicate_post_plugin_activation() {
	$installed_version = duplicate_post_get_installed_version();
	
	if ( $installed_version==duplicate_post_get_current_version() ) {
		// do nothing
	} else if ( $installed_version=='' ) {
		// Add all options, nothing already installed
		add_option(
			DUPLICATE_POST_VIEW_USER_LEVEL_OPTION,
			'2',
			'Default user level to copy posts' );
		add_option(
			DUPLICATE_POST_CREATE_USER_LEVEL_OPTION,
			'5',
			'Default user level to create the templates' );
		add_option(
			DUPLICATE_POST_ADMIN_USER_LEVEL_OPTION,
			'8',
			'Default user level to change the plugin options' );
	}
	// Update version number
	update_option( 'duplicate_post_version', duplicate_post_get_current_version() );	
}

/**
 * Check if WP version < 2.8: if so, post_row_actions does not exist, so we must add a custom column (the old way)
 */
global $wp_version;
if (strncmp($wp_version, "2.7",3) == 0 ){
	add_filter('manage_posts_columns', 'duplicate_post_add_duplicate_post_column');
	// Added by WarmStal
	add_filter('manage_pages_columns', 'duplicate_post_add_duplicate_post_column');
	add_action('manage_posts_custom_column', 'duplicate_post_make_duplicate_link', 10, 2);
	// Added by WarmStal
	add_action('manage_pages_custom_column', 'duplicate_page_make_duplicate_link', 10, 2);
} else {
	/**
	* Add to the links shown when the mouse gets over a post title in 'Edit Posts' or 'Edit Pages' screen 
	*/
	add_filter('post_row_actions', 'duplicate_post_make_duplicate_link_row',10,2);
	add_filter('page_row_actions', 'duplicate_page_make_duplicate_link_row',10,2);
}

/**
 * WP version < 2.8: add a custom column
 */
function duplicate_post_add_duplicate_post_column($columns) {
	if (duplicate_post_is_current_user_allowed_to_create()) {
		$columns[DUPLICATE_POST_COLUMN] = '';
	}
	return $columns;
}

/**
 * WP version < 2.8: add link to custom column for posts
 */
function duplicate_post_make_duplicate_link($column_name, $id) {
	if (duplicate_post_is_current_user_allowed_to_create()) {
		if ($column_name == DUPLICATE_POST_COLUMN) {
			echo "<a href='admin.php?action=duplicate_post_save_as_new_post&amp;post=" . $id 
				. "' title='" . __("Make a duplicate from this post", DUPLICATE_POST_I18N_DOMAIN) 
				. "' class='edit'>" . __("Duplicate", DUPLICATE_POST_I18N_DOMAIN) . "</a>";
		}
	}
}

/**
 * WP version < 2.8: add link to custom column for pages
 */
// Added by WarmStal
function duplicate_page_make_duplicate_link($column_name, $id) {
	if (duplicate_post_is_current_user_allowed_to_create()) {
		if ($column_name == DUPLICATE_POST_COLUMN) {
			echo "<a href='admin.php?action=duplicate_post_save_as_new_page&amp;post=" . $id
				. "' title='" . __("Make a duplicate from this page", DUPLICATE_POST_I18N_DOMAIN)
				. "' class='edit'>" . __("Duplicate", DUPLICATE_POST_I18N_DOMAIN) . "</a>";
		}
	}
}

/**
 * Connect actions to functions
 */
add_action('admin_action_duplicate_post_save_as_new_post', 'duplicate_post_save_as_new_post');
add_action('admin_action_duplicate_post_save_as_new_page', 'duplicate_post_save_as_new_page');

/**
 * Add the link to action list for post_row_actions
 */
function duplicate_post_make_duplicate_link_row($actions, $post) {
	if (duplicate_post_is_current_user_allowed_to_create()) {
			$actions['duplicate'] = '<a href="admin.php?action=duplicate_post_save_as_new_post&amp;post=' . $post->ID . '" title="' . __("Make a duplicate from this post", DUPLICATE_POST_I18N_DOMAIN) 
				. '" rel="permalink">' .  __("Duplicate", DUPLICATE_POST_I18N_DOMAIN) . '</a>';
	}
	return $actions;
}

/**
 * Add the link to action list for page_row_actions
 */
function duplicate_page_make_duplicate_link_row($actions, $page) {
	if (duplicate_post_is_current_user_allowed_to_create()) {
			$actions['duplicate'] = '<a href="admin.php?action=duplicate_post_save_as_new_page&amp;post=' . $page->ID . '" title="' . __("Make a duplicate from this page", DUPLICATE_POST_I18N_DOMAIN) 
				. '" rel="permalink">' .  __("Duplicate", DUPLICATE_POST_I18N_DOMAIN) . '</a>';
	}
	return $actions;
}

/**
 * Add a button in the post/page edit screen to create a clone
*/
add_action( 'post_submitbox_start', 'duplicate_post_add_duplicate_post_button' );

function duplicate_post_add_duplicate_post_button() {
	if ( isset( $_GET['post'] ) && duplicate_post_is_current_user_allowed_to_create()) {
		$act = "admin.php?action=duplicate_post_save_as_new_post";
		global $post;
		if ($post->post_type == "page") $act = "admin.php?action=duplicate_post_save_as_new_page";
		$notifyUrl = $act."&post=" . $_GET['post'];
?>
		<div id="duplicate-action">
				<a class="submitduplicate duplication" href="<?php echo $notifyUrl; ?>"><?php _e('Copy to a new draft', DUPLICATE_POST_I18N_DOMAIN); ?></a>
		</div>
<?php
	}
}

/**
 * Wrapper for the option 'duplicate_post_view_user_level'
 */
function duplicate_post_get_view_user_level() {
	return get_option( DUPLICATE_POST_VIEW_USER_LEVEL_OPTION );
}

/**
 * Wrapper for the option 'duplicate_post_view_user_level'
 */
function duplicate_post_set_view_user_level($new_level) {
	return update_option( DUPLICATE_POST_VIEW_USER_LEVEL_OPTION, $new_level );
}

/**
 * Wrapper for the option 'duplicate_post_create_user_level'
 */
function duplicate_post_get_create_user_level() {
	return get_option( DUPLICATE_POST_CREATE_USER_LEVEL_OPTION );
}

/**
 * Wrapper for the option 'duplicate_post_create_user_level'
 */
function duplicate_post_set_create_user_level($new_level) {
	return update_option( DUPLICATE_POST_CREATE_USER_LEVEL_OPTION, $new_level );
}

/**
 * Wrapper for the option 'duplicate_post_admin_user_level'
 */
function duplicate_post_get_admin_user_level() {
	return get_option( DUPLICATE_POST_ADMIN_USER_LEVEL_OPTION );
}

/**
 * Wrapper for the option 'duplicate_post_admin_user_level'
 */
function duplicate_post_set_admin_user_level($new_level) {
	return update_option( DUPLICATE_POST_ADMIN_USER_LEVEL_OPTION, $new_level );
}

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
 * Test if the user is allowed to view the templates & create posts
 */
function duplicate_post_is_current_user_allowed_to_view() {
	return current_user_can("level_" . duplicate_post_get_view_user_level());
}

/**
 * Test if the user is allowed to create templates
 */
function duplicate_post_is_current_user_allowed_to_create() {
	return current_user_can("level_" . duplicate_post_get_create_user_level());
}

/**
 * Test if the user is allowed to administrate the plugin
 */
function duplicate_post_is_current_user_allowed_to_admin() {
	return current_user_can("level_" . duplicate_post_get_admin_user_level());
}

/**
 * Get a level given a role
 */ 
function duplicate_post_get_level_from_role($role) {
	switch ($role) {
	case 0:		// subscribers		0
		return 0;
	case 1:		// contributors		1
		return 1;
	case 2:		// authors			2..4
		return 2;
	case 3:		// editors			5..7
		return 5;
	case 4:		// administrators		8..10
		return 8;		
	default:	// error
		return 0;
	}
}

/**
 * Get a role given a level
 */ 
function duplicate_post_get_role_from_level($level) {
	if ($level<=0) {
		// subscribers		0
		return 0;
	} else if ($level==1) {
		// contributors		1
		return 1;
	} else if ($level>=2 && $level<=4) {
		// authors			2..4
		return 2;
	} else if ($level>=5 && $level<=7) {
		// editors			5..7
		return 3;
	} else if ($level>=8) {
		// admins			8..10
		return 4;
	}	
	return 0;
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
 * Escape single quotes, specialchar double quotes, and fix line endings.
 */
function duplicate_post_js_escape($text) {
	if (function_exists('js_escape')) {
		return js_escape($text);
	} else {
		$safe_text = str_replace('&&', '&#038;&', $text);
		$safe_text = str_replace('&&', '&#038;&', $safe_text);
		$safe_text = preg_replace('/&(?:$|([^#])(?![a-z1-4]{1,8};))/', '&#038;$1', $safe_text);
		$safe_text = str_replace('<', '&lt;', $safe_text);
		$safe_text = str_replace('>', '&gt;', $safe_text);
		$safe_text = str_replace('"', '&quot;', $safe_text);
		$safe_text = str_replace('&#039;', "'", $safe_text);
		$safe_text = preg_replace("/\r?\n/", "\\n", addslashes($safe_text));
		return safe_text;
	}
}

/**
 * Get a page from the database
 */
function duplicate_post_get_page($id) {
	global $wpdb;
	$post = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID=$id");
	if ($post->post_type == "revision"){
		$id = $post->post_parent;
		$post = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID=$id");
		}
	return $post[0];
}

/**
 * Get a post from the database
 */
function duplicate_post_get_post($id) {
	global $wpdb;
	$post = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID=$id");
	if ($post->post_type == "revision"){
		$id = $post->post_parent;
		$post = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID=$id");
		}
	return $post[0];
}

/**
 * Copy the categories of a post to another post
 */
function duplicate_post_copy_post_categories($id, $new_id) {
	global $wpdb;
	if (isset($wpdb->terms)) {
		// WordPress 2.3
		$taxonomies = get_object_taxonomies('post'); //array("category", "post_tag");
		foreach ($taxonomies as $taxonomy) {
			$post_terms = wp_get_object_terms($id, $taxonomy);
			for ($i=0; $i<count($post_terms); $i++) {
				wp_set_object_terms($new_id, $post_terms[$i]->slug, $taxonomy, true);
			}
		}
	} else {
		$post_categories = $wpdb->get_results("SELECT category_id FROM $wpdb->post2cat WHERE post_id=$id");
		if (count($post_categories)!=0) {
			$sql_query = "INSERT INTO $wpdb->post2cat (post_id, category_id) ";
			
			for ($i=0; $i<count($post_categories); $i++) {
				$post_category = $post_categories[$i]->category_id;
				
				if ($i<count($post_categories)-1) {
					$sql_query .= "SELECT $new_id, $post_category UNION ALL ";
				} else {
					$sql_query .= "SELECT $new_id, $post_category";
				}
			}
		
			$wpdb->query($sql_query);	
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
		
		for ($i=0; $i<count($post_meta_infos); $i++) {
			$meta_info = $post_meta_infos[$i];
			
			$meta_value = addslashes($meta_info->meta_value);

			if ($i<count($post_meta_infos)-1) {
				$sql_query .= "SELECT $new_id, '$meta_info->meta_key', '$meta_value' UNION ALL ";
			} else {
				$sql_query .= "SELECT $new_id, '$meta_info->meta_key', '$meta_value'";
			}
		}
	
		$wpdb->query($sql_query);	
	} 
}

/**
 * Create a duplicate from a post
 */
function duplicate_post_create_duplicate_from_post($post) {
	global $wpdb;
	$new_post_type = 'post';
	$new_post_author = duplicate_post_get_current_user();
	$new_post_date = current_time('mysql');
	$new_post_date_gmt = get_gmt_from_date($new_post_date);
		
	$post_content    = str_replace("'", "''", $post->post_content);
	$post_content_filtered = str_replace("'", "''", $post->post_content_filtered);
	$post_excerpt    = str_replace("'", "''", $post->post_excerpt);
	$post_title      = str_replace("'", "''", $post->post_title);
	$post_status     = str_replace("'", "''", $post->post_status);
	$comment_status  = str_replace("'", "''", $post->comment_status);
	$ping_status     = str_replace("'", "''", $post->ping_status);
	
	// Insert the new template in the post table
	$wpdb->query(
			"INSERT INTO $wpdb->posts
			(post_author, post_date, post_date_gmt, post_content, post_content_filtered, post_title, post_excerpt,  post_status, post_type, comment_status, ping_status, post_password, to_ping, pinged, post_modified, post_modified_gmt, post_parent, menu_order, post_mime_type)
			VALUES
			('$new_post_author->ID', '$new_post_date', '$new_post_date_gmt', '$post_content', '$post_content_filtered', '$post_title', '$post_excerpt', 'draft', '$new_post_type', '$comment_status', '$ping_status', '$post->post_password', '$post->to_ping', '$post->pinged', '$new_post_date', '$new_post_date_gmt', '$post->post_parent', '$post->menu_order', '$post->post_mime_type')");
			
	$new_post_id = $wpdb->insert_id;
		
	// Copy the categories
	duplicate_post_copy_post_categories($post->ID, $new_post_id);
	
	// Copy the meta information
	duplicate_post_copy_post_meta_info($post->ID, $new_post_id);
	
	return $new_post_id;
}

/**
 * Create a duplicate from a page
 */
function duplicate_post_create_duplicate_from_page($post) {
	global $wpdb;
	$new_post_type = 'page';
	$new_post_date = current_time('mysql');
	$new_post_date_gmt = get_gmt_from_date($new_post_date);
	
	$post_content    = str_replace("'", "''", $post->post_content);
	$post_content_filtered = str_replace("'", "''", $post->post_content_filtered);
	$post_excerpt    = str_replace("'", "''", $post->post_excerpt);
	$post_title      = str_replace("'", "''", $post->post_title);
	$post_status     = str_replace("'", "''", $post->post_status);
	$post_name       = str_replace("'", "''", $post->post_name);
	$comment_status  = str_replace("'", "''", $post->comment_status);
	$ping_status     = str_replace("'", "''", $post->ping_status);
	
	// Insert the new template in the post table
	$wpdb->query(
			"INSERT INTO $wpdb->posts
			(post_author, post_date, post_date_gmt, post_content, post_content_filtered, post_title, post_excerpt,  post_status, post_type, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_parent, menu_order, post_mime_type)
			VALUES
			('$post->post_author', '$new_post_date', '$new_post_date_gmt', '$post_content', '$post_content_filtered', '$post_title', '$post_excerpt', 'draft', '$new_post_type', '$comment_status', '$ping_status', '$post->post_password', '$post_name', '$post->to_ping', '$post->pinged', '$new_post_date', '$new_post_date_gmt', '$post->post_parent', '$post->menu_order', '$post->post_mime_type')");
			
	$new_page_id = $wpdb->insert_id;
	
	// Copy the meta information
	duplicate_post_copy_post_meta_info($post->ID, $new_page_id);
	
	return $new_page_id;
}
?>