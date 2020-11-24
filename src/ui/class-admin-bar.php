<?php
/**
 * Duplicate Post class to manage the admin bar.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\UI;

use Yoast\WP\Duplicate_Post\Utils;

/**
 * Represents the Admin_Bar class.
 */
class Admin_Bar {

	/**
	 * Holds the object to create the action link to duplicate.
	 *
	 * @var Link_Builder
	 */
	protected $link_builder;

	/**
	 * Initializes the class.
	 *
	 * @param Link_Builder $link_builder The link builder.
	 */
	public function __construct( Link_Builder $link_builder ) {
		$this->link_builder = $link_builder;

		$this->register_hooks();
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_action( 'wp_before_admin_bar_render', [ $this, 'admin_bar_render' ] );
	}

	/**
	 * Shows Rewrite & Republish link in the Toolbar.
	 *
	 * @global \WP_Query     $wp_the_query
	 * @global \WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance.
	 */
	public function admin_bar_render() {
		global $wp_the_query;
		global $wp_admin_bar;

		if ( \intval( \get_option( 'duplicate_post_show_adminbar' ) ) !== 1 ) {
			return;
		}

		$post = null;

		if ( \is_admin() ) {
			$post = \get_post();
		} else {
			$post = $wp_the_query->get_queried_object();
		}

		if ( empty( $post ) ) {
			return;
		}

		$show_duplicate_link = Utils::is_current_user_allowed_to_copy()
								&& Utils::is_post_type_enabled( $post->post_type )
								&& ! Utils::is_rewrite_and_republish_copy( $post )
								&& Utils::is_valid_post_edit_screen()
								&& Utils::can_copy_to_draft( $post );

		/** This filter is documented in class-row-actions.php */
		if ( ! apply_filters( 'duplicate_post_show_link', $show_duplicate_link, $post ) ) {
			return;
		}

		$wp_admin_bar->add_menu(
			[
				'id'    => 'new_draft',
				'title' => \esc_attr__( 'Copy to a new draft', 'duplicate-post' ),
				'href'  => $this->link_builder->build_new_draft_link( $post ),
			]
		);

		if ( $post->post_status === 'publish' ) {
			$wp_admin_bar->add_menu(
				[
					'id'     => 'rewrite_republish',
					'parent' => 'new_draft',
					'title'  => \esc_attr__( 'Rewrite & Republish', 'duplicate-post' ),
					'href'   => $this->link_builder->build_rewrite_and_republish_link( $post ),
				]
			);
		}
	}
}
