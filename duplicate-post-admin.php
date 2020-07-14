<?php
/**
 * Backend functions.
 *
 * @package Duplicate Post
 * @since 2.0
 */

if ( ! is_admin() ) {
	return;
}

require_once dirname( __FILE__ ) . '/duplicate-post-options.php';

require_once dirname( __FILE__ ) . '/compat/duplicate-post-wpml.php';
require_once dirname( __FILE__ ) . '/compat/duplicate-post-jetpack.php';

/**
 * Wrapper for the option 'duplicate_post_version'.
 */
function duplicate_post_get_installed_version() {
	return get_option( 'duplicate_post_version' );
}

/**
 * Wrapper for the defined constant DUPLICATE_POST_CURRENT_VERSION.
 */
function duplicate_post_get_current_version() {
	return DUPLICATE_POST_CURRENT_VERSION;
}

add_action( 'admin_init', 'duplicate_post_admin_init' );

/**
 * Adds handlers depending on the options.
 */
function duplicate_post_admin_init() {
	duplicate_post_plugin_upgrade();

	if ( intval( get_option( 'duplicate_post_show_row' ) ) === 1 ) {
		add_filter( 'post_row_actions', 'duplicate_post_make_duplicate_link_row', 10, 2 );
		add_filter( 'page_row_actions', 'duplicate_post_make_duplicate_link_row', 10, 2 );
	}

	if ( intval( get_site_option( 'duplicate_post_show_notice' ) ) === 1 ) {
		if ( is_multisite() ) {
			add_action( 'network_admin_notices', 'duplicate_post_show_update_notice' );
		} else {
			add_action( 'admin_notices', 'duplicate_post_show_update_notice' );
		}
		add_action( 'wp_ajax_duplicate_post_dismiss_notice', 'duplicate_post_dismiss_notice' );
	}

	if ( intval( get_option( 'duplicate_post_show_submitbox' ) ) === 1 ) {
		add_action( 'post_submitbox_start', 'duplicate_post_add_duplicate_post_button' );
	}

	if ( intval( get_option( 'duplicate_post_show_original_column' ) ) === 1 ) {
		duplicate_post_show_original_column();
	}

	if ( intval( get_option( 'duplicate_post_show_original_in_post_states' ) ) === 1 ) {
		add_filter( 'display_post_states', 'duplicate_post_show_original_in_post_states', 10, 2 );
	}

	if ( intval( get_option( 'duplicate_post_show_original_meta_box' ) ) === 1 ) {
		add_action( 'add_meta_boxes', 'duplicate_post_add_custom_box' );
		add_action( 'save_post', 'duplicate_post_save_quick_edit_data' );
	}

	/**
	 * Connect actions to functions.
	 */
	add_action( 'admin_action_duplicate_post_save_as_new_post', 'duplicate_post_save_as_new_post' );
	add_action( 'admin_action_duplicate_post_save_as_new_post_draft', 'duplicate_post_save_as_new_post_draft' );

	add_filter( 'removable_query_args', 'duplicate_post_add_removable_query_arg', 10, 1 );

	add_action( 'dp_duplicate_post', 'duplicate_post_copy_post_meta_info', 10, 2 );
	add_action( 'dp_duplicate_page', 'duplicate_post_copy_post_meta_info', 10, 2 );

	if ( intval( get_option( 'duplicate_post_copychildren' ) ) === 1 ) {
		add_action( 'dp_duplicate_post', 'duplicate_post_copy_children', 20, 3 );
		add_action( 'dp_duplicate_page', 'duplicate_post_copy_children', 20, 3 );
	}

	if ( intval( get_option( 'duplicate_post_copyattachments' ) ) === 1 ) {
		add_action( 'dp_duplicate_post', 'duplicate_post_copy_attachments', 30, 2 );
		add_action( 'dp_duplicate_page', 'duplicate_post_copy_attachments', 30, 2 );
	}

	if ( intval( get_option( 'duplicate_post_copycomments' ) ) === 1 ) {
		add_action( 'dp_duplicate_post', 'duplicate_post_copy_comments', 40, 2 );
		add_action( 'dp_duplicate_page', 'duplicate_post_copy_comments', 40, 2 );
	}

	add_action( 'dp_duplicate_post', 'duplicate_post_copy_post_taxonomies', 50, 2 );
	add_action( 'dp_duplicate_page', 'duplicate_post_copy_post_taxonomies', 50, 2 );

	add_filter( 'plugin_row_meta', 'duplicate_post_add_plugin_links', 10, 2 );

	add_action( 'admin_notices', 'duplicate_post_action_admin_notice' );
}

/**
 * Plugin upgrade.
 */
