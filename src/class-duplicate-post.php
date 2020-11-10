<?php
/**
 * Duplicate Post main class.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post;

/**
 * Represents the Duplicate Post main class.
 */
class Duplicate_Post {

	/**
	 * Initializes the main class.
	 */
	public function __construct() {

		// Handle the user interface.
		new Duplicate_Post_User_Interface();
	}

	/**
	 * @param \WP_Post $post
	 * @param array $options
	 *
	 * @return number|\WP_Error.
	 */
	public function create_duplicate( \WP_Post $post, array $options ) {

		$defaults = [
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
		];

		$options = \wp_parse_args( $options, $defaults );

		$title = '';

		if ( 'attachment' !== $post->post_type ) {
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
			} else {
				$title = ' ';
			}
			$title = \trim( $prefix . $title . $suffix );

			if ( ! $options['copy_status'] ) {
				$new_post_status = 'draft';
			} else {
				$new_post_status = $post->post_status;
				if ( $new_post_status === 'publish' || $new_post_status === 'future' ) {
					// check if the user has the right capability.
					if ( \is_post_type_hierarchical( $post->post_type ) ) {
						if ( ! \current_user_can( 'publish_pages' ) ) {
							$new_post_status = 'pending';
						}
					} else {
						if ( ! \current_user_can( 'publish_posts' ) ) {
							$new_post_status = 'pending';
						}
					}
				}
			}
		}

		$new_post_author    = \wp_get_current_user();
		$new_post_author_id = $new_post_author->ID;
		if ( $options['copy_author'] ) {
			// check if the user has the right capability.
			if ( \is_post_type_hierarchical( $post->post_type ) ) {
				if ( \current_user_can( 'edit_others_pages' ) ) {
					$new_post_author_id = $post->post_author;
				}
			} else {
				if ( \current_user_can( 'edit_others_posts' ) ) {
					$new_post_author_id = $post->post_author;
				}
			}
		}

		$menu_order = $options['copy_menu_order'] ? $post->menu_order : 0;
		if ( ! empty( $options['increase_menu_order_by'] ) && is_numeric( $options['increase_menu_order_by'] ) ) {
			$menu_order += intval( $options['increase_menu_order_by'] );
		}

		$new_post = array(
			'post_author'           => $new_post_author_id,
			'post_content'          => $options['copy_content'] ? $post->post_content : '',
			'post_content_filtered' => $options['copy_content'] ? $post->post_content_filtered : '',
			'post_title'            => $title,
			'post_excerpt'          => $options['copy_excerpt'] ? $post->post_excerpt : '',
			'post_status'           => $new_post_status,
			'post_type'             => $post->post_type,
			'comment_status'        => $post->comment_status,
			'ping_status'           => $post->ping_status,
			'post_password'         => $options['copy_password'] ? $post->post_password : '',
			'post_name'             => $options['copy_name'] ? $post->post_name : '',
			'post_parent'           => empty( $options['parent_id'] ) ? $post->post_parent : $options['parent_id'],
			'menu_order'            => $menu_order,
			'post_mime_type'        => $post->post_mime_type,
		);

		if ( $options['copy_date'] ) {
			$new_post_date             = $post->post_date;
			$new_post['post_date']     = $new_post_date;
			$new_post['post_date_gmt'] = \get_gmt_from_date( $new_post_date );
		}

		/**
		 * Filter new post values.
		 *
		 * @param array   $new_post New post values.
		 * @param \WP_Post $post     Original post object.
		 *
		 * @return array.
		 */
		$new_post    = \apply_filters( 'duplicate_post_new_post', $new_post, $post );
		$new_post_id = \wp_insert_post( \wp_slash( $new_post ), true );

		if ( ! \is_wp_error( $new_post_id ) ) {
			\delete_post_meta( $new_post_id, '_dp_original' );
			\add_post_meta( $new_post_id, '_dp_original', $post->ID );
		}

		return $new_post_id;
	}

	public function create_duplicate_for_rewrite_and_republish( \WP_Post $post ) {
		$options = [
			'copy_title'      => true,
			'copy_date'       => true,
			'copy_name'       => true,
			'copy_content'    => true,
			'copy_excerpt'    => true,
			'copy_author'     => true,
			'copy_menu_order' => true,
		];

		return $this->create_duplicate( $post, $options );
	}
}
