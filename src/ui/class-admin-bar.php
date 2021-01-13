<?php
/**
 * Duplicate Post class to manage the admin bar.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\UI;

use WP_Post;
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
	 * Holds the asset manager.
	 *
	 * @var Asset_Manager
	 */
	protected $asset_manager;

	/**
	 * Initializes the class.
	 *
	 * @param Link_Builder       $link_builder       The link builder.
	 * @param Permissions_Helper $permissions_helper The permissions helper.
	 * @param Asset_Manager      $asset_manager      The asset manager.
	 */
	public function __construct( Link_Builder $link_builder, Permissions_Helper $permissions_helper, Asset_Manager $asset_manager ) {
		$this->link_builder       = $link_builder;
		$this->permissions_helper = $permissions_helper;
		$this->asset_manager      = $asset_manager;
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

		$show_new_draft             = ( \intval( Utils::get_option( 'duplicate_post_show_link', 'new_draft' ) ) === 1 );
		$show_rewrite_and_republish = ( \intval( Utils::get_option( 'duplicate_post_show_link', 'rewrite_republish' ) ) === 1 )
									&& $this->permissions_helper->should_rewrite_and_republish_be_allowed( $post );

		if ( $show_new_draft && $show_rewrite_and_republish ) {
			$wp_admin_bar->add_menu(
				[
					'id'    => 'duplicate-post',
					'title' => '<span class="ab-icon"></span><span class="ab-label">' . \__( 'Duplicate Post', 'duplicate-post' ) . '</span>',
					'href'  => $this->link_builder->build_new_draft_link( $post ),
				]
			);
			$wp_admin_bar->add_menu(
				[
					'id'     => 'new-draft',
					'parent' => 'duplicate-post',
					'title'  => \__( 'Copy to a new draft', 'duplicate-post' ),
					'href'   => $this->link_builder->build_new_draft_link( $post ),
				]
			);
			$wp_admin_bar->add_menu(
				[
					'id'     => 'rewrite-republish',
					'parent' => 'duplicate-post',
					'title'  => \__( 'Rewrite & Republish', 'duplicate-post' ),
					'href'   => $this->link_builder->build_rewrite_and_republish_link( $post ),
				]
			);
		} else {
			if ( $show_new_draft ) {
				$wp_admin_bar->add_menu(
					[
						'id'     => 'new-draft',
						'title'  => '<span class="ab-icon"></span><span class="ab-label">' . \__( 'Copy to a new draft', 'duplicate-post' ) . '</span>',
						'href'   => $this->link_builder->build_new_draft_link( $post ),
					]
				);
			}

			if ( $show_rewrite_and_republish ) {
				$wp_admin_bar->add_menu(
					[
						'id'     => 'rewrite-republish',
						'title'  => '<span class="ab-icon"></span><span class="ab-label">' . \__( 'Rewrite & Republish', 'duplicate-post' ) . '</span>',
						'href'   => $this->link_builder->build_rewrite_and_republish_link( $post ),
					]
				);
			}
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

		$this->asset_manager->enqueue_styles();
	}

	/**
	 * Returns the current post object (both if it's displayed or being edited).
	 *
	 * @global \WP_Query $wp_the_query
	 *
	 * @return false|WP_Post The Post object, false if we are not on a post.
	 */
	public function get_current_post() {
		global $wp_the_query;

		if ( \is_admin() ) {
			$post = \get_post();
		} else {
			$post = $wp_the_query->get_queried_object();
		}

		if ( empty( $post ) || ! $post instanceof WP_Post ) {
			return false;
		}

		if (
			( ! $this->permissions_helper->is_edit_post_screen() && ! \is_singular( $post->post_type ) )
			|| ! $this->permissions_helper->post_type_has_admin_bar( $post->post_type )
		) {
			return false;
		}

		if ( ! $this->permissions_helper->should_links_be_displayed( $post ) ) {
			return false;
		}

		return $post;
	}
}
