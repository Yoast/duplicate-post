<?php
/**
 * Add an option page
 */
if ( is_admin() ){ // admin actions
	add_action('admin_menu', 'duplicate_post_menu');
	add_action( 'admin_init', 'duplicate_post_register_settings');
}

function duplicate_post_register_settings() { // whitelist options
	register_setting( 'duplicate_post_group', 'duplicate_post_copydate');
	register_setting( 'duplicate_post_group', 'duplicate_post_copyexcerpt');
	register_setting( 'duplicate_post_group', 'duplicate_post_copystatus');
	register_setting( 'duplicate_post_group', 'duplicate_post_blacklist');
	register_setting( 'duplicate_post_group', 'duplicate_post_taxonomies_blacklist');
	register_setting( 'duplicate_post_group', 'duplicate_post_title_prefix');
	register_setting( 'duplicate_post_group', 'duplicate_post_title_suffix');
	register_setting( 'duplicate_post_group', 'duplicate_post_copy_user_level');
}


function duplicate_post_menu() {
	add_options_page(__("Duplicate Post Options", DUPLICATE_POST_I18N_DOMAIN), __("Duplicate Post", DUPLICATE_POST_I18N_DOMAIN), 'administrator', 'duplicatepost', 'duplicate_post_options');
}

function duplicate_post_options() {
	?>
<div class="wrap">
	<h2>
	<?php _e("Duplicate Post", DUPLICATE_POST_I18N_DOMAIN); ?>
	</h2>

	<form method="post" action="options.php">
	<?php settings_fields('duplicate_post_group'); ?>

		<table class="form-table">

			<tr valign="top">
				<th scope="row"><?php _e("Copy post/page date also", DUPLICATE_POST_I18N_DOMAIN); ?>
				</th>
				<td><input type="checkbox" name="duplicate_post_copydate" value="1" <?php  if(get_option('duplicate_post_copydate') == 1) echo 'checked="checked"'; ?>"/>
					<span class="description"><?php _e("Normally, the new copy has its publication date set to current time: check the box to copy the original post/page date", DUPLICATE_POST_I18N_DOMAIN); ?>
				</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e("Copy post/page status", DUPLICATE_POST_I18N_DOMAIN); ?>
				</th>
				<td><input type="checkbox" name="duplicate_post_copystatus" value="1" <?php  if(get_option('duplicate_post_copystatus') == 1) echo 'checked="checked"'; ?>"/>
					<span class="description"><?php _e("Copy the original post status (draft, published, pending) when cloning from the post list.", DUPLICATE_POST_I18N_DOMAIN); ?>
				</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e("Copy excerpt", DUPLICATE_POST_I18N_DOMAIN); ?>
				</th>
				<td><input type="checkbox" name="duplicate_post_copyexcerpt"
					value="1" <?php  if(get_option('duplicate_post_copyexcerpt') == 1) echo 'checked="checked"'; ?>"/>
					<span class="description"><?php _e("Copy the excerpt from the original post/page", DUPLICATE_POST_I18N_DOMAIN); ?>
				</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e("Do not copy these fields", DUPLICATE_POST_I18N_DOMAIN); ?>
				</th>
				<td><input type="text" name="duplicate_post_blacklist"
					value="<?php echo get_option('duplicate_post_blacklist'); ?>" /> <span
					class="description"><?php _e("Comma-separated list of meta fields that must not be copied", DUPLICATE_POST_I18N_DOMAIN); ?>
				</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e("Do not copy these taxonomies", DUPLICATE_POST_I18N_DOMAIN); ?>
				</th>
				<td><div
						style="height: 100px; width: 300px; padding: 5px; overflow: auto; border: 1px solid #ccc">
						<?php $taxonomies=get_taxonomies(array('public' => true),'objects');
						$taxonomies_blacklist = get_option('duplicate_post_taxonomies_blacklist');
						if ($taxonomies_blacklist == "") $taxonomies_blacklist = array();
						foreach ($taxonomies as $taxonomy ) : ?>
						<label style="display: block;"> <input type="checkbox"
							name="duplicate_post_taxonomies_blacklist[]"
							value="<?php echo $taxonomy->name?>"
							<?php if(in_array($taxonomy->name,$taxonomies_blacklist)) echo 'checked="checked"'?> />
							<?php echo $taxonomy->labels->name?> </label>
							<?php endforeach; ?>
					</div> <span class="description"><?php _e("Select the taxonomies you don't want to be copied", DUPLICATE_POST_I18N_DOMAIN); ?>
				</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e("Title prefix", DUPLICATE_POST_I18N_DOMAIN); ?>
				</th>
				<td><input type="text" name="duplicate_post_title_prefix"
					value="<?php echo get_option('duplicate_post_title_prefix'); ?>" />
					<span class="description"><?php _e("Prefix to be added before the original title, e.g. \"Copy of\" (blank for no prefix)", DUPLICATE_POST_I18N_DOMAIN); ?>
				</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e("Title suffix", DUPLICATE_POST_I18N_DOMAIN); ?>
				</th>
				<td><input type="text" name="duplicate_post_title_suffix"
					value="<?php echo get_option('duplicate_post_title_suffix'); ?>" />
					<span class="description"><?php _e("Suffix to be added after the original title, e.g. \"(dup)\" (blank for no suffix)", DUPLICATE_POST_I18N_DOMAIN); ?>
				</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e("Minimum level to copy posts", DUPLICATE_POST_I18N_DOMAIN); ?>
				</th>
				<td><select name="duplicate_post_copy_user_level">
						<option value="8"
						<?php if(get_option('duplicate_post_copy_user_level') == 8) echo 'selected="selected"'?>>
							<?php echo _x("Administrator", "User role", "default")?>
						</option>
						<option value="5"
						<?php if(get_option('duplicate_post_copy_user_level') == 5) echo 'selected="selected"'?>>
							<?php echo _x("Editor", "User role", "default")?>
						</option>
						<option value="2"
						<?php if(get_option('duplicate_post_copy_user_level') == 2) echo 'selected="selected"'?>>
							<?php echo _x("Author", "User role", "default")?>
						</option>
						<option value="1"
						<?php if(get_option('duplicate_post_copy_user_level') == 1) echo 'selected="selected"'?>>
							<?php echo _x("Contributor", "User role", "default")?>
						</option>
				</select> <span class="description"><?php _e("Warning: users will be able to copy all posts, even those of higher level users", DUPLICATE_POST_I18N_DOMAIN); ?>
				</span>
				</td>
			</tr>

		</table>

		<p class="submit">
			<input type="submit" class="button-primary"
				value="<?php _e('Save Changes', DUPLICATE_POST_I18N_DOMAIN) ?>" />
		</p>

	</form>
</div>
							<?php
}
?>