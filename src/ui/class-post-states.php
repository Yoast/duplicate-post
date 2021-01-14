<?php
/**
 * Duplicate Post class to manage the post states display.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\UI;

use WP_Post;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Utils;

/**
 * Represents the Post_States class.
 */
class Post_States {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * Initializes the class.
	 *
	 * @param Permissions_Helper $permissions_helper The Permissions helper object.
	 */
	public function __construct( Permissions_Helper $permissions_helper ) {
		$this->permissions_helper = $permissions_helper;
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_filter( 'display_post_states', [ $this, 'show_original_in_post_states' ], 10, 2 );
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
		if ( ! $post instanceof WP_Post
			|| ! \is_array( $post_states ) ) {
			return $post_states;
		}

		$original_item = Utils::get_original( $post );

		if ( ! $original_item ) {
			return $post_states;
		}

		if ( $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			/* translators: %s: Original item link (to view or edit) or title. */
			$post_states['duplicate_post_original_item'] = \sprintf( \esc_html__( 'Rewrite & Republish of %s', 'duplicate-post' ), Utils::get_edit_or_view_link( $original_item ) );
			return $post_states;
		}

		if ( \intval( \get_option( 'duplicate_post_show_original_in_post_states' ) ) === 1 ) {
			/* translators: %s: Original item link (to view or edit) or title. */
			$post_states['duplicate_post_original_item'] = \sprintf( \__( 'Original: %s', 'duplicate-post' ), Utils::get_edit_or_view_link( $original_item ) );
		}

		return $post_states;
	}
}
