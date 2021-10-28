<?php

namespace Yoast\WP\Duplicate_Post;

use WP_Error;
use WP_Post;

/**
 * Duplicate Post class to create copies.
 *
 * @since 4.0
 */
class Post_Duplicator {

	/**
	 * Returns an array with the default option values.
	 *
	 * @return array The default options values.
	 */
	public function get_default_options() {
		return [
			'copy_title'             => true,
			'copy_date'              => false,
			'copy_status'            => false,
			'copy_name'              => false,
			'copy_excerpt'           => true,
			'copy_content'           => true,
			'copy_thumbnail'         => true,
			'copy_template'          => true,
			'copy_format'            => true,
			'copy_author'            => false,
			'copy_password'          => false,
			'copy_attachments'       => false,
			'copy_children'          => false,
			'copy_comments'          => false,
			'copy_menu_order'        => true,
			'title_prefix'           => '',
			'title_suffix'           => '',
			'increase_menu_order_by' => null,
			'parent_id'              => null,
			'meta_excludelist'       => [],
			'taxonomies_excludelist' => [],
			'use_filters'            => true,
		];
	}

	/**
	 * Creates a copy of a post object, accordingly to an options array.
	 *
	 * @param WP_Post $post    The original post object.
	 * @param array   $options The options overriding the default ones.
	 *
	 * @return int|WP_Error The copy ID, or a WP_Error object on failure.
	 */
	public function create_duplicate( WP_Post $post, array $options = [] ) {
		$defaults = $this->get_default_options();
		$options  = \wp_parse_args( $options, $defaults );

		$title           = '';
		$new_post_status = $post->post_status;
		if ( $post->post_type !== 'attachment' ) {
			$title           = $this->generate_copy_title( $post, $options );
			$new_post_status = $this->generate_copy_status( $post, $options );
		}

		$new_post_author_id = $this->generate_copy_author( $post, $options );

		$menu_order = 0;
		if ( $options['copy_menu_order'] ) {
			$menu_order = $post->menu_order;
		}

		if ( ! empty( $options['increase_menu_order_by'] ) && \is_numeric( $options['increase_menu_order_by'] ) ) {
			$menu_order += \intval( $options['increase_menu_order_by'] );
		}

		$new_post = [
			'post_author'           => $new_post_author_id,
			'post_content'          => ( $options['copy_content'] ) ? $post->post_content : '',
			'post_content_filtered' => ( $options['copy_content'] ) ? $post->post_content_filtered : '',
			'post_title'            => $title,
			'post_excerpt'          => ( $options['copy_excerpt'] ) ? $post->post_excerpt : '',
			'post_status'           => $new_post_status,
			'post_type'             => $post->post_type,
			'comment_status'        => $post->comment_status,
			'ping_status'           => $post->ping_status,
			'post_password'         => ( $options['copy_password'] ) ? $post->post_password : '',
			'post_name'             => ( $options['copy_name'] ) ? $post->post_name : '',
			'post_parent'           => empty( $options['parent_id'] ) ? $post->post_parent : $options['parent_id'],
			'menu_order'            => $menu_order,
			'post_mime_type'        => $post->post_mime_type,
		];

		if ( $options['copy_date'] ) {
			$new_post_date             = $post->post_date;
			$new_post['post_date']     = $new_post_date;
			$new_post['post_date_gmt'] = \get_gmt_from_date( $new_post_date );
			\add_filter( 'wp_insert_post_data', [ $this, 'set_modified' ], 1, 1 );
		}

		if ( $options['use_filters'] ) {
			/**
			 * Filter new post values.
			 *
			 * @param array   $new_post New post values.
			 * @param WP_Post $post     Original post object.
			 *
			 * @return array
			 */
			$new_post = \apply_filters( 'duplicate_post_new_post', $new_post, $post );
		}

		$new_post_id = \wp_insert_post( \wp_slash( $new_post ), true );

		if ( $options['copy_date'] ) {
			\remove_filter( 'wp_insert_post_data', [ $this, 'set_modified' ], 1 );
		}

		if ( ! \is_wp_error( $new_post_id ) ) {
			\delete_post_meta( $new_post_id, '_dp_original' );
			\add_post_meta( $new_post_id, '_dp_original', $post->ID );
		}

		return $new_post_id;
	}