function duplicate_post_plugin_upgrade() {
	$installed_version = duplicate_post_get_installed_version();

	if ( duplicate_post_get_current_version() === $installed_version ) {
		return;
	}

	if ( empty( $installed_version ) ) {
		// Get default roles.
		$default_roles = array(
			'administrator',
			'editor',
			'wpseo_manager',
            'wpseo_editor'
		);

		// Cycle all roles and assign capability if its level >= duplicate_post_copy_user_level.
		foreach ( $default_roles as $level => $name ) {
			$role = get_role( $name );
			if ( ! empty( $role ) ) {
				$role->add_cap( 'copy_posts' );
			}
		}
	} else {
		$min_user_level = get_option( 'duplicate_post_copy_user_level' );

		if ( ! empty( $min_user_level ) ) {
			// Get default roles.
			$default_roles = array(
				1 => 'contributor',
				2 => 'author',
				3 => 'editor',
				8 => 'administrator',
			);

			// Cycle all roles and assign capability if its level >= duplicate_post_copy_user_level.
			foreach ( $default_roles as $level => $name ) {
				$role = get_role( $name );
				if ( $role && $min_user_level <= $level ) {
					$role->add_cap( 'copy_posts' );
				}
			}
			delete_option( 'duplicate_post_copy_user_level' );
		}
	}

	add_option( 'duplicate_post_copytitle', '1' );
	add_option( 'duplicate_post_copydate', '0' );
	add_option( 'duplicate_post_copystatus', '0' );
	add_option( 'duplicate_post_copyslug', '0' );
	add_option( 'duplicate_post_copyexcerpt', '1' );
	add_option( 'duplicate_post_copycontent', '1' );
	add_option( 'duplicate_post_copythumbnail', '1' );
	add_option( 'duplicate_post_copytemplate', '1' );
	add_option( 'duplicate_post_copyformat', '1' );
	add_option( 'duplicate_post_copyauthor', '0' );
	add_option( 'duplicate_post_copypassword', '0' );
	add_option( 'duplicate_post_copyattachments', '0' );
	add_option( 'duplicate_post_copychildren', '0' );
	add_option( 'duplicate_post_copycomments', '0' );
	add_option( 'duplicate_post_copymenuorder', '1' );
	add_option( 'duplicate_post_taxonomies_blacklist', array() );
	add_option( 'duplicate_post_blacklist', '' );
	add_option( 'duplicate_post_types_enabled', array( 'post', 'page' ) );
	add_option( 'duplicate_post_show_row', '1' );
	add_option( 'duplicate_post_show_adminbar', '1' );
	add_option( 'duplicate_post_show_submitbox', '1' );
	add_option( 'duplicate_post_show_bulkactions', '1' );
	add_option( 'duplicate_post_show_original_column', '0' );
	add_option( 'duplicate_post_show_original_in_post_states', '0' );
	add_option( 'duplicate_post_show_original_meta_box', '0' );

	$taxonomies_blacklist = get_option( 'duplicate_post_taxonomies_blacklist' );
	if ( '' === $taxonomies_blacklist ) {
		$taxonomies_blacklist = array();
	}
	if ( in_array( 'post_format', $taxonomies_blacklist, true ) ) {
		update_option( 'duplicate_post_copyformat', 0 );
		$taxonomies_blacklist = array_diff( $taxonomies_blacklist, array( 'post_format' ) );
		update_option( 'duplicate_post_taxonomies_blacklist', $taxonomies_blacklist );
	}

	$meta_blacklist = explode( ',', get_option( 'duplicate_post_blacklist' ) );
	if ( '' === $meta_blacklist ) {
		$meta_blacklist = array();
	}
	$meta_blacklist = array_map( 'trim', $meta_blacklist );
	if ( in_array( '_wp_page_template', $meta_blacklist, true ) ) {
		update_option( 'duplicate_post_copytemplate', 0 );
		$meta_blacklist = array_diff( $meta_blacklist, array( '_wp_page_template' ) );
	}
	if ( in_array( '_thumbnail_id', $meta_blacklist, true ) ) {
		update_option( 'duplicate_post_copythumbnail', 0 );
		$meta_blacklist = array_diff( $meta_blacklist, array( '_thumbnail_id' ) );
	}
	update_option( 'duplicate_post_blacklist', implode( ',', $meta_blacklist ) );

	delete_option( 'duplicate_post_admin_user_level' );
	delete_option( 'duplicate_post_create_user_level' );
	delete_option( 'duplicate_post_view_user_level' );
	delete_option( 'dp_notice' );

	delete_site_option( 'duplicate_post_version' );
	update_option( 'duplicate_post_version', duplicate_post_get_current_version() );

	delete_option( 'duplicate_post_show_notice' );
	update_site_option( 'duplicate_post_show_notice', 1 );
}

/**
 * Shows the update notice.
 *
 * @global string $wp_version The WordPress version string.
 */
function duplicate_post_show_update_notice() {
	global $wp_version;

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$class   = 'notice is-dismissible';
	$message = '<p><strong>' . sprintf(
		/* translators: %s: Duplicate Post version. */
		__( "What's new in Duplicate Post version %s:", 'duplicate-post' ),
		DUPLICATE_POST_CURRENT_VERSION
	) . '</strong></p>';

	$message .= '<p>%%SIGNUP_FORM%%</p>';

	$message .= '<p>' . __( 'Serving the WordPress community since November 2007.', 'duplicate-post' ) . '</p>';

	if ( version_compare( $wp_version, '4.2' ) < 0 ) {
		$message .= ' | <a id="duplicate-post-dismiss-notice" href="javascript:duplicate_post_dismiss_notice();">' .
			__( 'Dismiss this notice.', 'default' ) . '</a>';
	}
	$allowed_tags = array(
		'a'      => array(
			'href'  => array(),
			'title' => array(),
		),
		'br'     => array(),
		'p'      => array(),
		'em'     => array(),
		'strong' => array(),
	);

	$sanitized_message = wp_kses( $message, $allowed_tags );
	$sanitized_message = str_replace( '%%SIGNUP_FORM%%', duplicate_post_newsletter_signup_form(), $sanitized_message );

	$img_path = plugins_url( '/duplicate_post_yoast_icon-125x125.png', __FILE__ );

	echo '<div id="duplicate-post-notice" class="' . esc_attr( $class ) . '" style="display: flex; align-items: center;">
            <img src="' . esc_url( $img_path ) . '" alt=""/>
			<div style="margin: 0.5em">' . $sanitized_message . // phpcs:ignore WordPress.Security.EscapeOutput
			'</div></div>';

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
				jQuery('body').on('click', '.notice-dismiss', function(){
					duplicate_post_dismiss_notice();
				});
			});
			</script>";
}

/**
 * Dismisses the notice.
 *
 * @return boolean.
 */
function duplicate_post_dismiss_notice() {
	$result = update_site_option( 'duplicate_post_show_notice', 0 );
	return $result;
}

/**
 * Adds functions to columns-related hooks.
 */
