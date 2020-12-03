<?php
/**
 * Duplicate Post class to manage the post states display.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\UI;

use Yoast\WP\Duplicate_Post\Utils;

/**
 * Represents the Post_States class.
 */
class Post_States {

	/**
	 * Initializes the class.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
		if ( \intval( \get_option( 'duplicate_post_show_original_in_post_states' ) ) === 1 ) {
			\add_filter( 'display_post_states', [ $this, 'show_original_in_post_states' ], 10, 2 );
		}
	}

	/**
	 * Shows link to original post in the post states.
	 *
	 * @param array    $post_states The array of post states.
	 * @param \WP_Post $post        The current post.
	 *
	 * @return array The updated post states array.
	 */
	public function show_original_in_post_states( $post_states, $post ) {
		$original_item = Utils::get_original( $post );
		if ( $original_item ) {
			/* translators: Original item link (to view or edit) or title. */
			$post_states['duplicate_post_original_item'] = \sprintf( __( 'Original: %s', 'duplicate-post' ), Utils::get_edit_or_view_link( $original_item ) );
		}
		return $post_states;
	}
}
