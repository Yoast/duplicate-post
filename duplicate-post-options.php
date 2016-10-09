<?php
/**
 * Add an option page
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_admin() ){ // admin actions
	add_action('admin_menu', 'duplicate_post_menu');
	add_action( 'admin_init', 'duplicate_post_register_settings');
}

function duplicate_post_register_settings() { // whitelist options
	register_setting( 'duplicate_post_group', 'duplicate_post_copytitle');
	register_setting( 'duplicate_post_group', 'duplicate_post_copydate');
	register_setting( 'duplicate_post_group', 'duplicate_post_copystatus');
	register_setting( 'duplicate_post_group', 'duplicate_post_copyslug');
	register_setting( 'duplicate_post_group', 'duplicate_post_copyexcerpt');
	register_setting( 'duplicate_post_group', 'duplicate_post_copycontent');
	register_setting( 'duplicate_post_group', 'duplicate_post_copypassword');
	register_setting( 'duplicate_post_group', 'duplicate_post_copyattachments');
	register_setting( 'duplicate_post_group', 'duplicate_post_copychildren');
	register_setting( 'duplicate_post_group', 'duplicate_post_copycomments');
	register_setting( 'duplicate_post_group', 'duplicate_post_blacklist');
	register_setting( 'duplicate_post_group', 'duplicate_post_taxonomies_blacklist');
	register_setting( 'duplicate_post_group', 'duplicate_post_title_prefix');
	register_setting( 'duplicate_post_group', 'duplicate_post_title_suffix');
	register_setting( 'duplicate_post_group', 'duplicate_post_increase_menu_order_by');
	register_setting( 'duplicate_post_group', 'duplicate_post_roles');
	register_setting( 'duplicate_post_group', 'duplicate_post_types_enabled');
	register_setting( 'duplicate_post_group', 'duplicate_post_show_row');
	register_setting( 'duplicate_post_group', 'duplicate_post_show_adminbar');
	register_setting( 'duplicate_post_group', 'duplicate_post_show_submitbox');
}


function duplicate_post_menu() {
	add_options_page(__("Duplicate Post Options", 'duplicate-post'), __("Duplicate Post", 'duplicate-post'), 'manage_options', 'duplicatepost', 'duplicate_post_options');
}

function duplicate_post_options() {

	if ( current_user_can( 'promote_users' ) && (isset($_GET['settings-updated'])  && $_GET['settings-updated'] == true)){
		global $wp_roles;
		$roles = $wp_roles->get_names();

		$dp_roles = get_option('duplicate_post_roles');
		if ( $dp_roles == "" ) $dp_roles = array();

		foreach ($roles as $name => $display_name){
			$role = get_role($name);

			// role should have at least edit_posts capability
			if ( !$role->has_cap('edit_posts') ) continue;

			/* If the role doesn't have the capability and it was selected, add it. */
			if ( !$role->has_cap( 'copy_posts' )  && in_array($name, $dp_roles) )
				$role->add_cap( 'copy_posts' );

			/* If the role has the capability and it wasn't selected, remove it. */
			elseif ( $role->has_cap( 'copy_posts' ) && !in_array($name, $dp_roles) )
			$role->remove_cap( 'copy_posts' );
		}
	}
	?>
<div class="wrap">
	<div id="icon-options-general" class="icon32">
		<br>
	</div>
	<h1>
		<?php _e("Duplicate Post Options", 'duplicate-post'); ?>
	</h1>

	<div
		style="margin: 9px 15px 4px 0; padding: 5px; text-align: center; font-weight: bold; float: left;">
		<a href="http://lopo.it/duplicate-post-plugin"><?php _e('Visit plugin site'); ?>
		</a> - <a
			href="https://translate.wordpress.org/projects/wp-plugins/duplicate-post"><?php _e('Translate', 'duplicate-post'); ?>
		</a> - <a href="https://wordpress.org/plugins/duplicate-post/faq/"><?php _e('FAQ', 'duplicate-post'); ?>
		</a> - <a href="http://lopo.it/duplicate-post-plugin"><?php _e('Donate', 'duplicate-post'); ?>
			(10¢) </a>
		<form style="display: inline-block; vertical-align: middle;"
			action="https://www.paypal.com/cgi-bin/webscr" method="post"
			target="_top">
			<input type="hidden" name="cmd" value="_s-xclick"> <input
				type="hidden" name="encrypted"
				value="-----BEGIN PKCS7-----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-----END PKCS7-----