function duplicate_post_show_original_column() {
	$duplicate_post_types_enabled = get_option( 'duplicate_post_types_enabled', array( 'post', 'page' ) );
	if ( ! is_array( $duplicate_post_types_enabled ) ) {
		$duplicate_post_types_enabled = array( $duplicate_post_types_enabled );
	}

	if ( count( $duplicate_post_types_enabled ) ) {
		foreach ( $duplicate_post_types_enabled as $enabled_post_type ) {
			add_filter( "manage_{$enabled_post_type}_posts_columns", 'duplicate_post_add_original_column' );
			add_action( "manage_{$enabled_post_type}_posts_custom_column", 'duplicate_post_show_original_item', 10, 2 );
		}
		add_action( 'quick_edit_custom_box', 'duplicate_post_quick_edit_remove_original', 10, 2 );
		add_action( 'save_post', 'duplicate_post_save_quick_edit_data' );
		add_action( 'admin_enqueue_scripts', 'duplicate_post_admin_enqueue_scripts' );
	}
}

/**
 * Adds Original item column to the post list.
 *
 * @ignore
 *
 * @param array $post_columns The post columns array.
 * @return array.
 */
function duplicate_post_add_original_column( $post_columns ) {
	$post_columns['duplicate_post_original_item'] = __( 'Original item', 'duplicate-post' );
	return $post_columns;
}

/**
 * Sets the text to be displayed in the Original item column for the current post.
 *
 * @ignore
 *
 * @param string  $column_name  The name for the current column.
 * @param integer $post_id     The ID for the current post.
 */
