<?php
/**
 * Duplicate Post class to watch for the bulk actions and show notices.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\Watchers;

/**
 * Represents the Bulk_Actions_Watcher class.
 */
class Bulk_Actions_Watcher {

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
		\add_filter( 'removable_query_args', [ $this, 'add_removable_query_args' ] );
		\add_action( 'admin_notices', [ $this, 'add_bulk_clone_admin_notice' ] );
		\add_action( 'admin_notices', [ $this, 'add_bulk_rewrite_and_republish_admin_notice' ] );
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
			$removable_query_args[] = 'bulk_cloned';
			$removable_query_args[] = 'bulk_rewriting';
		}
		return $removable_query_args;
	}

	/**
	 * Shows a notice after the Clone bulk action has succeeded.
	 *
	 * @return void
	 */
	public function add_bulk_clone_admin_notice() {
		if ( ! empty( $_REQUEST['bulk_cloned'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$copied_posts = \intval( $_REQUEST['bulk_cloned'] ); // phpcs:ignore WordPress.Security.NonceVerification
			\printf(
				'<div id="message" class="notice notice-success fade"><p>' .
				\esc_html(
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
	 * Shows a notice after the Rewrite & Republish bulk action has succeeded.
	 *
	 * @return void
	 */
	public function add_bulk_rewrite_and_republish_admin_notice() {
		if ( ! empty( $_REQUEST['bulk_rewriting'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$copied_posts = \intval( $_REQUEST['bulk_rewriting'] ); // phpcs:ignore WordPress.Security.NonceVerification
			\printf(
				'<div id="message" class="notice notice-success fade"><p>' .
				\esc_html(
				/* translators: %s: Number of posts copied. */
					\_n(
						'%s post duplicated. You can now start rewriting your post in the duplicate of the original post. Once you choose to republish it your changes will be merged back into the original post.',
						'%s posts duplicated. You can now start rewriting your posts in the duplicates of the original posts. Once you choose to republish them your changes will be merged back into the original post.',
						$copied_posts,
						'duplicate-post'
					)
				) . '</p></div>',
				\esc_html( $copied_posts )
			);
		}
	}
}
