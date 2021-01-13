<?php
/**
 * Duplicate Post class to watch if the post has been republished for Rewrite & Republish.
 *
 * @package Duplicate_Post
 * @since 4.0
 */

namespace Yoast\WP\Duplicate_Post\Watchers;

use Yoast\WP\Duplicate_Post\Permissions_Helper;

/**
 * Represents the Republished_Post_Watcher class.
 */
class Republished_Post_Watcher {

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

		$this->register_hooks();
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_filter( 'removable_query_args', [ $this, 'add_removable_query_args' ] );
		\add_action( 'admin_notices', [ $this, 'add_admin_notice' ] );
		\add_action( 'enqueue_block_editor_assets', [ $this, 'add_block_editor_notice' ], 11 );
	}

	/**
	 * Adds vars to the removable query args.
	 *
	 * @param array $removable_query_args Array of query args keys.
	 *
	 * @return array The updated array of query args keys.
	 */
	public function add_removable_query_args( $removable_query_args ) {
		if ( \is_array( $removable_query_args ) ) {
			$removable_query_args[] = 'dprepublished';
			$removable_query_args[] = 'dpcopy';
			$removable_query_args[] = 'dpnonce';
		}
		return $removable_query_args;
	}

	/**
	 * Generates the translated text for the republished notice.
	 *
	 * @return string The translated text for the republished notice.
	 */
	public function get_notice_text() {
		return \__(
			'Your original post has been replaced with the rewritten post. You are now viewing the (rewritten) original post.',
			'duplicate-post'
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

		if ( ! empty( $_REQUEST['dprepublished'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div id="message" class="notice notice-success is-dismissible"><p>' .
				\esc_html( $this->get_notice_text() ) .
				'</p></div>';
		}
	}

	/**
	 * Shows a notice on the Block editor.
	 *
	 * @return void
	 */
	public function add_block_editor_notice() {
		if ( ! empty( $_REQUEST['dprepublished'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$notice = [
				'text'          => \wp_slash( $this->get_notice_text() ),
				'status'        => 'success',
				'isDismissible' => true,
			];

			\wp_add_inline_script(
				'duplicate_post_edit_script',
				"duplicatePostNotices.republished_notice = '" . \wp_json_encode( $notice ) . "';",
				'before'
			);
		}
	}
}