function duplicate_post_show_original_item( $column_name, $post_id ) {
	if ( 'duplicate_post_original_item' === $column_name ) {
		$column_value  = '<span data-no_original>-</span>';
		$original_item = duplicate_post_get_original( $post_id );
		if ( $original_item ) {
			$column_value = duplicate_post_get_edit_or_view_link( $original_item );
		}
		echo $column_value;  // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/**
 * Adds original item checkbox + edit link in the Quick Edit.
 *
 * @ignore
 *
 * @param string $column_name  The name for the current column.
 * @param string $post_type    The post type for the current post.
 */
function duplicate_post_quick_edit_remove_original( $column_name, $post_type ) {
	if ( 'duplicate_post_original_item' !== $column_name ) {
		return;
	}
	printf(
		'<fieldset class="inline-edit-col-left" id="duplicate_post_quick_edit_fieldset">
			<div class="inline-edit-col">
                <input type="checkbox" 
                name="duplicate_post_remove_original" 
                id="duplicate-post-remove-original" 
                value="duplicate_post_remove_original"
                aria-describedby="duplicate-post-remove-original-description">
                <label for="duplicate-post-remove-original">
					<span class="checkbox-title">%s</span>
				</label>
				<span id="duplicate-post-remove-original-description" class="checkbox-title">%s</span>
			</div>
		</fieldset>',
		esc_html__(
			'Delete reference to original item.',
			'duplicate-post'
		),
		wp_kses(
			__(
				'The original item this was copied from is: <span class="duplicate_post_original_item_title_span"></span>',
				'duplicate-post'
			),
			array(
				'span' => array(
					'class' => array(),
				),
			)
		)
	);
}

/**
 * Deletes the custom field with the ID of the original post.
 *
 * @ignore
 *
 * @param integer $post_id The current post ID.
 * @return void
 */
function duplicate_post_save_quick_edit_data( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! empty( $_POST['duplicate_post_remove_original'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		delete_post_meta( $post_id, '_dp_original' );
	}
}

/**
 * Shows link to original post in the post states.
 *
 * @ignore
 *
 * @param array    $post_states The array of post states.
 * @param \WP_Post $post The current post.
 * @return array
 */
function duplicate_post_show_original_in_post_states( $post_states, $post ) {
	$original_item = duplicate_post_get_original( $post->ID );
	if ( $original_item ) {
		// translators: Original item link (to view or edit) or title.
		$post_states['duplicate_post_original_item'] = sprintf( __( 'Original: %s', 'duplicate-post' ), duplicate_post_get_edit_or_view_link( $original_item ) );
	}
	return $post_states;
}

/**
 * Enqueues the Javascript file to inject column data into the Quick Edit.
 *
 * @ignore
 *
 * @param string $hook The current admin page.
 */
function duplicate_post_admin_enqueue_scripts( $hook ) {
	if ( 'edit.php' === $hook ) {
		wp_enqueue_script( 'duplicate_post_admin_script', plugins_url( 'duplicate_post_admin_script.js', __FILE__ ), false, DUPLICATE_POST_CURRENT_VERSION, true );
	}
}

/**
 * Adds a metabox to Edit screen.
 *
 * @ignore
 */
function duplicate_post_add_custom_box() {
	$screens = get_option( 'duplicate_post_types_enabled' );
	if ( ! is_array( $screens ) ) {
		$screens = array( $screens );
	}
	foreach ( $screens as $screen ) {
		add_meta_box(
			'duplicate_post_show_original',
			'Duplicate Post',
			'duplicate_post_custom_box_html',
			$screen,
			'side'
		);
	}
}

/**
 * Outputs the HTML for the metabox.
 *
 * @ignore
 *
 * @param \WP_Post $post The current post.
 */
function duplicate_post_custom_box_html( $post ) {
	$original_item = duplicate_post_get_original( $post->ID );
	if ( $original_item ) {
		?>
	<p>
		<input type="checkbox"
			name="duplicate_post_remove_original"
			id="duplicate-post-remove-original"
			value="duplicate_post_remove_original"
			aria-describedby="duplicate-post-remove-original-description">
		<label for="duplicate-post-remove-original">
			<?php esc_html_e( 'Delete reference to original item.', 'duplicate-post' ); ?>
		</label>
	</p>
	<p id="duplicate-post-remove-original-description">
		<?php
		printf(
			wp_kses(
				/* translators: %s: post title */
				__(
					'The original item this was copied from is: <span class="duplicate_post_original_item_title_span">%s</span>',
					'duplicate-post'
				),
				array(
					'span' => array(
						'class' => array(),
					),
				)
			),
			duplicate_post_get_edit_or_view_link( $original_item )  // phpcs:ignore WordPress.Security.EscapeOutput
		);
		?>
	</p>
		<?php
	} else {
		?>
		<script>
			(function(jQuery){
				jQuery('#duplicate_post_show_original').hide();
			})(jQuery);
		</script>
		<?php
	}
}

/**
 * Adds the link to action list for post_row_actions.
 *
 * @param array   $actions The actions array.
 * @param WP_Post $post The post object.
 * @return array.
 */
function duplicate_post_make_duplicate_link_row( $actions, $post ) {
	// $title = empty( $post->post_title ) ? __( '(no title)', 'duplicate-post' ) : $post->post_title;
	$title = _draft_or_post_title( $post );

	/**
	 * Filter allowing displaying duplicate post link for current post.
	 *
	 * @param boolean $show_duplicate_link When to show duplicate link.
	 * @param WP_Post $post                The post object.
	 *
	 * @return boolean
	 */
	if ( apply_filters( 'duplicate_post_show_link', duplicate_post_is_current_user_allowed_to_copy() && duplicate_post_is_post_type_enabled( $post->post_type ), $post ) ) {
		$actions['clone'] = '<a href="' . duplicate_post_get_clone_post_link( $post->ID, 'display', false ) .
			'" aria-label="' . esc_attr(
				/* translators: %s: Post title. */
				sprintf( __( 'Clone &#8220;%s&#8221;', 'duplicate-post' ), $title )
			) . '">' .
			esc_html_x( 'Clone', 'verb', 'duplicate-post' ) . '</a>';

		$actions['edit_as_new_draft'] = '<a href="' . duplicate_post_get_clone_post_link( $post->ID ) .
			'" aria-label="' . esc_attr(
				/* translators: %s: Post title. */
				sprintf( __( 'New draft of &#8220;%s&#8221;', 'duplicate-post' ), $title )
			) . '">' .
			esc_html__( 'New Draft', 'duplicate-post' ) .
			'</a>';
	}
	return $actions;
}

/**
 * Adds a button in the post/page edit screen to create a clone
 *
 * @param WP_Post|null $post The post object that's being edited.
 */
function duplicate_post_add_duplicate_post_button( $post = null ) {
	if ( is_null( $post ) ) {
		if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$id   = intval( wp_unslash( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$post = get_post( $id );
		}
	}
	if ( ! is_null( $post ) ) {
		/** This filter is documented in duplicate-post-admin.php */
		if ( apply_filters( 'duplicate_post_show_link', duplicate_post_is_current_user_allowed_to_copy() && duplicate_post_is_post_type_enabled( $post->post_type ), $post ) ) {
			?>
<div id="duplicate-action">
	<a class="submitduplicate duplication"
		href="<?php echo esc_url( duplicate_post_get_clone_post_link( $post->id ) ); ?>"><?php esc_html_e( 'Copy to a new draft', 'duplicate-post' ); ?>
	</a>
</div>
			<?php
		}
	}
}

/**
 * Calls the creation of a new copy of the selected post (as a draft) then redirects to the edit post screen.
 *
 * @see duplicate_post_save_as_new_post()
 */
function duplicate_post_save_as_new_post_draft() {
	duplicate_post_save_as_new_post( 'draft' );
}

/**
 * Adds 'cloned' to the removable query args.
 *
 * @ignore
 *
 * @param array $removable_query_args Array of query args keys.
 * @return array.
 */
function duplicate_post_add_removable_query_arg( $removable_query_args ) {
	$removable_query_args[] = 'cloned';
	return $removable_query_args;
}

/**
 * Calls the creation of a new copy of the selected post (by default preserving the original publish status)
 * then redirects to the post list.
 *
 * @param string $status The status name.
 */
function duplicate_post_save_as_new_post( $status = '' ) {
	if ( ! duplicate_post_is_current_user_allowed_to_copy() ) {
		wp_die( esc_html__( 'Current user is not allowed to copy posts.', 'duplicate-post' ) );
	}

	if ( ! ( isset( $_GET['post'] ) || isset( $_POST['post'] ) || // Input var okay.
			( isset( $_REQUEST['action'] ) && 'duplicate_post_save_as_new_post' === $_REQUEST['action'] ) ) ) { // Input var okay.
		wp_die( esc_html__( 'No post to duplicate has been supplied!', 'duplicate-post' ) );
	}

	// Nonce check.
	check_admin_referer( 'duplicate-post_' . ( isset( $_GET['post'] ) ? intval( wp_unslash( $_GET['post'] ) ) : intval( wp_unslash( $_POST['post'] ) ) ) ); // Input var okay.

	// Get the original post.
	$id   = ( isset( $_GET['post'] ) ? intval( wp_unslash( $_GET['post'] ) ) : intval( wp_unslash( $_POST['post'] ) ) ); // Input var okay.
	$post = get_post( $id );

	// Copy the post and insert it.
	if ( isset( $post ) && null !== $post ) {
		$post_type = $post->post_type;
		$new_id    = duplicate_post_create_duplicate( $post, $status );

		// Die on insert error.
		if ( is_wp_error( $new_id ) ) {
			wp_die(
				esc_html(
					__( 'Copy creation failed, could not find original:', 'duplicate-post' ) . ' '
					. htmlspecialchars( $id )
				)
			);
		}

		if ( '' === $status ) {
			$sendback = wp_get_referer();
			if ( ! $sendback || strpos( $sendback, 'post.php' ) !== false || strpos( $sendback, 'post-new.php' ) !== false ) {
				if ( 'attachment' === $post_type ) {
					$sendback = admin_url( 'upload.php' );
				} else {
					$sendback = admin_url( 'edit.php' );
					if ( ! empty( $post_type ) ) {
						$sendback = add_query_arg( 'post_type', $post_type, $sendback );
					}
				}
			} else {
				$sendback = remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'cloned', 'ids' ), $sendback );
			}
			// Redirect to the post list screen.
			wp_safe_redirect(
				add_query_arg(
					array(
						'cloned' => 1,
						'ids'    => $post->ID,
					),
					$sendback
				)
			);
			exit();
		} else {
			// Redirect to the edit screen for the new draft post.
			wp_safe_redirect(
				add_query_arg(
					array(
						'cloned' => 1,
						'ids'    => $post->ID,
					),
					admin_url( 'post.php?action=edit&post=' . $new_id . ( isset( $_GET['classic-editor'] ) ? '&classic-editor' : '' ) )
				)
			);
			exit();
		}
	} else {
		wp_die(
			esc_html(
				__( 'Copy creation failed, could not find original:', 'duplicate-post' ) . ' '
				. htmlspecialchars( $id )
			)
		);
	}
}

/**
 * Copies the taxonomies of a post to another post.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param integer $new_id New post ID.
 * @param WP_Post $post The original post object.
 */
function duplicate_post_copy_post_taxonomies( $new_id, $post ) {
	global $wpdb;
	if ( isset( $wpdb->terms ) ) {
		// Clear default category (added by wp_insert_post).
		wp_set_object_terms( $new_id, null, 'category' );

		$post_taxonomies = get_object_taxonomies( $post->post_type );
		// several plugins just add support to post-formats but don't register post_format taxonomy.
		if ( post_type_supports( $post->post_type, 'post-formats' ) && ! in_array( 'post_format', $post_taxonomies, true ) ) {
			$post_taxonomies[] = 'post_format';
		}

		$taxonomies_blacklist = get_option( 'duplicate_post_taxonomies_blacklist' );
		if ( '' === $taxonomies_blacklist ) {
			$taxonomies_blacklist = array();
		}
		if ( intval( get_option( 'duplicate_post_copyformat' ) ) === 0 ) {
			$taxonomies_blacklist[] = 'post_format';
		}
		$taxonomies = array_diff( $post_taxonomies, $taxonomies_blacklist );
		foreach ( $taxonomies as $taxonomy ) {
			$post_terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'orderby' => 'term_order' ) );
			$terms      = array();
			$num_terms  = count( $post_terms );
			for ( $i = 0; $i < $num_terms; $i++ ) {
				$terms[] = $post_terms[ $i ]->slug;
			}
			wp_set_object_terms( $new_id, $terms, $taxonomy );
		}
	}
}

