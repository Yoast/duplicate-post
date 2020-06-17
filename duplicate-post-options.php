<?php
/**
 * Options page
 *
 * @package Duplicate Post
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( is_admin() ) {
	add_action( 'admin_menu', 'duplicate_post_menu' );
	add_action( 'admin_init', 'duplicate_post_register_settings' );
}

/**
 * Registers settings.
 */
function duplicate_post_register_settings() {
	register_setting( 'duplicate_post_group', 'duplicate_post_copytitle' );
	register_setting( 'duplicate_post_group', 'duplicate_post_copydate' );
	register_setting( 'duplicate_post_group', 'duplicate_post_copystatus' );
	register_setting( 'duplicate_post_group', 'duplicate_post_copyslug' );
	register_setting( 'duplicate_post_group', 'duplicate_post_copyexcerpt' );
	register_setting( 'duplicate_post_group', 'duplicate_post_copycontent' );
	register_setting( 'duplicate_post_group', 'duplicate_post_copythumbnail' );
	register_setting( 'duplicate_post_group', 'duplicate_post_copytemplate' );
	register_setting( 'duplicate_post_group', 'duplicate_post_copyformat' );
	register_setting( 'duplicate_post_group', 'duplicate_post_copyauthor' );
	register_setting( 'duplicate_post_group', 'duplicate_post_copypassword' );
	register_setting( 'duplicate_post_group', 'duplicate_post_copyattachments' );
	register_setting( 'duplicate_post_group', 'duplicate_post_copychildren' );
	register_setting( 'duplicate_post_group', 'duplicate_post_copycomments' );
	register_setting( 'duplicate_post_group', 'duplicate_post_copymenuorder' );
	register_setting( 'duplicate_post_group', 'duplicate_post_blacklist' );
	register_setting( 'duplicate_post_group', 'duplicate_post_taxonomies_blacklist' );
	register_setting( 'duplicate_post_group', 'duplicate_post_title_prefix' );
	register_setting( 'duplicate_post_group', 'duplicate_post_title_suffix' );
	register_setting( 'duplicate_post_group', 'duplicate_post_increase_menu_order_by' );
	register_setting( 'duplicate_post_group', 'duplicate_post_roles' );
	register_setting( 'duplicate_post_group', 'duplicate_post_types_enabled' );
	register_setting( 'duplicate_post_group', 'duplicate_post_show_row' );
	register_setting( 'duplicate_post_group', 'duplicate_post_show_adminbar' );
	register_setting( 'duplicate_post_group', 'duplicate_post_show_submitbox' );
	register_setting( 'duplicate_post_group', 'duplicate_post_show_bulkactions' );
	register_setting( 'duplicate_post_group', 'duplicate_post_show_notice' );
}

/**
 * Adds options page to menu.
 */
function duplicate_post_menu() {
	add_options_page(
		__( 'Duplicate Post Options', 'duplicate-post' ),
		__( 'Duplicate Post', 'duplicate-post' ),
		'manage_options',
		'duplicatepost',
		'duplicate_post_options'
	);
}

/**
 * Show options page.
 *
 * @global WP_Roles $wp_roles WordPress User Roles.
 * @global string $wp_version The WordPress version string.
 */