	/**
	 * Modifies the post data to set the modified date to now.
	 *
	 * This is needed for the Block editor when a post is copied with its date,
	 * so that the current publish date is shown instead of "Immediately".
	 *
	 * @param array $data The array of post data.
	 *
	 * @return array The updated array of post data.
	 */
	public function set_modified( $data ) {
		$data['post_modified']     = \current_time( 'mysql' );
		$data['post_modified_gmt'] = \current_time( 'mysql', 1 );

		return $data;
	}

	/**
	 * Wraps the function to create a copy for the Rewrite & Republish feature.
	 *
	 * @param WP_Post $post The original post object.
	 *
	 * @return int|WP_Error The copy ID, or a WP_Error object on failure.
	 */
	public function create_duplicate_for_rewrite_and_republish( WP_Post $post ) {
		$options  = [
			'copy_title'      => true,
			'copy_date'       => true,
			'copy_name'       => false,
			'copy_content'    => true,
			'copy_excerpt'    => true,
			'copy_author'     => true,
			'copy_menu_order' => true,
			'use_filters'     => false,
		];
		$defaults = $this->get_default_options();
		$options  = \wp_parse_args( $options, $defaults );

		$new_post_id = $this->create_duplicate( $post, $options );

		if ( ! \is_wp_error( $new_post_id ) ) {
			$this->copy_post_taxonomies( $new_post_id, $post, $options );
			$this->copy_post_meta_info( $new_post_id, $post, $options );

			\update_post_meta( $new_post_id, '_dp_is_rewrite_republish_copy', 1 );
			\update_post_meta( $post->ID, '_dp_has_rewrite_republish_copy', $new_post_id );
			\update_post_meta( $new_post_id, '_dp_creation_date_gmt', \current_time( 'mysql', 1 ) );
		}

		return $new_post_id;
	}

	/**
	 * Copies the taxonomies of a post to another post.
	 *
	 * @param int     $new_id  New post ID.
	 * @param WP_Post $post    The original post object.
	 * @param array   $options The options array.
	 *
	 * @return void
	 */
	public function copy_post_taxonomies( $new_id, $post, $options ) {
		// Clear default category (added by wp_insert_post).
		\wp_set_object_terms( $new_id, null, 'category' );

		$post_taxonomies = \get_object_taxonomies( $post->post_type );
		// Several plugins just add support to post-formats but don't register post_format taxonomy.
		if ( \post_type_supports( $post->post_type, 'post-formats' ) && ! \in_array( 'post_format', $post_taxonomies, true ) ) {
			$post_taxonomies[] = 'post_format';
		}

		$taxonomies_excludelist = $options['taxonomies_excludelist'];
		if ( ! \is_array( $taxonomies_excludelist ) ) {
			$taxonomies_excludelist = [];
		}

		if ( ! $options['copy_format'] ) {
			$taxonomies_excludelist[] = 'post_format';
		}

		if ( $options['use_filters'] ) {
			/**
			 * Filters the taxonomy excludelist when copying a post.
			 *
			 * @param array $taxonomies_excludelist The taxonomy excludelist from the options.
			 *
			 * @return array
			 */
			$taxonomies_excludelist = \apply_filters( 'duplicate_post_taxonomies_excludelist_filter', $taxonomies_excludelist );
		}

		$post_taxonomies = \array_diff( $post_taxonomies, $taxonomies_excludelist );

		foreach ( $post_taxonomies as $taxonomy ) {
			$post_terms = \wp_get_object_terms( $post->ID, $taxonomy, [ 'orderby' => 'term_order' ] );
			$terms      = [];
			$num_terms  = \count( $post_terms );
			for ( $i = 0; $i < $num_terms; $i++ ) {
				$terms[] = $post_terms[ $i ]->slug;
			}
			\wp_set_object_terms( $new_id, $terms, $taxonomy );
		}
	}