/**
 * Copies the meta information of a post to another post
 *
 * @param integer $new_id The new post ID.
 * @param WP_Post $post The original post object.
 */
function duplicate_post_copy_post_meta_info( $new_id, $post ) {
	$post_meta_keys = get_post_custom_keys( $post->ID );
	if ( empty( $post_meta_keys ) ) {
		return;
	}
	$meta_blacklist = get_option( 'duplicate_post_blacklist' );
	if ( '' === $meta_blacklist ) {
		$meta_blacklist = array();
	} else {
		$meta_blacklist = explode( ',', $meta_blacklist );
		$meta_blacklist = array_filter( $meta_blacklist );
		$meta_blacklist = array_map( 'trim', $meta_blacklist );
	}
	$meta_blacklist[] = '_edit_lock'; // edit lock.
	$meta_blacklist[] = '_edit_last'; // edit lock.
	if ( intval( get_option( 'duplicate_post_copytemplate' ) ) === 0 ) {
		$meta_blacklist[] = '_wp_page_template';
	}
	if ( intval( get_option( 'duplicate_post_copythumbnail' ) ) === 0 ) {
		$meta_blacklist[] = '_thumbnail_id';
	}

	$meta_blacklist = apply_filters( 'duplicate_post_blacklist_filter', $meta_blacklist );

	$meta_blacklist_string = '(' . implode( ')|(', $meta_blacklist ) . ')';
	if ( strpos( $meta_blacklist_string, '*' ) !== false ) {
		$meta_blacklist_string = str_replace( array( '*' ), array( '[a-zA-Z0-9_]*' ), $meta_blacklist_string );

		$meta_keys = array();
		foreach ( $post_meta_keys as $meta_key ) {
			if ( ! preg_match( '#^' . $meta_blacklist_string . '$#', $meta_key ) ) {
				$meta_keys[] = $meta_key;
			}
		}
	} else {
		$meta_keys = array_diff( $post_meta_keys, $meta_blacklist );
	}

	$meta_keys = apply_filters( 'duplicate_post_meta_keys_filter', $meta_keys );

	foreach ( $meta_keys as $meta_key ) {
		$meta_values = get_post_custom_values( $meta_key, $post->ID );
		foreach ( $meta_values as $meta_value ) {
			$meta_value = maybe_unserialize( $meta_value );
			add_post_meta( $new_id, $meta_key, duplicate_post_wp_slash( $meta_value ) );
		}
	}
}

/**
 * Workaround for inconsistent wp_slash.
 * Works only with WP 4.4+ (map_deep)
 *
 * @ignore
 *
 * @param mixed $value Array or object to be recursively slashed.
 * @return string|mixed.
 */
function duplicate_post_addslashes_deep( $value ) {
	if ( function_exists( 'map_deep' ) ) {
		return map_deep( $value, 'duplicate_post_addslashes_to_strings_only' );
	} else {
		return wp_slash( $value );
	}
}

/**
 * Adds slashes only to strings.
 *
 * @ignore
 *
 * @param mixed $value Value to slash only if string.
 * @return string|mixed.
 */
function duplicate_post_addslashes_to_strings_only( $value ) {
	return is_string( $value ) ? addslashes( $value ) : $value;
}

/**
 * Replacement function for faulty core wp_slash().
 *
 * @ignore
 *
 * @param mixed $value What to add slash to.
 * @return mixed.
 */
function duplicate_post_wp_slash( $value ) {
	return duplicate_post_addslashes_deep( $value );
}

/**
 * Copies attachments, including physical files.
 *
 * @param integer $new_id The new post ID.
 * @param WP_Post $post The original post object.
 */
