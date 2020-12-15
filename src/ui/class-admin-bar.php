<?php
/**
 * Duplicate Post class to manage the admin bar.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\UI;

use Yoast\WP\Duplicate_Post\Permissions_Helper;
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
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * Initializes the class.
	 *
	 * @param Link_Builder       $link_builder       The link builder.
	 * @param Permissions_Helper $permissions_helper The permissions helper.
	 */
	public function __construct( Link_Builder $link_builder, Permissions_Helper $permissions_helper ) {
		$this->link_builder       = $link_builder;
		$this->permissions_helper = $permissions_helper;
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
		if ( \intval( Utils::get_option( 'duplicate_post_show_link_in', 'adminbar' ) ) === 1 ) {
			\add_action( 'wp_before_admin_bar_render', [ $this, 'admin_bar_render' ] );
			\add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
			\add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		}
	}

	/**
	 * Shows Rewrite & Republish link in the Toolbar.
	 *
	 * @global \WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance.
	 *
	 * @return void
	 */
	public function admin_bar_render() {
		global $wp_admin_bar;

		if ( ! \is_admin_bar_showing() ) {
			return;
		}

		$post = $this->get_current_post();

		if ( ! $post ) {
			return;
		}

		// By default this is false, as we generally always want to show.
		$parent = false;

		if ( \intval( Utils::get_option( 'duplicate_post_show_link', 'new_draft' ) ) === 1 ) {
			$parent = true;
			$wp_admin_bar->add_menu(
				[
					'id'    => 'new_draft',
					'title' => \esc_attr__( 'Copy to a new draft', 'duplicate-post' ),
					'href'  => $this->link_builder->build_new_draft_link( $post ),
				]
			);
		}

		if ( \intval( Utils::get_option( 'duplicate_post_show_link', 'rewrite_republish' ) ) === 1
			&& $post->post_status === 'publish' ) {
			$wp_admin_bar->add_menu(
				[
					'id'     => 'rewrite_republish',
					'parent' => ( $parent ) ? 'new_draft' : false,
					'title'  => \esc_attr__( 'Rewrite & Republish', 'duplicate-post' ),
					'href'   => $this->link_builder->build_rewrite_and_republish_link( $post ),
				]
			);
		}
	}

	/**
	 * Links stylesheet for Toolbar link.
	 *
	 * @global \WP_Query $wp_the_query.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		if ( ! \is_admin_bar_showing() ) {
			return;
		}

		$post = $this->get_current_post();

		if ( ! $post ) {
			return;
		}

		\wp_enqueue_style( 'duplicate-post' );
	}

	/**
	 * Returns the current post object (both if it's displayed or being edited).
	 *
	 * @global \WP_Query $wp_the_query
	 *
	 * @return false|\WP_Post The Post object, false if we are not on a post.
	 */
	public function get_current_post() {
		global $wp_the_query;

		if ( \is_admin() ) {
			$post = \get_post();
		} else {
			$post = $wp_the_query->get_queried_object();
		}

		if ( empty( $post ) || ! \is_a( $post, '\WP_Post' ) ) {
			return false;
		}

		$show_duplicate_link = $this->permissions_helper->should_link_be_displayed( $post )
							&& $this->permissions_helper->is_valid_post_edit_screen()
							&& $this->permissions_helper->post_type_has_admin_bar( $post->post_type );

		/** This filter is documented in class-row-actions.php */
		if ( ! \apply_filters( 'duplicate_post_show_link', $show_duplicate_link, $post ) ) {
			return false;
		}

		return $post;
	}
}
