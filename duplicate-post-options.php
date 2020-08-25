<?php
/**
 * Add an option page
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_admin() ){ // admin actions
	add_action( 'admin_menu', 'duplicate_post_menu' );
	add_action( 'admin_init', 'duplicate_post_register_settings' );
}

function duplicate_post_register_settings() { // whitelist options
	register_setting( 'duplicate_post_group', 'duplicate_post_copytitle');
	register_setting( 'duplicate_post_group', 'duplicate_post_copydate');
	register_setting( 'duplicate_post_group', 'duplicate_post_copystatus');
	register_setting( 'duplicate_post_group', 'duplicate_post_copyslug');
	register_setting( 'duplicate_post_group', 'duplicate_post_copyexcerpt');
	register_setting( 'duplicate_post_group', 'duplicate_post_copycontent');
	register_setting( 'duplicate_post_group', 'duplicate_post_copythumbnail');
	register_setting( 'duplicate_post_group', 'duplicate_post_copytemplate');
	register_setting( 'duplicate_post_group', 'duplicate_post_copyformat');
	register_setting( 'duplicate_post_group', 'duplicate_post_copyauthor');
	register_setting( 'duplicate_post_group', 'duplicate_post_copypassword');
	register_setting( 'duplicate_post_group', 'duplicate_post_copyattachments');
	register_setting( 'duplicate_post_group', 'duplicate_post_copychildren');
	register_setting( 'duplicate_post_group', 'duplicate_post_copycomments');
	register_setting( 'duplicate_post_group', 'duplicate_post_copymenuorder');
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
	register_setting( 'duplicate_post_group', 'duplicate_post_show_bulkactions');
	register_setting( 'duplicate_post_group', 'duplicate_post_show_original_column');
	register_setting( 'duplicate_post_group', 'duplicate_post_show_original_in_post_states');
	register_setting( 'duplicate_post_group', 'duplicate_post_show_original_meta_box');
	register_setting( 'duplicate_post_group', 'duplicate_post_show_notice');
}


function duplicate_post_menu() {
	$page_hook = add_options_page(
        /* translators: %s: Yoast */
		sprintf( __( '%s Duplicate Post settings', 'duplicate-post' ), 'Yoast' ),
		__( 'Duplicate Post', 'duplicate-post' ),
		'manage_options',
		'duplicatepost',
		'duplicate_post_options'
	);
	add_action( $page_hook, 'duplicate_post_add_options_page_css' );
}

/**
 * Enqueues a CSS file with styles for the options page.
 *
 * @ignore
 */
function duplicate_post_add_options_page_css() {
	wp_enqueue_style( 'duplicate-post-options', plugins_url( '/duplicate-post-options.css', __FILE__ ), array(), DUPLICATE_POST_CURRENT_VERSION );
}