function duplicate_post_copy_attachments( $new_id, $post ) {
	// get thumbnail ID.
	$old_thumbnail_id = get_post_thumbnail_id( $post->ID );
	// get children.
	$children = get_posts(
		array(
			'post_type'   => 'any',
			'numberposts' => - 1,
			'post_status' => 'any',
			'post_parent' => $post->ID,
		)
	);
	// clone old attachments.
	foreach ( $children as $child ) {
		if ( 'attachment' !== $child->post_type ) {
			continue;
		}
		$url = wp_get_attachment_url( $child->ID );
		// Let's copy the actual file.
		$tmp = download_url( $url );
		if ( is_wp_error( $tmp ) ) {
			continue;
		}

		$desc = wp_slash( $child->post_content );

		$file_array             = array();
		$file_array['name']     = basename( $url );
		$file_array['tmp_name'] = $tmp;
		// "Upload" to the media collection
		$new_attachment_id = media_handle_sideload( $file_array, $new_id, $desc );

		if ( is_wp_error( $new_attachment_id ) ) {
			unlink( $file_array['tmp_name'] );
			continue;
		}
		$new_post_author = wp_get_current_user();
		$cloned_child    = array(
			'ID'           => $new_attachment_id,
			'post_title'   => $child->post_title,
			'post_exceprt' => $child->post_title,
			'post_author'  => $new_post_author->ID,
		);
		wp_update_post( wp_slash( $cloned_child ) );

		$alt_title = get_post_meta( $child->ID, '_wp_attachment_image_alt', true );
		if ( $alt_title ) {
			update_post_meta( $new_attachment_id, '_wp_attachment_image_alt', wp_slash( $alt_title ) );
		}

		// if we have cloned the post thumbnail, set the copy as the thumbnail for the new post.
		if ( intval( get_option( 'duplicate_post_copythumbnail' ) ) === 1 && $old_thumbnail_id === $child->ID ) {
			set_post_thumbnail( $new_id, $new_attachment_id );
		}
	}
}

/**
 * Copies child posts.
 *
 * @param integer $new_id The new post ID.
 * @param WP_Post $post The original post object.
 * @param string  $status Optional. The destination status.
 */
function duplicate_post_copy_children( $new_id, $post, $status = '' ) {
	// get children.
	$children = get_posts(
		array(
			'post_type'   => 'any',
			'numberposts' => - 1,
			'post_status' => 'any',
			'post_parent' => $post->ID,
		)
	);

	foreach ( $children as $child ) {
		if ( 'attachment' === $child->post_type ) {
			continue;
		}
		duplicate_post_create_duplicate( $child, $status, $new_id );
	}
}

/**
 * Copies comments.
 *
 * @param integer $new_id The new post ID.
 * @param WP_Post $post The original post object.
 */
function duplicate_post_copy_comments( $new_id, $post ) {
	$comments = get_comments(
		array(
			'post_id' => $post->ID,
			'order'   => 'ASC',
			'orderby' => 'comment_date_gmt',
		)
	);

	$old_id_to_new = array();
	foreach ( $comments as $comment ) {
		// do not copy pingbacks or trackbacks.
		if ( ! empty( $comment->comment_type ) ) {
			continue;
		}
		$parent      = ( $comment->comment_parent && $old_id_to_new[ $comment->comment_parent ] ) ? $old_id_to_new[ $comment->comment_parent ] : 0;
		$commentdata = array(
			'comment_post_ID'      => $new_id,
			'comment_author'       => $comment->comment_author,
			'comment_author_email' => $comment->comment_author_email,
			'comment_author_url'   => $comment->comment_author_url,
			'comment_content'      => $comment->comment_content,
			'comment_type'         => '',
			'comment_parent'       => $parent,
			'user_id'              => $comment->user_id,
			'comment_author_IP'    => $comment->comment_author_IP,
			'comment_agent'        => $comment->comment_agent,
			'comment_karma'        => $comment->comment_karma,
			'comment_approved'     => $comment->comment_approved,
		);
		if ( intval( get_option( 'duplicate_post_copydate' ) ) === 1 ) {
			$commentdata['comment_date']     = $comment->comment_date;
			$commentdata['comment_date_gmt'] = get_gmt_from_date( $comment->comment_date );
		}
		$new_comment_id = wp_insert_comment( $commentdata );
		if ( intval( get_option( 'duplicate_post_copycommentmeta' ) ) === 1 ) {
			$commentmeta = get_comment_meta( $new_comment_id );
			foreach ( $commentmeta as $meta_key => $meta_value ) {
				add_comment_meta( $new_comment_id, $meta_key, duplicate_post_wp_slash( $meta_value ) );
			}
		}
		$old_id_to_new[ $comment->comment_ID ] = $new_comment_id;
	}
}

/**
 * Creates a duplicate from a post.
 *
 * This is the main functions that does the cloning.
 *
 * @param WP_Post $post The original post object.
 * @param string  $status Optional. The intended destination status.
 * @param string  $parent_id Optional. The parent post ID if we are calling this recursively.
 * @return number|WP_Error.
 */
