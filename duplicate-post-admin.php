<?php
/**
 * Backend functions.
 *
 * @package Duplicate Post
 * @since   2.0
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

	if ( intval( get_site_option( 'duplicate_post_show_notice' ) ) === 1 ) {
		if ( is_multisite() ) {
			add_action( 'network_admin_notices', 'duplicate_post_show_update_notice' );
		} else {
			add_action( 'admin_notices', 'duplicate_post_show_update_notice' );
		}
		add_action( 'wp_ajax_duplicate_post_dismiss_notice', 'duplicate_post_dismiss_notice' );
	}

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
			'editor',
			'administrator',
			'wpseo_manager',
			'wpseo_editor',
		);

		foreach ( $default_roles as $name ) {
			$role = get_role( $name );
			if ( ! empty( $role ) ) {
				$role->add_cap( 'copy_posts' );
			}
		}
	}

	$show_links_in_defaults = [
		'row'         => '1',
		'adminbar'    => '1',
		'submitbox'   => '1',
		'bulkactions' => '1',
	];

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
	add_option( 'duplicate_post_show_original_column', '0' );
	add_option( 'duplicate_post_show_original_in_post_states', '0' );
	add_option( 'duplicate_post_show_original_meta_box', '0' );
	add_option(
		'duplicate_post_show_link',
		[
			'new_draft'         => '1',
			'clone'             => '1',
			'rewrite_republish' => '1',
		]
	);
	add_option( 'duplicate_post_show_link_in', $show_links_in_defaults );

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

	delete_option( 'duplicate_post_show_notice' );
	if ( version_compare( $installed_version, '4.1.0' ) < 0 ) {
		update_site_option( 'duplicate_post_show_notice', 1 );
	}

	// Migrate the 'Show links in' options to the new array-based structure.
	duplicate_post_migrate_show_links_in_options( $show_links_in_defaults );

	delete_site_option( 'duplicate_post_version' );
	update_option( 'duplicate_post_version', duplicate_post_get_current_version() );
}

/**
 * Runs the upgrade routine for version 4.0 to update the options in the database.
 *
 * @param array $defaults The default options to fall back on.
 *
 * @return void
 */
function duplicate_post_migrate_show_links_in_options( $defaults ) {
	$options_to_migrate = [
		'duplicate_post_show_row'         => 'row',
		'duplicate_post_show_adminbar'    => 'adminbar',
		'duplicate_post_show_submitbox'   => 'submitbox',
		'duplicate_post_show_bulkactions' => 'bulkactions',
	];

	$new_options = [];
	foreach ( $options_to_migrate as $old => $new ) {
		$new_options[ $new ] = \get_option( $old, $defaults[ $new ] );

		\delete_option( $old );
	}

	\update_option( 'duplicate_post_show_link_in', $new_options );
}

/**
 * Shows the update notice.
 *
 * @global string $wp_version The WordPress version string.
 */
