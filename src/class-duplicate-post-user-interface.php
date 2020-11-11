<?php
/**
 * Duplicate Post user interface.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post;

/**
 * Represents the Duplicate Post User Interface class.
 */
class Duplicate_Post_User_Interface {

	/**
	 * Initializes the class.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 */
	private function register_hooks() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'duplicate_post_admin_enqueue_block_editor_scripts' ) );
	}

	/**
	 * Enqueues the necessary JavaScript code for the block editor.
	 *
	 * @return void
	 */
	public function duplicate_post_admin_enqueue_block_editor_scripts() {
		\wp_enqueue_script(
			'duplicate_post_edit_script',
			\plugins_url( \sprintf( 'js/dist/duplicate-post-edit-%s.js', $this->duplicate_post_flatten_version( DUPLICATE_POST_CURRENT_VERSION ) ), DUPLICATE_POST_FILE ),
			array(
				'wp-blocks',
				'wp-element',
				'wp-i18n',
			),
			DUPLICATE_POST_CURRENT_VERSION,
			true
		);

		\wp_localize_script(
			'duplicate_post_edit_script',
			'duplicatePostRewriteRepost',
			array(
				'permalink' => $this->duplicate_post_get_rewrite_republish_permalink(),
			)
		);
	}

	/**
	 * Generates a rewrite and republish permalink for the current post.
	 *
	 * @return string The permalink. Returns empty if the post hasn't been published yet.
	 */
	private function duplicate_post_get_rewrite_republish_permalink() {
		$post = get_post();
		if ( $post->post_status !== 'publish' ) {
			return '';
		}

		return \duplicate_post_get_clone_post_link( $post->ID );
	}

	/**
	 * Flattens a version number for use in a filename.
	 *
	 * @param string $version The original version number.
	 *
	 * @return string The flattened version number.
	 */
	public function duplicate_post_flatten_version( $version ) {
		$parts = \explode( '.', $version );

		if ( \count( $parts ) === 2 && \preg_match( '/^\d+$/', $parts[1] ) === 1 ) {
			$parts[] = '0';
		}

		return \implode( '', $parts );
	}
}