function duplicate_post_create_duplicate( $post, $status = '', $parent_id = '' ) {
	/**
	 * Fires before duplicating a post.
	 *
	 * @param WP_Post $post      The original post object.
	 * @param boolean $status    The intended destination status.
	 * @param integer $parent_id The parent post ID if we are calling this recursively.
	 */
	do_action( 'duplicate_post_pre_copy', $post, $status, $parent_id );
	/**
	 * Filter allowing to copy post.
	 *
	 * @param boolean $can_duplicate Default to `true`.
	 * @param WP_Post $post          The original post object.
	 * @param boolean $status        The intended destination status.
	 * @param integer $parent_id     The parent post ID if we are calling this recursively.
	 *
	 * @return boolean.
	 */
	$can_duplicate = apply_filters( 'duplicate_post_allow', true, $post, $status, $parent_id );
	if ( ! $can_duplicate ) {
		wp_die( esc_html( __( 'You aren\'t allowed to duplicate this post', 'duplicate-post' ) ) );
	}

	if ( ! duplicate_post_is_post_type_enabled( $post->post_type ) && 'attachment' !== $post->post_type ) {
		wp_die(
			esc_html(
				__( 'Copy features for this post type are not enabled in options page', 'duplicate-post' ) . ': ' .
				$post->post_type
			)
		);
	}

	$new_post_status = ( empty( $status ) ) ? $post->post_status : $status;
	$title           = ' ';

	if ( 'attachment' !== $post->post_type ) {
		$prefix = sanitize_text_field( get_option( 'duplicate_post_title_prefix' ) );
		$suffix = sanitize_text_field( get_option( 'duplicate_post_title_suffix' ) );
		if ( 1 === intval( get_option( 'duplicate_post_copytitle' ) ) ) {
			$title = $post->post_title;
			if ( ! empty( $prefix ) ) {
				$prefix .= ' ';
			}
			if ( ! empty( $suffix ) ) {
				$suffix = ' ' . $suffix;
			}
		} else {
			$title = ' ';
		}
		$title = trim( $prefix . $title . $suffix );

		/*
		 * Not sure we should force a title. Instead, we should respect what WP does.
		 * if ( '' === $title ) {
		 * 	// empty title.
		 * 	$title = __( 'Untitled', 'default' );
		 * }
		 */

		if ( 0 === intval( get_option( 'duplicate_post_copystatus' ) ) ) {
			$new_post_status = 'draft';
		} else {
			if ( 'publish' === $new_post_status || 'future' === $new_post_status ) {
				// check if the user has the right capability.
				if ( is_post_type_hierarchical( $post->post_type ) ) {
					if ( ! current_user_can( 'publish_pages' ) ) {
						$new_post_status = 'pending';
					}
				} else {
					if ( ! current_user_can( 'publish_posts' ) ) {
						$new_post_status = 'pending';
					}
				}
			}
		}
	}

	$new_post_author    = wp_get_current_user();
	$new_post_author_id = $new_post_author->ID;
	if ( '1' === intval( get_option( 'duplicate_post_copyauthor' ) ) ) {
		// check if the user has the right capability.
		if ( is_post_type_hierarchical( $post->post_type ) ) {
			if ( current_user_can( 'edit_others_pages' ) ) {
				$new_post_author_id = $post->post_author;
			}
		} else {
			if ( current_user_can( 'edit_others_posts' ) ) {
				$new_post_author_id = $post->post_author;
			}
		}
	}

	$menu_order             = ( '1' === intval( get_option( 'duplicate_post_copymenuorder' ) ) ) ? $post->menu_order : 0;
	$increase_menu_order_by = get_option( 'duplicate_post_increase_menu_order_by' );
	if ( ! empty( $increase_menu_order_by ) && is_numeric( $increase_menu_order_by ) ) {
		$menu_order += intval( $increase_menu_order_by );
	}

	$post_name = $post->post_name;
	if ( 1 !== intval( get_option( 'duplicate_post_copyslug' ) ) ) {
		$post_name = '';
	}
	$new_post_parent = empty( $parent_id ) ? $post->post_parent : $parent_id;

	$new_post = array(
		'menu_order'            => $menu_order,
		'comment_status'        => $post->comment_status,
		'ping_status'           => $post->ping_status,
		'post_author'           => $new_post_author_id,
		'post_content'          => ( 1 === intval( get_option( 'duplicate_post_copycontent' ) ) ) ? $post->post_content : '',
		'post_content_filtered' => ( 1 === intval( get_option( 'duplicate_post_copycontent' ) ) ) ? $post->post_content_filtered : '',
		'post_excerpt'          => ( 1 === intval( get_option( 'duplicate_post_copyexcerpt' ) ) ) ? $post->post_excerpt : '',
		'post_mime_type'        => $post->post_mime_type,
		'post_parent'           => $new_post_parent,
		'post_password'         => ( 1 === intval( get_option( 'duplicate_post_copypassword' ) ) ) ? $post->post_password : '',
		'post_status'           => $new_post_status,
		'post_title'            => $title,
		'post_type'             => $post->post_type,
		'post_name'             => $post_name,
	);

	if ( 1 === intval( get_option( 'duplicate_post_copydate' ) ) ) {
		$new_post_date             = $post->post_date;
		$new_post['post_date']     = $new_post_date;
		$new_post['post_date_gmt'] = get_gmt_from_date( $new_post_date );
	}

	/**
	 * Filter new post values.
	 *
	 * @param array   $new_post New post values.
	 * @param WP_Post $post     Original post object.
	 *
	 * @return array.
	 */
	$new_post    = apply_filters( 'duplicate_post_new_post', $new_post, $post );
	$new_post_id = wp_insert_post( wp_slash( $new_post ), true );

	// If you have written a plugin which uses non-WP database tables to save
	// information about a post you can hook this action to dupe that data.
	if ( 0 !== $new_post_id && ! is_wp_error( $new_post_id ) ) {

		if ( 'page' === $post->post_type || is_post_type_hierarchical( $post->post_type ) ) {
			do_action( 'dp_duplicate_page', $new_post_id, $post, $status );
		} else {
			do_action( 'dp_duplicate_post', $new_post_id, $post, $status );
		}

		delete_post_meta( $new_post_id, '_dp_original' );
		add_post_meta( $new_post_id, '_dp_original', $post->ID );
	}

	/**
	 * Fires after duplicating a post.
	 *
	 * @param integer|WP_Error $new_post_id The new post id or WP_Error object on error.
	 * @param WP_Post          $post        The original post object.
	 * @param boolean          $status      The intended destination status.
	 * @param integer          $parent_id   The parent post ID if we are calling this recursively.
	 */
	do_action( 'duplicate_post_post_copy', $new_post_id, $post, $status, $parent_id );

	return $new_post_id;
}