	/**
	 * Copies the meta information of a post to another post.
	 *
	 * @param int     $new_id  The new post ID.
	 * @param WP_Post $post    The original post object.
	 * @param array   $options The options array.
	 *
	 * @return void
	 */
	public function copy_post_meta_info( $new_id, $post, $options ) {
		$post_meta_keys = \get_post_custom_keys( $post->ID );
		if ( empty( $post_meta_keys ) ) {
			return;
		}
		$meta_excludelist = $options['meta_excludelist'];
		if ( ! \is_array( $meta_excludelist ) ) {
			$meta_excludelist = [];
		}
		$meta_excludelist = \array_merge( $meta_excludelist, Utils::get_default_filtered_meta_names() );
		if ( ! $options['copy_template'] ) {
			$meta_excludelist[] = '_wp_page_template';
		}
		if ( ! $options['copy_thumbnail'] ) {
			$meta_excludelist[] = '_thumbnail_id';
		}

		if ( $options['use_filters'] ) {
			/**
			 * Filters the meta fields excludelist when copying a post.
			 *
			 * @param array $meta_excludelist The meta fields excludelist from the options.
			 *
			 * @return array
			 */
			$meta_excludelist = \apply_filters( 'duplicate_post_excludelist_filter', $meta_excludelist );
		}

		$meta_excludelist_string = '(' . \implode( ')|(', $meta_excludelist ) . ')';
		if ( \strpos( $meta_excludelist_string, '*' ) !== false ) {
			$meta_excludelist_string = \str_replace( [ '*' ], [ '[a-zA-Z0-9_]*' ], $meta_excludelist_string );

			$meta_keys = [];
			foreach ( $post_meta_keys as $meta_key ) {
				if ( ! \preg_match( '#^' . $meta_excludelist_string . '$#', $meta_key ) ) {
					$meta_keys[] = $meta_key;
				}
			}
		}
		else {
			$meta_keys = \array_diff( $post_meta_keys, $meta_excludelist );
		}

		if ( $options['use_filters'] ) {
			/**
			 * Filters the list of meta fields names when copying a post.
			 *
			 * @param array $meta_keys The list of meta fields name, with the ones in the excludelist already removed.
			 *
			 * @return array
			 */
			$meta_keys = \apply_filters( 'duplicate_post_meta_keys_filter', $meta_keys );
		}

		foreach ( $meta_keys as $meta_key ) {
			$meta_values = \get_post_custom_values( $meta_key, $post->ID );

			// Clear existing meta data so that add_post_meta() works properly with non-unique keys.
			\delete_post_meta( $new_id, $meta_key );

			foreach ( $meta_values as $meta_value ) {
				$meta_value = \maybe_unserialize( $meta_value );
				\add_post_meta( $new_id, $meta_key, Utils::recursively_slash_strings( $meta_value ) );
			}
		}
	}

	/**
	 * Generates and returns the title for the copy.
	 *
	 * @param WP_Post $post    The original post object.
	 * @param array   $options The options array.
	 *
	 * @return string The calculated title for the copy.
	 */
	public function generate_copy_title( WP_Post $post, array $options ) {
		$prefix = \sanitize_text_field( $options['title_prefix'] );
		$suffix = \sanitize_text_field( $options['title_suffix'] );
		if ( $options['copy_title'] ) {
			$title = $post->post_title;
			if ( ! empty( $prefix ) ) {
				$prefix .= ' ';
			}
			if ( ! empty( $suffix ) ) {
				$suffix = ' ' . $suffix;
			}
		}
		else {
			$title = '';
		}
		return \trim( $prefix . $title . $suffix );
	}

	/**
	 * Generates and returns the status for the copy.
	 *
	 * @param WP_Post $post    The original post object.
	 * @param array   $options The options array.
	 *
	 * @return string The calculated status for the copy.
	 */
	public function generate_copy_status( WP_Post $post, array $options ) {
		$new_post_status = 'draft';

		if ( $options['copy_status'] ) {
			$new_post_status = $post->post_status;
			if ( $new_post_status === 'publish' || $new_post_status === 'future' ) {
				// Check if the user has the right capability.
				if ( \is_post_type_hierarchical( $post->post_type ) ) {
					if ( ! \current_user_can( 'publish_pages' ) ) {
						$new_post_status = 'pending';
					}
				}
				elseif ( ! \current_user_can( 'publish_posts' ) ) {
					$new_post_status = 'pending';
				}
			}
		}

		return $new_post_status;
	}

	/**
	 * Generates and returns the author ID for the copy.
	 *
	 * @param WP_Post $post    The original post object.
	 * @param array   $options The options array.
	 *
	 * @return int|string The calculated author ID for the copy.
	 */
	public function generate_copy_author( WP_Post $post, array $options ) {
		$new_post_author    = \wp_get_current_user();
		$new_post_author_id = $new_post_author->ID;
		if ( $options['copy_author'] ) {
			// Check if the user has the right capability.
			if ( \is_post_type_hierarchical( $post->post_type ) ) {
				if ( \current_user_can( 'edit_others_pages' ) ) {
					$new_post_author_id = $post->post_author;
				}
			}
			elseif ( \current_user_can( 'edit_others_posts' ) ) {
				$new_post_author_id = $post->post_author;
			}
		}

		return $new_post_author_id;
	}
}
