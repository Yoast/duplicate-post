<?php

namespace Yoast\WP\Duplicate_Post\UI;

use WP_Post;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Utils;

/**
 * Duplicate Post class to manage the block editor UI.
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
		\add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'hide_elementor_post_status' ] );
		\add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'enqueue_elementor_script' ], 9 );
		\add_action( 'admin_enqueue_scripts', [ $this, 'should_previously_used_keyword_assessment_run' ], 9 );
		\add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_scripts' ] );
		\add_filter( 'wpseo_link_suggestions_indexables', [ $this, 'remove_original_from_wpseo_link_suggestions' ], 10, 3 );
	}

	/**
	 * Enqueues the necessary Elementor script for the current post.
	 *
	 * @return void
	 */
	public function enqueue_elementor_script() {
		$post = \get_post();

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$edit_js_object = $this->generate_js_object( $post );
		$this->asset_manager->enqueue_elementor_script( $edit_js_object );
	}

	/**
	 * Hides the post status control if we're working on a Rewrite and Republish post.
	 *
	 * @return void
	 */
	public function hide_elementor_post_status() {
		$post = \get_post();

		if ( ! $post instanceof WP_Post || ! $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			return;
		}
		\wp_add_inline_style(
			'elementor-editor',
			'.elementor-control-post_status { display: none !important; }'
		);
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

		$edit_js_object = $this->generate_js_object( $post );
		$this->asset_manager->enqueue_edit_script( $edit_js_object );

		if ( $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
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

		if ( ! $post instanceof WP_Post || ! $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
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

	/**
	 * Generates an array of data to be passed as a localization object to JavaScript.
	 *
	 * @param WP_Post $post The current post object.
	 *
	 * @return array The data to pass to JavaScript.
	 */
	protected function generate_js_object( WP_Post $post ) {
		$is_rewrite_and_republish_copy = $this->permissions_helper->is_rewrite_and_republish_copy( $post );

		return [
			'newDraftLink'            => $this->get_new_draft_permalink(),
			'rewriteAndRepublishLink' => $this->get_rewrite_republish_permalink(),
			'showLinks'               => Utils::get_option( 'duplicate_post_show_link' ),
			'showLinksIn'             => Utils::get_option( 'duplicate_post_show_link_in' ),
			'rewriting'               => ( $is_rewrite_and_republish_copy ) ? 1 : 0,
			'originalEditURL'         => $this->get_original_post_edit_url(),
		];
	}

	/**
	 * Filters the Yoast SEO Premium link suggestions.
	 *
	 * Removes the original post from the Yoast SEO Premium link suggestions
	 * displayed on the Rewrite & Republish copy.
	 *
	 * @param array  $suggestions An array of suggestion indexables that can be filtered.
	 * @param int    $object_id   The object id for the current indexable.
	 * @param string $object_type The object type for the current indexable.
	 *
	 * @return array The filtered array of suggestion indexables.
	 */
	public function remove_original_from_wpseo_link_suggestions( $suggestions, $object_id, $object_type ) {
		if ( $object_type !== 'post' ) {
			return $suggestions;
		}

		// WordPress get_post already checks if the passed ID is valid and returns null if it's not.
		$post = \get_post( $object_id );

		if ( ! $post instanceof WP_Post || ! $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			return $suggestions;
		}

		$original_post_id = Utils::get_original_post_id( $post->ID );

		return \array_filter(
			$suggestions,
			static function ( $suggestion ) use ( $original_post_id ) {
				return $suggestion->object_id !== $original_post_id;
			}
		);
	}
}