/**
 * Adds some links on the plugin page.
 *
 * @param array  $links The links array.
 * @param string $file The file name.
 * @return array.
 */
function duplicate_post_add_plugin_links( $links, $file ) {
	if ( plugin_basename( dirname( __FILE__ ) . '/duplicate-post.php' ) === $file ) {
		$links[] = '<a href="https://yoast.com/wordpress/plugins/duplicate-post">' . esc_html__( 'Documentation', 'duplicate-post' ) . '</a>';
	}
	return $links;
}

/*** NOTICES */

/**
 * Shows a notice after the copy has succeeded.
 *
 * @ignore
 */
function duplicate_post_action_admin_notice() {
	if ( ! empty( $_REQUEST['cloned'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$copied_posts = intval( $_REQUEST['cloned'] ); // phpcs:ignore WordPress.Security.NonceVerification
		printf(
			'<div id="message" class="notice notice-success fade"><p>' .
				esc_html(
					/* translators: %s: Number of posts copied. */
					_n(
						'%s item copied.',
						'%s items copied.',
						$copied_posts,
						'duplicate-post'
					)
				) . '</p></div>',
			esc_html( $copied_posts )
		);
		remove_query_arg( 'cloned' );
	}
}

/*** BULK ACTIONS */

add_action( 'admin_init', 'duplicate_post_add_bulk_filters_for_enabled_post_types' );

/**
 * Adds the handlers for bulk actions.
 *
 * @ignore
 */
function duplicate_post_add_bulk_filters_for_enabled_post_types() {
	if ( 1 !== intval( get_option( 'duplicate_post_show_bulkactions' ) ) ) {
		return;
	}
	$duplicate_post_types_enabled = get_option( 'duplicate_post_types_enabled', array( 'post', 'page' ) );
	if ( ! is_array( $duplicate_post_types_enabled ) ) {
		$duplicate_post_types_enabled = array( $duplicate_post_types_enabled );
	}
	foreach ( $duplicate_post_types_enabled as $duplicate_post_type_enabled ) {
		add_filter( "bulk_actions-edit-{$duplicate_post_type_enabled}", 'duplicate_post_register_bulk_action' );
		add_filter( "handle_bulk_actions-edit-{$duplicate_post_type_enabled}", 'duplicate_post_action_handler', 10, 3 );
	}
}

/**
 * Adds 'Clone' to the bulk action dropdown.
 *
 * @ignore
 *
 * @param array $bulk_actions The bulk actions array.
 * @return array.
 */
function duplicate_post_register_bulk_action( $bulk_actions ) {
	$bulk_actions['duplicate_post_clone'] = esc_html__( 'Clone', 'duplicate-post' );
	return $bulk_actions;
}

/**
 * Bulk action handler.
 *
 * @ignore
 *
 * @param string $redirect_to The URL to redirect to.
 * @param string $doaction The action that has been called.
 * @param array  $post_ids The array of marked post IDs.
 * @return string.
 */
function duplicate_post_action_handler( $redirect_to, $doaction, $post_ids ) {
	if ( 'duplicate_post_clone' !== $doaction ) {
		return $redirect_to;
	}
	$counter = 0;
	foreach ( $post_ids as $post_id ) {
		$post = get_post( $post_id );
		if ( ! empty( $post ) ) {
			if ( 1 !== intval( get_option( 'duplicate_post_copychildren' ) )
					|| ! is_post_type_hierarchical( $post->post_type )
					|| ( is_post_type_hierarchical( $post->post_type ) && ! duplicate_post_has_ancestors_marked( $post, $post_ids ) )
				) {
				if ( ! is_wp_error( duplicate_post_create_duplicate( $post ) ) ) {
					$counter++;
				}
			}
		}
	}
	$redirect_to = add_query_arg( 'cloned', $counter, $redirect_to );
	return $redirect_to;
}

/**
 * Checks if the post has ancestors marked for copy.
 *
 * If we are copying children, and the post has already an ancestor marked for copy, we have to filter it out.
 *
 * @ignore
 *
 * @param WP_Post $post The post object.
 * @param array   $post_ids The array of marked post IDs.
 * @return boolean.
 */
function duplicate_post_has_ancestors_marked( $post, $post_ids ) {
	$ancestors_in_array = 0;
	$parent             = wp_get_post_parent_id( $post->ID );
	while ( $parent ) {
		if ( in_array( $parent, $post_ids, true ) ) {
			$ancestors_in_array++;
		}
		$parent = wp_get_post_parent_id( $parent );
	}
	return ( 0 !== $ancestors_in_array );
}

/**
 * Renders the newsletter signup form.
 *
 * @return string The HTML of the newsletter signup form (escaped).
 */
function duplicate_post_newsletter_signup_form() {
	$copy = sprintf(
		/* translators: 1: Yoast */
		__(
			'If you want to stay up to date about all the exciting developments around duplicate post, subscribe to the %1$s newsletter!',
			'duplicate-post'
		),
		'Yoast'
	);

	$email_label = __( 'Email Address', 'duplicate-post' );

	$html = '
<!-- Begin Mailchimp Signup Form -->
<div id="mc_embed_signup">
<form action="https://yoast.us1.list-manage.com/subscribe/post?u=ffa93edfe21752c921f860358&amp;id=972f1c9122" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
    <div id="mc_embed_signup_scroll">
	' . $copy . '
<div class="mc-field-group" style="margin-top: 8px;">
	<label for="mce-EMAIL">' . $email_label . '</label>
	<input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL">
	<input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button">
</div>
	<div id="mce-responses" class="clear">
		<div class="response" id="mce-error-response" style="display:none"></div>
		<div class="response" id="mce-success-response" style="display:none"></div>
	</div>    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
    <div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_ffa93edfe21752c921f860358_972f1c9122" tabindex="-1" value=""></div>
    </div>
</form>
</div>
<!--End mc_embed_signup-->
';

	return $html;
}