function duplicate_post_options() {
	global $wp_roles, $wp_version;

	if ( current_user_can( 'promote_users' ) && (isset($_GET['settings-updated'])  && $_GET['settings-updated'] == true)){
		$roles = $wp_roles->get_names();

		$dp_roles = get_option('duplicate_post_roles');
		if ( $dp_roles == "" ) $dp_roles = array();

		foreach ($roles as $name => $display_name){
			$role = get_role($name);

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
	<h1>
		<?php
		/* translators: %s: Yoast */
		echo esc_html(sprintf( __( '%s Duplicate Post settings', 'duplicate-post'), 'Yoast' ) );
		?>
	</h1>

	<script>
		var tablist;
		var tabs;
		var panels;

		// For easy reference
		var keys = {
			end: 35,
			home: 36,
			left: 37,
			up: 38,
			right: 39,
			down: 40,
			delete: 46
		};

		// Add or substract depending on key pressed
		var direction = {
			37: -1,
			38: -1,
			39: 1,
			40: 1
		};


		function generateArrays () {
			tabs = document.querySelectorAll('#duplicate_post_settings_form [role="tab"]');
			panels = document.querySelectorAll('#duplicate_post_settings_form [role="tabpanel"]');
		};

		function addListeners (index) {
			tabs[index].addEventListener('click', function(event){
				var tab = event.target;
				activateTab(tab, false);
			});
			tabs[index].addEventListener('keydown', function(event) {
				var key = event.keyCode;

				switch (key) {
					case keys.end:
						event.preventDefault();
						// Activate last tab
						activateTab(tabs[tabs.length - 1]);
						break;
					case keys.home:
						event.preventDefault();
						// Activate first tab
						activateTab(tabs[0]);
						break;
				};
			});
			tabs[index].addEventListener('keyup', function(event) {
				var key = event.keyCode;

				switch (key) {
					case keys.left:
					case keys.right:
						switchTabOnArrowPress(event);
						break;
				};
			});

			// Build an array with all tabs (<button>s) in it
			tabs[index].index = index;
		};


		// Either focus the next, previous, first, or last tab
		// depening on key pressed
		function switchTabOnArrowPress (event) {
			var pressed = event.keyCode;

			for (x = 0; x < tabs.length; x++) {
				tabs[x].addEventListener('focus', focusEventHandler);
			};

			if (direction[pressed]) {
				var target = event.target;
				if (target.index !== undefined) {
					if (tabs[target.index + direction[pressed]]) {
						tabs[target.index + direction[pressed]].focus();
					}
					else if (pressed === keys.left || pressed === keys.up) {
						focusLastTab();
					}
					else if (pressed === keys.right || pressed == keys.down) {
						focusFirstTab();
					};
				};
			};
		};

		// Activates any given tab panel
		function activateTab (tab, setFocus) {
			setFocus = setFocus || true;
			// Deactivate all other tabs
			deactivateTabs();

			// Remove tabindex attribute
			tab.removeAttribute('tabindex');

			// Set the tab as selected
			tab.setAttribute('aria-selected', 'true');

			tab.classList.add('nav-tab-active');

			// Get the value of aria-controls (which is an ID)
			var controls = tab.getAttribute('aria-controls');

			// Remove hidden attribute from tab panel to make it visible
			document.getElementById(controls).removeAttribute('hidden');

			// Set focus when required
			if (setFocus) {
				tab.focus();
			};
		};

		// Deactivate all tabs and tab panels
		function deactivateTabs () {
			for (t = 0; t < tabs.length; t++) {
				tabs[t].setAttribute('tabindex', '-1');
				tabs[t].setAttribute('aria-selected', 'false');
				tabs[t].classList.remove('nav-tab-active');
				tabs[t].removeEventListener('focus', focusEventHandler);
			};

			for (p = 0; p < panels.length; p++) {
				panels[p].setAttribute('hidden', 'hidden');
			};
		};

		// Make a guess
		function focusFirstTab () {
			tabs[0].focus();
		};

		// Make a guess
		function focusLastTab () {
			tabs[tabs.length - 1].focus();
		};

		//
		function focusEventHandler (event) {
			var target = event.target;

			checkTabFocus(target);
		};

		// Only activate tab on focus if it still has focus after the delay
		function checkTabFocus (target) {
			focused = document.activeElement;

			if (target === focused) {
				activateTab(target, false);
			};
		};

		document.addEventListener("DOMContentLoaded", function () {
			tablist = document.querySelectorAll('#duplicate_post_settings_form [role="tablist"]')[0];

			generateArrays();

			// Bind listeners
			for (i = 0; i < tabs.length; ++i) {
				addListeners(i);
			};


		});

	jQuery(function(){
		jQuery('.taxonomy_private').hide();

        jQuery( '.toggle-private-taxonomies' )
            .on( 'click', function() {
                buttonElement = jQuery( this );
                jQuery( '.taxonomy_private' ).toggle( 300, function() {
                    buttonElement.attr( 'aria-expanded', jQuery( this ).is( ":visible" ) );
                } );
            } );
	});

	</script>

	<form method="post" action="options.php" style="clear: both" id="duplicate_post_settings_form">
		<?php settings_fields('duplicate_post_group'); ?>

		<header role="tablist" aria-label="<?php esc_attr_e('Settings sections', 'duplicate-post'); ?>" class="nav-tab-wrapper">
			<button
					type="button"
					role="tab"
					class="nav-tab nav-tab-active"
					aria-selected="true"
					aria-controls="what-tab"
					id="what"><?php esc_html_e('What to copy', 'duplicate-post'); ?>
			</button>
			<button
					type="button"
					role="tab"
					class="nav-tab"
					aria-selected="false"
					aria-controls="who-tab"
					id="who"
					tabindex="-1"><?php esc_html_e('Permissions', 'duplicate-post'); ?>
			</button>
			<button
					type="button"
					role="tab"
					class="nav-tab"
					aria-selected="false"
					aria-controls="where-tab"
					id="where"
					tabindex="-1"><?php esc_html_e('Display', 'duplicate-post'); ?>
			</button>
		</header>

		<section
				tabindex="0"
				role="tabpanel"
				id="what-tab"
				aria-labelledby="what">
			<h2 class="hide-if-js"><?php esc_html_e( 'What to copy', 'duplicate-post' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e('Post/page elements to copy', 'duplicate-post'); ?></th>
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
						<label for="duplicate_post_title_prefix">
							<?php esc_html_e("Title prefix", 'duplicate-post'); ?>
						</label>
					</th>
					<td><input type="text" name="duplicate_post_title_prefix"
                               id="duplicate_post_title_prefix"
                               aria-describedby="duplicate-post-title-prefix-description"
						       value="<?php form_option('duplicate_post_title_prefix'); ?>" />
                        <p id="duplicate-post-title-prefix-description">
                            <?php esc_html_e("Prefix to be added before the title, e.g. \"Copy of\" (blank for no prefix)", 'duplicate-post'); ?>
					    </p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="duplicate_post_title_suffix">
							<?php esc_html_e("Title suffix", 'duplicate-post'); ?>
						</label>
					</th>
					<td><input type="text" name="duplicate_post_title_suffix"
                               id="duplicate_post_title_suffix"
                               aria-describedby="duplicate-post-title-suffix-description"
						       value="<?php form_option('duplicate_post_title_suffix'); ?>" />
                        <p id="duplicate-post-title-suffix-description">
							<?php esc_html_e( 'Suffix to be added after the title, e.g. "(dup)" (blank for no suffix)', 'duplicate-post' ); ?>
                        </p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="duplicate_post_increase_menu_order_by">
							<?php esc_html_e("Increase menu order by", 'duplicate-post'); ?>
						</label>
					</th>
					<td><input type="number" min="0" step="1" name="duplicate_post_increase_menu_order_by"
                               id="duplicate_post_increase_menu_order_by"
                               aria-describedby="duplicate-post-increase-menu-order-by-description"
                               value="<?php form_option('duplicate_post_increase_menu_order_by'); ?>" />
                        <p id="duplicate-post-increase-menu-order-by-description">
							<?php esc_html_e( 'Add this number to the original menu order (blank or zero to retain the value)', 'duplicate-post' ); ?>
                        </p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="duplicate_post_blacklist">
							<?php esc_html_e("Do not copy these fields", 'duplicate-post'); ?>
						</label>
					</th>
					<td id="textfield"><input type="text"
						name="duplicate_post_blacklist"
					  	id="duplicate_post_blacklist"
                        aria-describedby="duplicate-post-blacklist-description"
						value="<?php form_option('duplicate_post_blacklist'); ?>" />
                        <p id="duplicate-post-blacklist-description">
							<?php esc_html_e( 'Comma-separated list of meta fields that must not be copied.', 'duplicate-post' ); ?>
							<?php esc_html_e( 'You can use * to match zero or more alphanumeric characters or underscores: e.g. field*', 'duplicate-post' ); ?>
                        </p>
                    </td>
				</tr>
				<tr>
					<th scope="row">
					    <?php esc_html_e( 'Do not copy these taxonomies', 'duplicate-post' ); ?>
					</th>
					<td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php esc_html_e( 'Do not copy these taxonomies', 'duplicate-post' ); ?></legend>
                            <?php
                            $taxonomies = get_taxonomies( array(), 'objects' );
                            usort( $taxonomies, 'duplicate_post_tax_obj_cmp' );
                            $taxonomies_blacklist = get_option( 'duplicate_post_taxonomies_blacklist' );
                            if ( ! is_array( $taxonomies_blacklist ) ) {
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
		<section
				tabindex="0"
				role="tabpanel"
				id="who-tab"
				aria-labelledby="who"
				hidden="hidden">
			<h2 class="hide-if-js"><?php esc_html_e( 'Permissions', 'duplicate-post' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php if ( current_user_can( 'promote_users' ) ){ ?>
				<tr>
					<th scope="row"><?php esc_html_e("Roles allowed to copy", 'duplicate-post'); ?></th>
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
					<th scope="row"><?php esc_html_e("Enable for these post types", 'duplicate-post'); ?></th>
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
		<section
				tabindex="0"
				role="tabpanel"
				id="where-tab"
				aria-labelledby="where"
				hidden="hidden">
			<h2 class="hide-if-js"><?php esc_html_e( 'Display', 'duplicate-post' ); ?></h2>
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
								'<a href="' . esc_url( 'https://developer.yoast.com/duplicate-post/functions-template-tags#duplicate_post_clone_post_link' ) . '">',
								'</a>'
							);
							?>
                        </p>
                    </td>
                </tr>
				<tr>
					<th scope="row"><?php esc_html_e("Show original item:", 'duplicate-post'); ?></th>
					<td>
                        <input
                                type="checkbox"
                                name="duplicate_post_show_original_meta_box"
                                id="duplicate-post-show-original-meta-box"
                                aria-describedby="duplicate-post-show-original-meta-box-description"
                                value="1"
                            <?php
                                if( 1 === intval( get_option( 'duplicate_post_show_original_meta_box' ) ) ) {
                                    echo 'checked="checked"';
                                } ?>/>
                        <label for="duplicate-post-show-original-meta-box"><?php esc_html_e("In a metabox in the Edit screen [Classic editor]", 'duplicate-post'); ?></label>
                        <p id="duplicate-post-show-original-meta-box-description">(<?php esc_html_e("you'll also be able to delete the reference to the original item with a checkbox", 'duplicate-post');  ?>)</p><br/>
                        <input
                                type="checkbox"
                                name="duplicate_post_show_original_column"
                                id="duplicate-post-show-original-column"
                                aria-describedby="duplicate-post-show-original-column-description"
							    value="1"
                            <?php
                                if( 1 === intval( get_option( 'duplicate_post_show_original_column' ) ) ) {
                                    echo 'checked="checked"';
								} ?>/>
                        <label for="duplicate-post-show-original-column"><?php esc_html_e("In a column in the Post list", 'duplicate-post'); ?></label>
                        <p id="duplicate-post-show-original-column-description">(<?php esc_html_e("you'll also be able to delete the reference to the original item with a checkbox in Quick Edit", 'duplicate-post');  ?>)</p><br/>
                        <input
                                type="checkbox"
                                name="duplicate_post_show_original_in_post_states"
                                id="duplicate-post-show-original-in-post-states"
                                value="1"
                            <?php
                                if( 1 === intval( get_option( 'duplicate_post_show_original_in_post_states' ) ) ) {
                                    echo 'checked="checked"';
								} ?>/>
							<label for="duplicate-post-show-original-in-post-states"><?php esc_html_e("After the title in the Post list", 'duplicate-post'); ?></label>
					</td>
				</tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Update notice', 'duplicate-post' ); ?></th>
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
				value="<?php esc_html_e('Save changes', 'duplicate-post') ?>" />
		</p>

	</form>
</div>
<?php
}
?>