function duplicate_post_show_update_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$current_screen = get_current_screen();
	if (
		empty( $current_screen ) ||
		empty( $current_screen->base ) ||
		( $current_screen->base !== 'dashboard' && $current_screen->base !== 'plugins' )
	) {
		return;
	}

	$class   = 'notice is-dismissible';
	$message = '<p><strong>' . sprintf(
		/* translators: %s: Yoast Duplicate Post version. */
		__( "What's new in Yoast Duplicate Post version %s:", 'duplicate-post' ),
		DUPLICATE_POST_CURRENT_VERSION
	) . '</strong> ';
	$message .= __( 'Now also available in Elementor: the powerful Rewrite & Republish feature. Updating your content has never been easier!', 'duplicate-post' )
		. ' ';

	$message .= '<a href="https://yoa.st/duplicate-post-4-1">'
				. sprintf(
					/* translators: %s: Yoast Duplicate Post version. */
					__( 'Read more about what’s new in Yoast Duplicate Post %s!', 'duplicate-post' ),
					DUPLICATE_POST_CURRENT_VERSION
				)
				. '</a></p>';

	$message .= '<p>%%SIGNUP_FORM%%</p>';

	$allowed_tags = array(
		'a'      => array(
			'href'  => array(),
		),
		'br'     => array(),
		'p'      => array(),
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
 * @return bool
 */
function duplicate_post_dismiss_notice() {
	$result = update_site_option( 'duplicate_post_show_notice', 0 );
	return $result;
}

/**
 * Copies the taxonomies of a post to another post.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int     $new_id New post ID.
 * @param WP_Post $post   The original post object.
 */
function duplicate_post_copy_post_taxonomies( $new_id, $post ) {
	global $wpdb;
	if ( isset( $wpdb->terms ) ) {
		// Clear default category (added by wp_insert_post).
		wp_set_object_terms( $new_id, null, 'category' );

		$post_taxonomies = get_object_taxonomies( $post->post_type );
		// Several plugins just add support to post-formats but don't register post_format taxonomy.
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

		/**
		 * Filters the taxonomy excludelist when copying a post.
		 *
		 * @param array $taxonomies_blacklist The taxonomy excludelist from the options.
		 *
		 * @return array
		 */
		$taxonomies_blacklist = apply_filters( 'duplicate_post_taxonomies_excludelist_filter', $taxonomies_blacklist );

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
 * @param int     $new_id The new post ID.
 * @param WP_Post $post   The original post object.
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
	$meta_blacklist[] = '_edit_lock'; // Edit lock.
	$meta_blacklist[] = '_edit_last'; // Edit lock.
	$meta_blacklist[] = '_dp_is_rewrite_republish_copy';
	$meta_blacklist[] = '_dp_has_rewrite_republish_copy';
	if ( intval( get_option( 'duplicate_post_copytemplate' ) ) === 0 ) {
		$meta_blacklist[] = '_wp_page_template';
	}
	if ( intval( get_option( 'duplicate_post_copythumbnail' ) ) === 0 ) {
		$meta_blacklist[] = '_thumbnail_id';
	}

	$meta_blacklist = apply_filters_deprecated( 'duplicate_post_blacklist_filter', array( $meta_blacklist ), '3.2.5', 'duplicate_post_excludelist_filter' );
	/**
	 * Filters the meta fields excludelist when copying a post.
	 *
	 * @param array $meta_blacklist The meta fields excludelist from the options.
	 *
	 * @return array
	 */
	$meta_blacklist = apply_filters( 'duplicate_post_excludelist_filter', $meta_blacklist );

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

	/**
	 * Filters the list of meta fields names when copying a post.
	 *
	 * @param array $meta_keys The list of meta fields name, with the ones in the excludelist already removed.
	 *
	 * @return array
	 */
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
 * @return string|mixed
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
 * @return string|mixed
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
 * @return mixed
 */
function duplicate_post_wp_slash( $value ) {
	return duplicate_post_addslashes_deep( $value );
}

/**
 * Copies attachments, including physical files.
 *
 * @param int     $new_id The new post ID.
 * @param WP_Post $post   The original post object.
 */
function duplicate_post_copy_attachments( $new_id, $post ) {
	// Get thumbnail ID.
	$old_thumbnail_id = get_post_thumbnail_id( $post->ID );
	// Get children.
	$children = get_posts(
		array(
			'post_type'   => 'any',
			'numberposts' => -1,
			'post_status' => 'any',
			'post_parent' => $post->ID,
		)
	);
	// Clone old attachments.
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

		// If we have cloned the post thumbnail, set the copy as the thumbnail for the new post.
		if ( intval( get_option( 'duplicate_post_copythumbnail' ) ) === 1 && $old_thumbnail_id === $child->ID ) {
			set_post_thumbnail( $new_id, $new_attachment_id );
		}
	}
}

/**
 * Copies child posts.
 *
 * @param int     $new_id The new post ID.
 * @param WP_Post $post   The original post object.
 * @param string  $status Optional. The destination status.
 */
function duplicate_post_copy_children( $new_id, $post, $status = '' ) {
	// Get children.
	$children = get_posts(
		array(
			'post_type'   => 'any',
			'numberposts' => -1,
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
 * @param int     $new_id The new post ID.
 * @param WP_Post $post   The original post object.
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
		// Do not copy pingbacks or trackbacks.
		if ( $comment->comment_type === 'pingback' || $comment->comment_type === 'trackback' ) {
			continue;
		}
		$parent      = ( $comment->comment_parent && $old_id_to_new[ $comment->comment_parent ] ) ? $old_id_to_new[ $comment->comment_parent ] : 0;
		$commentdata = array(
			'comment_post_ID'      => $new_id,
			'comment_author'       => $comment->comment_author,
			'comment_author_email' => $comment->comment_author_email,
			'comment_author_url'   => $comment->comment_author_url,
			'comment_content'      => $comment->comment_content,
			'comment_type'         => $comment->comment_type,
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
		$commentmeta    = get_comment_meta( $new_comment_id );
		foreach ( $commentmeta as $meta_key => $meta_value ) {
			add_comment_meta( $new_comment_id, $meta_key, duplicate_post_wp_slash( $meta_value ) );
		}
		$old_id_to_new[ $comment->comment_ID ] = $new_comment_id;
	}
}

/**
 * Creates a duplicate from a post.
 *
 * This is the main functions that does the cloning.
 *
 * @param WP_Post $post      The original post object.
 * @param string  $status    Optional. The intended destination status.
 * @param string  $parent_id Optional. The parent post ID if we are calling this recursively.
 * @return int|WP_Error
 */
function duplicate_post_create_duplicate( $post, $status = '', $parent_id = '' ) {
	/**
	 * Fires before duplicating a post.
	 *
	 * @param WP_Post $post      The original post object.
	 * @param bool    $status    The intended destination status.
	 * @param int     $parent_id The parent post ID if we are calling this recursively.
	 */
	do_action( 'duplicate_post_pre_copy', $post, $status, $parent_id );

	/**
	 * Filter allowing to copy post.
	 *
	 * @param bool    $can_duplicate Default to `true`.
	 * @param WP_Post $post          The original post object.
	 * @param bool    $status        The intended destination status.
	 * @param int     $parent_id     The parent post ID if we are calling this recursively.
	 *
	 * @return bool
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
		if ( intval( get_option( 'duplicate_post_copytitle' ) ) === 1 ) {
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
		 *  // empty title.
		 *  $title = __( 'Untitled', 'default' );
		 * }
		 */

		if ( intval( get_option( 'duplicate_post_copystatus' ) ) === 0 ) {
			$new_post_status = 'draft';
		} else {
			if ( 'publish' === $new_post_status || 'future' === $new_post_status ) {
				// Check if the user has the right capability.
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
	if ( intval( get_option( 'duplicate_post_copyauthor' ) ) === 1 ) {
		// Check if the user has the right capability.
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

	$menu_order             = ( intval( get_option( 'duplicate_post_copymenuorder' ) ) === 1 ) ? $post->menu_order : 0;
	$increase_menu_order_by = get_option( 'duplicate_post_increase_menu_order_by' );
	if ( ! empty( $increase_menu_order_by ) && is_numeric( $increase_menu_order_by ) ) {
		$menu_order += intval( $increase_menu_order_by );
	}

	$post_name = $post->post_name;
	if ( intval( get_option( 'duplicate_post_copyslug' ) ) !== 1 ) {
		$post_name = '';
	}
	$new_post_parent = empty( $parent_id ) ? $post->post_parent : $parent_id;

	$new_post = array(
		'menu_order'            => $menu_order,
		'comment_status'        => $post->comment_status,
		'ping_status'           => $post->ping_status,
		'post_author'           => $new_post_author_id,
		'post_content'          => ( intval( get_option( 'duplicate_post_copycontent' ) ) === 1 ) ? $post->post_content : '',
		'post_content_filtered' => ( intval( get_option( 'duplicate_post_copycontent' ) ) === 1 ) ? $post->post_content_filtered : '',
		'post_excerpt'          => ( intval( get_option( 'duplicate_post_copyexcerpt' ) ) === 1 ) ? $post->post_excerpt : '',
		'post_mime_type'        => $post->post_mime_type,
		'post_parent'           => $new_post_parent,
		'post_password'         => ( intval( get_option( 'duplicate_post_copypassword' ) ) === 1 ) ? $post->post_password : '',
		'post_status'           => $new_post_status,
		'post_title'            => $title,
		'post_type'             => $post->post_type,
		'post_name'             => $post_name,
	);

	if ( intval( get_option( 'duplicate_post_copydate' ) ) === 1 ) {
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
	 * @return array
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
	 * @param int|WP_Error $new_post_id The new post id or WP_Error object on error.
	 * @param WP_Post      $post        The original post object.
	 * @param bool         $status      The intended destination status.
	 * @param int          $parent_id   The parent post ID if we are calling this recursively.
	 */
	do_action( 'duplicate_post_post_copy', $new_post_id, $post, $status, $parent_id );

	return $new_post_id;
}

/**
 * Adds some links on the plugin page.
 *
 * @param array  $links The links array.
 * @param string $file  The file name.
 * @return array
 */
function duplicate_post_add_plugin_links( $links, $file ) {
	if ( plugin_basename( dirname( __FILE__ ) . '/duplicate-post.php' ) === $file ) {
		$links[] = '<a href="https://yoast.com/wordpress/plugins/duplicate-post">' . esc_html__( 'Documentation', 'duplicate-post' ) . '</a>';
	}
	return $links;
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
			'If you want to stay up to date about all the exciting developments around Duplicate Post, subscribe to the %1$s newsletter!',
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
	<input type="submit" value="' . esc_attr__( 'Subscribe', 'duplicate-post' ) . '" name="subscribe" id="mc-embedded-subscribe" class="button">
</div>
	<div id="mce-responses" class="clear">
		<div class="response" id="mce-error-response" style="display:none"></div>
		<div class="response" id="mce-success-response" style="display:none"></div>
	</div>    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
	<div class="screen-reader-text" aria-hidden="true"><input type="text" name="b_ffa93edfe21752c921f860358_972f1c9122" tabindex="-1" value=""></div>
	</div>
</form>
</div>
<!--End mc_embed_signup-->
';

	return $html;
}
