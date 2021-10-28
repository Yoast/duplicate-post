<?php

namespace Yoast\WP\Duplicate_Post\Watchers;

use Yoast\WP\Duplicate_Post\Permissions_Helper;

/**
 * Duplicate Post class to watch for the link actions and show notices.
 */
class Link_Actions_Watcher {

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
		\add_filter( 'removable_query_args', [ $this, 'add_removable_query_args' ], 10, 1 );
		\add_action( 'admin_notices', [ $this, 'add_clone_admin_notice' ] );
		\add_action( 'admin_notices', [ $this, 'add_rewrite_and_republish_admin_notice' ] );
		\add_action( 'enqueue_block_editor_assets', [ $this, 'add_rewrite_and_republish_block_editor_notice' ] );
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
			$removable_query_args[] = 'cloned';
			$removable_query_args[] = 'rewriting';
		}
		return $removable_query_args;
	}

	/**
	 * Shows a notice after the Clone link action has succeeded.
	 *
	 * @return void
	 */
	public function add_clone_admin_notice() {
		if ( ! empty( $_REQUEST['cloned'] ) ) {
			if ( ! $this->permissions_helper->is_classic_editor() ) {
				return;
			}

			$copied_posts = \intval( $_REQUEST['cloned'] );
			\printf(
				'<div id="message" class="notice notice-success fade"><p>'
				. \esc_html(
					/* translators: %s: Number of posts copied. */
					\_n(
						'%s item copied.',
						'%s items copied.',
						$copied_posts,
						'duplicate-post'
					)
				) . '</p></div>',
				\esc_html( $copied_posts )
			);
		}
	}

	/**
	 * Shows a notice in Classic editor after the Rewrite & Republish action via link has succeeded.
	 *
	 * @return void
	 */
	public function add_rewrite_and_republish_admin_notice() {
		if ( ! empty( $_REQUEST['rewriting'] ) ) {
			if ( ! $this->permissions_helper->is_classic_editor() ) {
				return;
			}

			print '<div id="message" class="notice notice-warning is-dismissible fade"><p>'
				. \esc_html__(
					'You can now start rewriting your post in this duplicate of the original post. If you click "Republish", your changes will be merged into the original post and you’ll be redirected there.',
					'duplicate-post'
				) . '</p></div>';
		}
	}

	/**
	 * Shows a notice on the Block editor after the Rewrite & Republish action via link has succeeded.
	 *
	 * @return void
	 */
	public function add_rewrite_and_republish_block_editor_notice() {
		if ( ! empty( $_REQUEST['rewriting'] ) ) {
			$notice = [
				'text'          => \wp_slash(
					\__(
						'You can now start rewriting your post in this duplicate of the original post. If you click "Republish", this rewritten post will replace the original post.',
						'duplicate-post'
					)
				),
				'status'        => 'warning',
				'isDismissible' => true,
			];

			\wp_add_inline_script(
				'duplicate_post_edit_script',
				"duplicatePostNotices.rewriting_notice = '" . \wp_json_encode( $notice ) . "';",
				'before'
			);
		}
	}
}
