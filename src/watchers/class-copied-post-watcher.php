<?php
/**
 * Duplicate Post class to watch if the current post has a Rewrite & Republish copy.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\Watchers;

use Yoast\WP\Duplicate_Post\Permissions_Helper;

/**
 * Represents the Copied_Post_Watcher class.
 */
class Copied_Post_Watcher {

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
	public function __construct( $permissions_helper ) {
		$this->permissions_helper = $permissions_helper;

		$this->register_hooks();
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_action( 'admin_notices', [ $this, 'add_admin_notice' ] );
		\add_action( 'enqueue_block_editor_assets', [ $this, 'add_block_editor_notice' ], 11 );
	}

	/**
	 * Generates the translated text for the notice.
	 *
	 * @param \WP_Post $post The current post object.
	 *
	 * @return string The translated text for the notice.
	 */
	public function get_notice_text( $post ) {
		if ( $this->permissions_helper->has_trashed_rewrite_and_republish_copy( $post ) ) {
			return \__(
				'You can only make one Rewrite & Republish duplicate at a time, and a duplicate of this post already exists in the trash. Permanently delete it if you want to make a new duplicate.',
				'duplicate-post'
			);
		}

		$scheduled_copy = $this->permissions_helper->has_scheduled_rewrite_and_republish_copy( $post );
		if ( ! $scheduled_copy ) {
			return \__(
				'A duplicate of this post was made. Please note that any changes you make to this post will be replaced when the duplicated version is republished.',
				'duplicate-post'
			);
		}

		return \sprintf(
			/* translators: %1$s: scheduled date of the copy, %2$s: scheduled time of the copy. */
			\__(
				'A duplicate of this post was made, which is scheduled to replace this post on %1$s at %2$s.',
				'duplicate-post'
			),
			\get_the_time( \get_option( 'date_format' ), $scheduled_copy ),
			\get_the_time( \get_option( 'time_format' ), $scheduled_copy )
		);
	}

	/**
	 * Shows a notice on the Classic editor.
	 *
	 * @return void
	 */
	public function add_admin_notice() {
		if ( ! $this->permissions_helper->is_classic_editor() ) {
			return;
		}

		$post = \get_post();

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( $this->permissions_helper->has_rewrite_and_republish_copy( $post ) ) {
			print '<div id="message" class="notice notice-warning is-dismissible fade"><p>' .
				\esc_html( $this->get_notice_text( $post ) ) .
				'</p></div>';
		}
	}

	/**
	 * Shows a notice on the Block editor.
	 *
	 * @return void
	 */
	public function add_block_editor_notice() {
		$post = \get_post();

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( $this->permissions_helper->has_rewrite_and_republish_copy( $post ) ) {

			$notice = [
				'text'          => \wp_slash( $this->get_notice_text( $post ) ),
				'status'        => 'warning',
				'isDismissible' => true,
			];

			\wp_add_inline_script(
				'duplicate_post_edit_script',
				"duplicatePostNotices.has_rewrite_and_republish_notice = '" . \wp_json_encode( $notice ) . "';",
				'before'
			);
		}
	}
}
