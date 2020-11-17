<?php
/**
 * Duplicate Post class to manage the row actions.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post;

/**
 * Represents the Row_Action class.
 */
class Row_Action {

	/**
	 * Holds the object to create the action link to duplicate.
	 *
	 * @var Link_Builder
	 */
	protected $link_builder;

	/**
	 * Initializes the class.
	 */
	public function __construct() {
		$this->link_builder = new Rewrite_Republish_Link_Builder();
	}

	/**
	 * Hooks in the `post_row_actions` and `page_row_actions` filters to add a 'Rewrite and Republish' link.
	 *
	 * @param array    $actions The array of actions from the filter.
	 * @param \WP_Post $post    The post object.
	 *
	 * @return mixed
	 */
	public function add_action_link( array $actions, \WP_Post $post ) {
		$title = \_draft_or_post_title( $post );

		if ( $post->post_status === 'publish' ) {
			$actions['rewrite'] = '<a href="' . $this->link_builder->build_link( $post->ID ) .
				'" aria-label="' . \esc_attr(
				/* translators: %s: Post title. */
					\sprintf( __( 'Rewrite & Republish &#8220;%s&#8221;', 'duplicate-post' ), $title )
				) . '">' .
				\esc_html_x( 'Rewrite & Republish', 'verb', 'duplicate-post' ) . '</a>';
		}

		return $actions;
	}
}