"><input type="image"
				src="https://www.paypalobjects.com/en_GB/i/btn/btn_donate_SM.gif"
				border="0" name="submit"
				alt="PayPal – The safer, easier way to pay online."> <img alt=""
				border="0" src="https://www.paypalobjects.com/it_IT/i/scr/pixel.gif"
				width="1" height="1">
		</form>


	</div>

	<script>
	jQuery(document).on( 'click', '.nav-tab-wrapper a', function() {
		jQuery('.nav-tab').removeClass('nav-tab-active');
		jQuery(this).addClass('nav-tab-active');
		jQuery('section').hide();
		jQuery('section').eq(jQuery(this).index()).show();	
		return false;
	})
	
	function toggle_private_taxonomies(){
		jQuery('.taxonomy_private').toggle();
	}
	jQuery(function(){
		jQuery('.taxonomy_private').hide();
	});
	
	</script>

	<style>
h2.nav-tab-wrapper {
	margin: 22px 0 0 0;
}

h2 .nav-tab:focus {
	color: #555;
	box-shadow: none;
}

#sections {
	padding: 22px;
	background: #fff;
	border: 1px solid #ccc;
	border-top: 0px;
}

section {
	display: none;
}

section:first-of-type {
	display: block;
}

.no-js h2.nav-tab-wrapper {
	display: none;
}

.no-js #sections {
	border-top: 1px solid #ccc;
	margin-top: 22px;
}

.no-js section {
	border-top: 1px dashed #aaa;
	margin-top: 22px;
	padding-top: 22px;
}

.no-js section:first-child {
	margin: 0px;
	padding: 0px;
	border: 0px;
}

label {
	display: block;
}

label.taxonomy_private {
	font-style: italic;
}

