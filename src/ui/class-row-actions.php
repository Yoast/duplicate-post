<?php
/**
 * Duplicate Post class to manage the row actions.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\UI;

use WP_Post;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Utils;

/**
 * Represents the Row_Action class.
 */
class Row_Actions {

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
		if ( \intval( Utils::get_option( 'duplicate_post_show_link_in', 'row' ) ) === 0 ) {
			return;
		}

		if ( \intval( Utils::get_option( 'duplicate_post_show_link', 'clone' ) ) === 1 ) {
			\add_filter( 'post_row_actions', [ $this, 'add_clone_action_link' ], 10, 2 );
			\add_filter( 'page_row_actions', [ $this, 'add_clone_action_link' ], 10, 2 );
		}

		if ( \intval( Utils::get_option( 'duplicate_post_show_link', 'new_draft' ) ) === 1 ) {
			\add_filter( 'post_row_actions', [ $this, 'add_new_draft_action_link' ], 10, 2 );
			\add_filter( 'page_row_actions', [ $this, 'add_new_draft_action_link' ], 10, 2 );
		}

		if ( \intval( Utils::get_option( 'duplicate_post_show_link', 'rewrite_republish' ) ) === 1 ) {
			\add_filter( 'post_row_actions', [ $this, 'add_rewrite_and_republish_action_link' ], 10, 2 );
			\add_filter( 'page_row_actions', [ $this, 'add_rewrite_and_republish_action_link' ], 10, 2 );
		}
	}

	/**
	 * Hooks in the `post_row_actions` and `page_row_actions` filters to add a 'Clone' link.
	 *
	 * @param array    $actions The array of actions from the filter.
	 * @param \WP_Post $post    The post object.
	 *
	 * @return array The updated array of actions.
	 */
	public function add_clone_action_link( $actions, $post ) {
		if ( ! $post instanceof WP_Post
			|| ! $this->permissions_helper->should_links_be_displayed( $post )
			|| ! \is_array( $actions ) ) {
			return $actions;
		}

		$title = \_draft_or_post_title( $post );

		$actions['clone'] = '<a href="' . $this->link_builder->build_clone_link( $post->ID ) .
			'" aria-label="' . \esc_attr(
			/* translators: %s: Post title. */
				\sprintf( \__( 'Clone &#8220;%s&#8221;', 'duplicate-post' ), $title )
			) . '">' .
			\esc_html_x( 'Clone', 'verb', 'duplicate-post' ) . '</a>';

		return $actions;
	}

	/**
	 * Hooks in the `post_row_actions` and `page_row_actions` filters to add a 'New Draft' link.
	 *
	 * @param array    $actions The array of actions from the filter.
	 * @param \WP_Post $post    The post object.
	 *
	 * @return array The updated array of actions.
	 */
	public function add_new_draft_action_link( $actions, $post ) {
		if ( ! $post instanceof WP_Post
			|| ! $this->permissions_helper->should_links_be_displayed( $post )
			|| ! \is_array( $actions ) ) {
			return $actions;
		}

		$title = \_draft_or_post_title( $post );

		$actions['edit_as_new_draft'] = '<a href="' . $this->link_builder->build_new_draft_link( $post->ID ) .
			'" aria-label="' . \esc_attr(
			/* translators: %s: Post title. */
				\sprintf( \__( 'New draft of &#8220;%s&#8221;', 'duplicate-post' ), $title )
			) . '">' .
			\esc_html__( 'New Draft', 'duplicate-post' ) .
			'</a>';

		return $actions;
	}

	/**
	 * Hooks in the `post_row_actions` and `page_row_actions` filters to add a 'Rewrite & Republish' link.
	 *
	 * @param array    $actions The array of actions from the filter.
	 * @param \WP_Post $post    The post object.
	 *
	 * @return array The updated array of actions.
	 */
	public function add_rewrite_and_republish_action_link( $actions, $post ) {
		if (
			! $post instanceof WP_Post
			|| ! $this->permissions_helper->should_rewrite_and_republish_be_allowed( $post )
			|| ! $this->permissions_helper->should_links_be_displayed( $post )
			|| ! \is_array( $actions )
		) {
			return $actions;
		}

		$title = \_draft_or_post_title( $post );

		$actions['rewrite'] = '<a href="' . $this->link_builder->build_rewrite_and_republish_link( $post->ID ) .
			'" aria-label="' . \esc_attr(
			/* translators: %s: Post title. */
				\sprintf( \__( 'Rewrite & Republish &#8220;%s&#8221;', 'duplicate-post' ), $title )
			) . '">' .
			\esc_html_x( 'Rewrite & Republish', 'verb', 'duplicate-post' ) . '</a>';

		return $actions;
	}
}
