<?php
/**
 * Duplicate Post class to manage the block editor UI.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\UI;

use WP_Post;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Utils;

/**
 * Represents the Block_Editor class.
 */
class Block_Editor {

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
		\add_action( 'admin_enqueue_scripts', [ $this, 'should_previously_used_keyword_assessment_run' ], 9 );
		\add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_scripts' ] );
	}

	/**
	 * Disables the Yoast SEO PreviouslyUsedKeyword assessment for Rewrite & Republish original and duplicate posts.
	 *
	 * @return void
	 */
	public function should_previously_used_keyword_assessment_run() {
		if ( $this->permissions_helper->is_edit_post_screen() || $this->permissions_helper->is_new_post_screen() ) {

			$post = \get_post();

			if (
				$post instanceof WP_Post
				&& (
					$this->permissions_helper->is_rewrite_and_republish_copy( $post )
					|| $this->permissions_helper->has_rewrite_and_republish_copy( $post )
				)
			) {
				\add_filter( 'wpseo_previously_used_keyword_active', '__return_false' );
			}
		}
	}

	/**
	 * Enqueues the necessary JavaScript code for the block editor.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_scripts() {
		$post = \get_post();

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$is_rewrite_and_republish_copy = $this->permissions_helper->is_rewrite_and_republish_copy( $post );

		$edit_js_object = [
			'newDraftLink'            => $this->get_new_draft_permalink(),
			'rewriteAndRepublishLink' => $this->get_rewrite_republish_permalink(),
			'showLinks'               => Utils::get_option( 'duplicate_post_show_link' ),
			'showLinksIn'             => Utils::get_option( 'duplicate_post_show_link_in' ),
			'rewriting'               => $is_rewrite_and_republish_copy ? 1 : 0,
			'originalEditURL'         => $this->get_original_post_edit_url(),
		];
		$this->asset_manager->enqueue_edit_script( $edit_js_object );

		if ( $is_rewrite_and_republish_copy ) {
			$string_js_object = [
				'checkLink' => $this->get_check_permalink(),
			];
			$this->asset_manager->enqueue_strings_script( $string_js_object );
		}
	}

	/**
	 * Generates a New Draft permalink for the current post.
	 *
	 * @return string The permalink. Returns empty if the post can't be copied.
	 */
	public function get_new_draft_permalink() {
		$post = \get_post();

		if ( ! $post instanceof WP_Post || ! $this->permissions_helper->should_links_be_displayed( $post ) ) {
			return '';
		}

		return $this->link_builder->build_new_draft_link( $post );
	}

	/**
	 * Generates a Rewrite & Republish permalink for the current post.
	 *
	 * @return string The permalink. Returns empty if the post cannot be copied for Rewrite & Republish.
	 */
	public function get_rewrite_republish_permalink() {
		$post = \get_post();

		if (
			! $post instanceof WP_Post
			|| $this->permissions_helper->is_rewrite_and_republish_copy( $post )
			|| $this->permissions_helper->has_rewrite_and_republish_copy( $post )
			|| ! $this->permissions_helper->should_links_be_displayed( $post )
			|| $this->permissions_helper->is_elementor_active()
		) {
			return '';
		}

		return $this->link_builder->build_rewrite_and_republish_link( $post );
	}

	/**
	 * Generates a Check Changes permalink for the current post, if it's intended for Rewrite & Republish.
	 *
	 * @return string The permalink. Returns empty if the post does not exist or it's not a Rewrite & Republish copy.
	 */
	public function get_check_permalink() {
		$post = \get_post();

		if ( ! $post instanceof WP_Post || ! $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			return '';
		}

		return $this->link_builder->build_check_link( $post );
	}

	/**
	 * Generates a URL to the original post edit screen.
	 *
	 * @return string The URL. Empty if the copy post doesn't have an original.
	 */
	public function get_original_post_edit_url() {
		$post = \get_post();

		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		$original_post_id = Utils::get_original_post_id( $post->ID );

		if ( ! $original_post_id ) {
			return '';
		}

		return \add_query_arg(
			[
				'dprepublished' => 1,
				'dpcopy'        => $post->ID,
				'dpnonce'       => \wp_create_nonce( 'dp-republish' ),
			],
			\admin_url( 'post.php?action=edit&post=' . $original_post_id )
		);
	}
}