a.toggle_link {
	font-size: small;
}
</style>


	<form method="post" action="options.php" style="clear: both">
		<?php settings_fields('duplicate_post_group'); ?>

		<h2 class="nav-tab-wrapper">
			<a class="nav-tab nav-tab-active"
				href="<?php echo admin_url() ?>/index.php?page=duplicate-post-what"><?php _e('What to copy', 'duplicate-post'); ?>
			</a> <a class="nav-tab"
				href="<?php echo admin_url() ?>/index.php?page=duplicate-post-who"><?php _e('Permissions', 'duplicate-post'); ?>
			</a> <a class="nav-tab"
				href="<?php echo admin_url() ?>/index.php?page=duplicate-post-where"><?php _e('Display', 'duplicate-post'); ?>
			</a>
		</h2>

		<section>

			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('Post/page elements to copy', 'duplicate-post'); ?>
					</th>
					<td colspan="2"><label> <input type="checkbox"
							name="duplicate_post_copytitle" value="1" <?php  if(get_option('duplicate_post_copytitle') == 1) echo 'checked="checked"'; ?>"/>
							<?php _e("Title", 'default'); ?>
					</label> <label> <input type="checkbox"
							name="duplicate_post_copydate" value="1" <?php  if(get_option('duplicate_post_copydate') == 1) echo 'checked="checked"'; ?>"/>
							<?php _e("Date", 'default'); ?>
					</label> <label> <input type="checkbox"
							name="duplicate_post_copystatus" value="1" <?php  if(get_option('duplicate_post_copystatus') == 1) echo 'checked="checked"'; ?>"/>
							<?php _e("Status", 'default'); ?>
					</label> <label> <input type="checkbox"
							name="duplicate_post_copyslug" value="1" <?php  if(get_option('duplicate_post_copyslug') == 1) echo 'checked="checked"'; ?>"/>
							<?php _e("Slug", 'default'); ?>
					</label> <label> <input type="checkbox"
							name="duplicate_post_copyexcerpt" value="1" <?php  if(get_option('duplicate_post_copyexcerpt') == 1) echo 'checked="checked"'; ?>"/>
							<?php _e("Excerpt", 'default'); ?>
					</label> <label> <input type="checkbox"
							name="duplicate_post_copycontent" value="1" <?php  if(get_option('duplicate_post_copycontent') == 1) echo 'checked="checked"'; ?>"/>
							<?php _e("Content", 'default'); ?>
					</label> <label> <input type="checkbox"
							name="duplicate_post_copypassword" value="1" <?php  if(get_option('duplicate_post_copypassword') == 1) echo 'checked="checked"'; ?>"/>
							<?php _e("Password", 'default'); ?>
					</label> <label> <input type="checkbox"
							name="duplicate_post_copyattachments" value="1" <?php  if(get_option('duplicate_post_copyattachments') == 1) echo 'checked="checked"'; ?>"/>
							<?php _e("Attachments", 'duplicate-post');  ?>
					</label> <label> <input type="checkbox"
							name="duplicate_post_copychildren" value="1" <?php  if(get_option('duplicate_post_copychildren') == 1) echo 'checked="checked"'; ?>"/>
							<?php _e("Children", 'duplicate-post');  ?>
					</label> <label> <input type="checkbox"
							name="duplicate_post_copycomments" value="1" <?php  if(get_option('duplicate_post_copycomments') == 1) echo 'checked="checked"'; ?>"/>
							<?php _e("Comments", 'default');  ?> (<?php _e("except pingbacks and trackbacks", 'duplicate-post');  ?>)
					</label>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e("Title prefix", 'duplicate-post'); ?>
					</th>
					<td><input type="text" name="duplicate_post_title_prefix"
						value="<?php echo get_option('duplicate_post_title_prefix'); ?>" />
					</td>
					<td><span class="description"><?php _e("Prefix to be added before the title, e.g. \"Copy of\" (blank for no prefix)", 'duplicate-post'); ?>
					</span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e("Title suffix", 'duplicate-post'); ?>
					</th>
					<td><input type="text" name="duplicate_post_title_suffix"
						value="<?php echo get_option('duplicate_post_title_suffix'); ?>" />
					</td>
					<td><span class="description"><?php _e("Suffix to be added after the title, e.g. \"(dup)\" (blank for no suffix)", 'duplicate-post'); ?>
					</span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e("Increase menu order by", 'duplicate-post'); ?>
					</th>
					<td><input type="text" name="duplicate_post_increase_menu_order_by"
						value="<?php echo get_option('duplicate_post_increase_menu_order_by'); ?>" />
					</td>
					<td><span class="description"><?php _e("Add this number to the original menu order (blank or zero to retain the value)", 'duplicate-post'); ?>
					</span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e("Do not copy these fields", 'duplicate-post'); ?>
					</th>
					<td id="textfield"><input type="text"
						name="duplicate_post_blacklist"
						value="<?php echo get_option('duplicate_post_blacklist'); ?>" /></td>
					<td><span class="description"><?php _e("Comma-separated list of meta fields that must not be copied", 'duplicate-post'); ?><br />
							<small><?php _e("Add <code>_thumbnail_id</code> to prevent featured images to be copied", 'duplicate-post'); ?>
						</small> </span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e("Do not copy these taxonomies", 'duplicate-post'); ?><br />
						<a class="toggle_link" href="#"
						onclick="toggle_private_taxonomies();return false;"><?php _e('Show/hide private taxonomies', 'duplicate-post');?>
					</a>
					</th>
					<td colspan="2"><?php $taxonomies=get_taxonomies(array(),'objects'); usort($taxonomies, 'duplicate_post_tax_obj_cmp');
					$taxonomies_blacklist = get_option('duplicate_post_taxonomies_blacklist');
					if ($taxonomies_blacklist == "") $taxonomies_blacklist = array();
					foreach ($taxonomies as $taxonomy ) : ?> <label
						class="taxonomy_<?php echo ($taxonomy->public)?'public':'private';?>">
							<input type="checkbox"
							name="duplicate_post_taxonomies_blacklist[]"
							value="<?php echo $taxonomy->name?>"
							<?php if(in_array($taxonomy->name, $taxonomies_blacklist)) echo 'checked="checked"'?> />
							<?php echo $taxonomy->labels->name.' ['.$taxonomy->name.']'; ?>
					</label> <?php endforeach; ?> <span class="description"><?php _e("Select the taxonomies you don't want to be copied", 'duplicate-post'); ?>
					</span>
					</td>
				</tr>
			</table>
		</section>
		<section>
			<table class="form-table">
				<?php if ( current_user_can( 'promote_users' ) ){ ?>
				<tr valign="top">
					<th scope="row"><?php _e("Roles allowed to copy", 'duplicate-post'); ?>
					</th>
					<td><?php	global $wp_roles;
					$roles = $wp_roles->get_names();
					foreach ($roles as $name => $display_name): $role = get_role($name);
					if ( !$role->has_cap('edit_posts') ) continue; ?> <label> <input
							type="checkbox" name="duplicate_post_roles[]"
							value="<?php echo $name ?>"
							<?php if($role->has_cap('copy_posts')) echo 'checked="checked"'?> />
							<?php echo translate_user_role($display_name); ?>
					</label> <?php endforeach; ?> <span class="description"><?php _e("Warning: users will be able to copy all posts, even those of other users", 'duplicate-post'); ?><br />
							<?php _e("Passwords and contents of password-protected posts may become visible to undesired users and visitors", 'duplicate-post'); ?>
					</span>
					</td>
				</tr>
				<?php } ?>
				<tr valign="top">
					<th scope="row"><?php _e("Enable for these post types", 'duplicate-post'); ?>
					</th>
					<td><?php $post_types = get_post_types(array('public' => true),'objects');
					foreach ($post_types as $post_type_object ) :
					if ($post_type_object->name == 'attachment') continue; ?> <label> <input
							type="checkbox" name="duplicate_post_types_enabled[]"
							value="<?php echo $post_type_object->name?>"
							<?php if(duplicate_post_is_post_type_enabled($post_type_object->name)) echo 'checked="checked"'?> />
							<?php echo $post_type_object->labels->name?>
					</label> <?php endforeach; ?> <span class="description"><?php _e("Select the post types you want the plugin to be enabled", 'duplicate-post'); ?>
							<br /> <?php _e("Whether the links are displayed for custom post types registered by themes or plugins depends on their use of standard WordPress UI elements", 'duplicate-post'); ?>
					</span>
					</td>
				</tr>
			</table>
		</section>
		<section>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e("Show links in", 'duplicate-post'); ?>
					</th>
					<td><label><input type="checkbox" name="duplicate_post_show_row"
							value="1" <?php  if(get_option('duplicate_post_show_row') == 1) echo 'checked="checked"'; ?>"/>
							<?php _e("Post list", 'duplicate-post'); ?> </label> <label><input
							type="checkbox" name="duplicate_post_show_submitbox" value="1" <?php  if(get_option('duplicate_post_show_submitbox') == 1) echo 'checked="checked"'; ?>"/>
							<?php _e("Edit screen", 'duplicate-post'); ?> </label> <label><input
							type="checkbox" name="duplicate_post_show_adminbar" value="1" <?php  if(get_option('duplicate_post_show_adminbar') == 1) echo 'checked="checked"'; ?>"/>
							<?php _e("Admin bar", 'duplicate-post'); ?> </label>
					</td>
				</tr>
				<tr valign="top">
					<td colspan="2"><span class="description"><?php _e("Whether the links are displayed for custom post types registered by themes or plugins depends on their use of standard WordPress UI elements", 'duplicate-post'); ?>
							<br /> <?php printf(__('You can also use the template tag duplicate_post_clone_post_link( $link, $before, $after, $id ). More info <a href="%s">here</a>', 'duplicate-post'), 'https://wordpress.org/plugins/duplicate-post/other_notes/'); ?>
					</span>
					</td>
				</tr>
			</table>
		</section>
		<p class="submit">
			<input type="submit" class="button-primary"
				value="<?php _e('Save Changes', 'duplicate-post') ?>" />
		</p>

	</form>
</div>
<?php
}
?>