function duplicate_post_options() {
	global $wp_roles, $wp_version;

	if ( current_user_can( 'promote_users' ) && ( isset( $_GET['settings-updated'] ) && true === $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$roles = $wp_roles->get_names();

		$dp_roles = get_option( 'duplicate_post_roles' );
		if ( '' === $dp_roles ) {
			$dp_roles = array();
		}

		foreach ( $roles as $name => $display_name ) {
			$role = get_role( $name );

			if ( ! $role->has_cap( 'copy_posts' ) && in_array( $name, $dp_roles, true ) ) {
				/* If the role doesn't have the capability and it was selected, add it. */
				$role->add_cap( 'copy_posts' );
			} elseif ( $role->has_cap( 'copy_posts' ) && ! in_array( $name, $dp_roles, true ) ) {
				/* If the role has the capability and it wasn't selected, remove it. */
				$role->remove_cap( 'copy_posts' );
			}
		}
	}
	?>
<div class="wrap">
	<h1>
		<?php esc_html_e( 'Duplicate Post Options', 'duplicate-post' ); ?>
	</h1>

	<div
		style="margin: 9px 15px 4px 0; padding: 5px 30px; text-align: center; float: left; clear: left; border: solid 3px #cccccc; width: 600px;">
		<p>
		<?php esc_html_e( 'Help me develop the plugin, add new features and improve support!', 'duplicate-post' ); ?>
		<br />
		<?php esc_html_e( 'Donate whatever sum you choose, even just 10¢.', 'duplicate-post' ); ?>
		<br /> <a href="https://duplicate-post.lopo.it/donate"><img
				id="donate-button" style="margin: 0 auto;"
				src="<?php echo esc_url( plugins_url( 'donate.png', __FILE__ ) ); ?>"
				alt="Donate" /></a> <br /> <a href="https://duplicate-post.lopo.it/"><?php esc_html_e( 'Documentation', 'duplicate-post' ); ?></a>
			- <a
				href="https://translate.wordpress.org/projects/wp-plugins/duplicate-post"><?php esc_html_e( 'Translate', 'duplicate-post' ); ?></a>
			- <a href="https://wordpress.org/support/plugin/duplicate-post"><?php esc_html_e( 'Support Forum', 'duplicate-post' ); ?></a>
		</p>
	</div>


	<script>
	jQuery(document).on( 'click', '.nav-tab-wrapper a', function() {
		jQuery('.nav-tab').removeClass('nav-tab-active');
		jQuery(this).addClass('nav-tab-active');
		jQuery('section').hide();
		jQuery('section').eq(jQuery(this).index()).show();
		return false;
	});

	jQuery( function() {
		jQuery( '.taxonomy_private' ).hide();

		jQuery( '.toggle-private-taxonomies' )
			.on( 'click', function() {
				buttonElement = jQuery( this );
				jQuery( '.taxonomy_private' ).toggle( 300, function() {
					buttonElement.attr( 'aria-expanded', jQuery( this ).is( ":visible" ) );
				} );
			} );
	} );

	</script>

	<style>
.nav-tab-wrapper {
	margin: 22px 0 0 0;
}

.js section {
	display: none;
}

.js section:first-of-type {
	display: block;
}

.no-js .nav-tab-wrapper {
	display: none;
}

.no-js section {
	border-top: 1px dashed #aaa;
	margin-top: 22px;
	padding-top: 22px;
}

.no-js section:first-of-type {
	margin: 0;
	padding: 0;
	border: 0;
}

label.taxonomy_private {
	font-style: italic;
}

.toggle-private-taxonomies.button-link {
	margin-top: 1em;
}

img#donate-button {
	vertical-align: middle;
}
	</style>


	<form method="post" action="options.php" style="clear: both">
		<?php settings_fields( 'duplicate_post_group' ); ?>

		<h2 class="nav-tab-wrapper">
			<a class="nav-tab nav-tab-active"
				href="<?php echo esc_url( admin_url( '/index.php?page=duplicate-post-what' ) ); ?>"><?php esc_html_e( 'What to copy', 'duplicate-post' ); ?>
			</a> <a class="nav-tab"
				href="<?php echo esc_url( admin_url( '/index.php?page=duplicate-post-who' ) ); ?>"><?php esc_html_e( 'Permissions', 'duplicate-post' ); ?>
			</a> <a class="nav-tab"
				href="<?php echo esc_url( admin_url( '/index.php?page=duplicate-post-where' ) ); ?>"><?php esc_html_e( 'Display', 'duplicate-post' ); ?>
			</a>
		</h2>

		<section>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Post/page elements to copy', 'duplicate-post' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Post/page elements to copy', 'duplicate-post' ); ?></legend>
							<input type="checkbox"
								name="duplicate_post_copytitle" value="1"
								id="duplicate-post-copytitle"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_copytitle' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-copytitle"><?php esc_html_e( 'Title', 'default' ); ?></label><br />
							<input type="checkbox"
								name="duplicate_post_copydate" value="1"
								id="duplicate-post-copydate"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_copydate' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-copydate"><?php esc_html_e( 'Date', 'default' ); ?></label><br />
							<input type="checkbox"
								name="duplicate_post_copystatus" value="1"
								id="duplicate-post-copystatus"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_copystatus' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-copystatus"><?php esc_html_e( 'Status', 'default' ); ?></label><br />
							<input type="checkbox"
								name="duplicate_post_copyslug" value="1"
								id="duplicate-post-copyslug"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_copyslug' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-copyslug"><?php esc_html_e( 'Slug', 'default' ); ?></label><br />
							<input type="checkbox"
								name="duplicate_post_copyexcerpt" value="1"
								id="duplicate-post-copyexcerpt"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_copyexcerpt' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-copyexcerpt"><?php esc_html_e( 'Excerpt', 'default' ); ?></label><br />
							<input type="checkbox"
								name="duplicate_post_copycontent" value="1"
								id="duplicate-post-copycontent"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_copycontent' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-copycontent"><?php esc_html_e( 'Content', 'default' ); ?></label><br />
							<input type="checkbox"
								name="duplicate_post_copythumbnail" value="1"
								id="duplicate-post-copythumbnail"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_copythumbnail' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-copythumbnail"><?php esc_html_e( 'Featured Image', 'default' ); ?></label><br />
							<input type="checkbox"
								name="duplicate_post_copytemplate" value="1"
								id="duplicate-post-copytemplate"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_copytemplate' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-copytemplate"><?php esc_html_e( 'Template', 'default' ); ?></label><br />
							<input type="checkbox"
								name="duplicate_post_copyformat" value="1"
								id="duplicate-post-copyformat"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_copyformat' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-copyformat"><?php echo esc_html_x( 'Format', 'post format', 'default' ); ?></label><br />
							<input type="checkbox"
								name="duplicate_post_copyauthor" value="1"
								id="duplicate-post-copyauthor"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_copyauthor' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-copyauthor"><?php esc_html_e( 'Author', 'default' ); ?></label><br />
							<input type="checkbox"
								name="duplicate_post_copypassword" value="1"
								id="duplicate-post-copypassword"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_copypassword' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-copypassword"><?php esc_html_e( 'Password', 'default' ); ?></label><br />
							<input type="checkbox"
								name="duplicate_post_copyattachments" value="1"
								id="duplicate-post-copyattachments"
								aria-describedby="duplicate-post-copyattachments-description"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_copyattachments' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-copyattachments"><?php esc_html_e( 'Attachments', 'duplicate-post' ); ?></label>
							<span id="duplicate-post-copyattachments-description">(<?php esc_html_e( 'you probably want this unchecked, unless you have very special requirements', 'duplicate-post' ); ?>)</span><br />
							<input type="checkbox"
								name="duplicate_post_copychildren" value="1"
								id="duplicate-post-copychildren"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_copychildren' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-copychildren"><?php esc_html_e( 'Children', 'duplicate-post' ); ?></label><br />
							<input type="checkbox"
								name="duplicate_post_copycomments" value="1"
								id="duplicate-post-copycomments"
								aria-describedby="duplicate-post-copycomments-description"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_copycomments' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-copycomments"><?php esc_html_e( 'Comments', 'default' ); ?></label>
							<span id="duplicate-post-copycomments-description">(<?php esc_html_e( 'except pingbacks and trackbacks', 'duplicate-post' ); ?>)</span><br />
							<input type="checkbox"
								name="duplicate_post_copymenuorder" value="1"
								id="duplicate-post-copymenuorder"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_copymenuorder' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-copymenuorder"><?php esc_html_e( 'Menu order', 'default' ); ?></label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="duplicate-post-title-prefix"><?php esc_html_e( 'Title prefix', 'duplicate-post' ); ?></label>
					</th>
					<td><input type="text" name="duplicate_post_title_prefix"
						id="duplicate-post-title-prefix"
						aria-describedby="duplicate-post-title-prefix-description"
						value="<?php form_option( 'duplicate_post_title_prefix' ); ?>" />
						<p id="duplicate-post-title-prefix-description">
							<?php esc_html_e( 'Prefix to be added before the title, e.g. "Copy of" (blank for no prefix)', 'duplicate-post' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="duplicate-post-title-suffix"><?php esc_html_e( 'Title suffix', 'duplicate-post' ); ?></label>
					</th>
					<td><input type="text" name="duplicate_post_title_suffix"
						id="duplicate-post-title-suffix"
						aria-describedby="duplicate-post-title-suffix-description"
						value="<?php form_option( 'duplicate_post_title_suffix' ); ?>" />
						<p id="duplicate-post-title-suffix-description">
							<?php esc_html_e( 'Suffix to be added after the title, e.g. "(dup)" (blank for no suffix)', 'duplicate-post' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="duplicate-post-increase-menu-order-by"><?php esc_html_e( 'Increase menu order by', 'duplicate-post' ); ?></label>
					</th>
					<td><input type="number" min="0" step="1" name="duplicate_post_increase_menu_order_by"
						id="duplicate-post-increase-menu-order-by"
						aria-describedby="duplicate-post-increase-menu-order-by-description"
						value="<?php form_option( 'duplicate_post_increase_menu_order_by' ); ?>" />
						<p id="duplicate-post-increase-menu-order-by-description">
							<?php esc_html_e( 'Add this number to the original menu order (blank or zero to retain the value)', 'duplicate-post' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="duplicate-post-blacklist"><?php esc_html_e( 'Do not copy these fields', 'duplicate-post' ); ?></label>
					</th>
					<td id="textfield"><input type="text"
						name="duplicate_post_blacklist"
						id="duplicate-post-blacklist"
						aria-describedby="duplicate-post-blacklist-description"
						value="<?php form_option( 'duplicate_post_blacklist' ); ?>" />
						<p id="duplicate-post-blacklist-description">
							<?php esc_html_e( 'Comma-separated list of meta fields that must not be copied.', 'duplicate-post' ); ?>
							<?php esc_html_e( 'You can use * to match zero or more alphanumeric characters or underscores: e.g. field*', 'duplicate-post' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Do not copy these taxonomies', 'duplicate-post' ); ?><br />
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Do not copy these taxonomies', 'duplicate-post' ); ?></legend>
							<?php

							$taxonomies = get_taxonomies( array(), 'objects' );
							usort( $taxonomies, 'duplicate_post_tax_obj_cmp' );
							$taxonomies_blacklist = get_option( 'duplicate_post_taxonomies_blacklist' );
							if ( '' === $taxonomies_blacklist ) {
								$taxonomies_blacklist = array();
							}
							foreach ( $taxonomies as $taxonomy ) :
								if ( 'post_format' === $taxonomy->name ) {
									continue;
								}
								?>
							<div class="taxonomy_<?php echo ( $taxonomy->public ) ? 'public' : 'private'; ?>">
								<input type="checkbox"
									name="duplicate_post_taxonomies_blacklist[]"
									id="duplicate-post-<?php echo esc_attr( $taxonomy->name ); ?>"
									value="<?php echo esc_attr( $taxonomy->name ); ?>"
									<?php
									if ( in_array( $taxonomy->name, $taxonomies_blacklist, true ) ) {
										echo 'checked="checked"';
									}
									?>
								/>
								<label for="duplicate-post-<?php echo esc_attr( $taxonomy->name ); ?>">
									<?php echo esc_html( $taxonomy->labels->name . ' [' . $taxonomy->name . ']' ); ?>
								</label><br />
							</div>
							<?php endforeach; ?>
							<button type="button" class="button-link hide-if-no-js toggle-private-taxonomies" aria-expanded="false">
								<?php esc_html_e( 'Show/hide private taxonomies', 'duplicate-post' ); ?>
							</button>
						</fieldset>
					</td>
				</tr>
			</table>
		</section>
		<section>
			<table class="form-table" role="presentation">
				<?php if ( current_user_can( 'promote_users' ) ) { ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Roles allowed to copy', 'duplicate-post' ); ?>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Roles allowed to copy', 'duplicate-post' ); ?></legend>
							<?php

							$roles             = $wp_roles->get_names();
							$post_types        = get_post_types( array( 'show_ui' => true ), 'objects' );
							$edit_capabilities = array( 'edit_posts' => true );
							foreach ( $post_types as $post_type ) {
								$edit_capabilities[ $post_type->cap->edit_posts ] = true;
							}
							foreach ( $roles as $name => $display_name ) :
								$role = get_role( $name );
								if ( count( array_intersect_key( $role->capabilities, $edit_capabilities ) ) > 0 ) :
									?>
							<input type="checkbox"
								name="duplicate_post_roles[]"
								id="duplicate-post-<?php echo esc_attr( $name ); ?>"
								value="<?php echo esc_attr( $name ); ?>"
									<?php
									if ( $role->has_cap( 'copy_posts' ) ) {
										echo 'checked="checked"';}
									?>
							/>
							<label for="duplicate-post-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( translate_user_role( $display_name ) ); ?></label><br />
									<?php
								endif;
							endforeach;
							?>
							<p>
								<?php esc_html_e( 'Warning: users will be able to copy all posts, even those of other users.', 'duplicate-post' ); ?><br />
								<?php esc_html_e( 'Passwords and contents of password-protected posts may become visible to undesired users and visitors.', 'duplicate-post' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>
				<?php } ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable for these post types', 'duplicate-post' ); ?>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Enable for these post types', 'duplicate-post' ); ?></legend>
							<?php
							$post_types = get_post_types( array( 'show_ui' => true ), 'objects' );
							foreach ( $post_types as $post_type_object ) :
								if ( 'attachment' === $post_type_object->name ) {
									continue;
								}
								?>
							<input type="checkbox"
								name="duplicate_post_types_enabled[]"
								id="duplicate-post-<?php echo esc_attr( $post_type_object->name ); ?>"
								value="<?php echo esc_attr( $post_type_object->name ); ?>"
								<?php
								if ( duplicate_post_is_post_type_enabled( $post_type_object->name ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-<?php echo esc_attr( $post_type_object->name ); ?>"><?php echo esc_html( $post_type_object->labels->name ); ?></label><br />
							<?php endforeach; ?>
							<p>
								<?php esc_html_e( 'Select the post types you want the plugin to be enabled for.', 'duplicate-post' ); ?><br />
								<?php esc_html_e( 'Whether the links are displayed for custom post types registered by themes or plugins depends on their use of standard WordPress UI elements.', 'duplicate-post' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>
			</table>
		</section>
		<section>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Show links in', 'duplicate-post' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Show links in', 'duplicate-post' ); ?></legend>
							<input
								type="checkbox"
								name="duplicate_post_show_row"
								id="duplicate-post-show-row"
								value="1"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_show_row' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-show-row"><?php esc_html_e( 'Post list', 'duplicate-post' ); ?></label><br />
							<input
								type="checkbox"
								name="duplicate_post_show_submitbox"
								id="duplicate-post-show-submitbox"
								value="1"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_show_submitbox' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-show-submitbox"><?php esc_html_e( 'Edit screen', 'duplicate-post' ); ?></label><br />
							<input
								type="checkbox"
								name="duplicate_post_show_adminbar"
								id="duplicate-post-show-adminbar"
								aria-describedby="duplicate-post-show-adminbar-description"
								value="1"
								<?php
								if ( 1 === intval( get_option( 'duplicate_post_show_adminbar' ) ) ) {
									echo 'checked="checked"';}
								?>
							/>
							<label for="duplicate-post-show-adminbar"><?php esc_html_e( 'Admin bar', 'duplicate-post' ); ?></label>
							<span id="duplicate-post-show-adminbar-description">(<?php esc_html_e( 'now works on Edit screen too - check this option to use with Gutenberg enabled', 'duplicate-post' ); ?>)</span><br />
							<?php
							if ( version_compare( $wp_version, '4.7' ) >= 0 ) {
								?>
								<input
									type="checkbox"
									name="duplicate_post_show_bulkactions"
									id="duplicate-post-show-bulkactions"
									value="1"
									<?php
									if ( 1 === intval( get_option( 'duplicate_post_show_bulkactions' ) ) ) {
										echo 'checked="checked"';}
									?>
								/>
								<label for="duplicate-post-show-bulkactions"><?php esc_html_e( 'Bulk Actions', 'default' ); ?></label>
							<?php } ?>
						</fieldset>
						<p>
							<?php esc_html_e( 'Whether the links are displayed for custom post types registered by themes or plugins depends on their use of standard WordPress UI elements.', 'duplicate-post' ); ?>
							<br />
							<?php
							printf(
								/* translators: 1: Code start tag, 2: Code closing tag, 3: Link start tag to the template tag documentation, 4: Link closing tag. */
								esc_html__( 'You can also use the template tag %1$sduplicate_post_clone_post_link( $link, $before, $after, $id )%2$s. %3$sMore info on the template tag%4$s.', 'duplicate-post' ),
								'<code>',
								'</code>',
								'<a href="' . esc_url( 'https://duplicate-post.lopo.it/docs/developers-guide/functions-template-tags/duplicate_post_clone_post_link/' ) . '">',
								'</a>'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Update notice', 'duplicate-post' ); ?>
					</th>
					<td>
						<input
							type="checkbox"
							name="duplicate_post_show_notice"
							id="duplicate-post-show-notice"
							value="1"
							<?php
							if ( 1 === intval( get_option( 'duplicate_post_show_notice' ) ) ) {
								echo 'checked="checked"';
							}
							?>
						/>
						<label for="duplicate-post-show-notice"><?php esc_html_e( 'Show update notice', 'duplicate-post' ); ?></label>
					</td>
				</tr>
			</table>
		</section>
		<p class="submit">
			<input type="submit" class="button button-primary"
				value="<?php esc_html_e( 'Save changes', 'duplicate-post' ); ?>" />
		</p>

	</form>
</div>
	<?php
}
?>
