<?php
/**
 * Duplicate Post handler class for duplication bulk actions.
 *
 * @package Duplicate_Post
 * @since 4.0
 */

namespace Yoast\WP\Duplicate_Post\Handlers;

use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Post_Duplicator;
use Yoast\WP\Duplicate_Post\Utils;

/**
 * Represents the handler for duplication bulk actions.
 */
class Bulk_Handler {

	/**
	 * Post_Duplicator object.
	 *
	 * @var Post_Duplicator
	 */
	protected $post_duplicator;

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * Initializes the class.
	 *
	 * @param Post_Duplicator    $post_duplicator    The Post_Duplicator object.
	 * @param Permissions_Helper $permissions_helper The Permissions Helper object.
	 */
	public function __construct( Post_Duplicator $post_duplicator, Permissions_Helper $permissions_helper ) {
		$this->post_duplicator    = $post_duplicator;
		$this->permissions_helper = $permissions_helper;
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_action( 'admin_init', [ $this, 'add_bulk_handlers' ] );
	}

	/**
	 * Hooks the handler for the Rewrite & Republish action for all the selected post types.
	 *
	 * @return void
	 */
	public function add_bulk_handlers() {
		$duplicate_post_types_enabled = $this->permissions_helper->get_enabled_post_types();

		foreach ( $duplicate_post_types_enabled as $duplicate_post_type_enabled ) {
			\add_filter( "handle_bulk_actions-edit-{$duplicate_post_type_enabled}", [ $this, 'bulk_action_handler' ], 10, 3 );
		}
	}

	/**
	 * Handles the bulk actions.
	 *
	 * @param string $redirect_to The URL to redirect to.
	 * @param string $doaction    The action that has been called.
	 * @param array  $post_ids    The array of marked post IDs.
	 *
	 * @return string The URL to redirect to.
	 */
	public function bulk_action_handler( $redirect_to, $doaction, $post_ids ) {
		$redirect_to = $this->clone_bulk_action_handler( $redirect_to, $doaction, $post_ids );
		return $this->rewrite_bulk_action_handler( $redirect_to, $doaction, $post_ids );
	}

	/**
	 * Handles the bulk action for the Rewrite & Republish feature.
	 *
	 * @param string $redirect_to The URL to redirect to.
	 * @param string $doaction    The action that has been called.
	 * @param array  $post_ids    The array of marked post IDs.
	 *
	 * @return string The URL to redirect to.
	 */
	public function rewrite_bulk_action_handler( $redirect_to, $doaction, $post_ids ) {
		if ( $doaction !== 'duplicate_post_bulk_rewrite_republish' ) {
			return $redirect_to;
		}

		$counter = 0;
		if ( \is_array( $post_ids ) ) {
			foreach ( $post_ids as $post_id ) {
				$post = \get_post( $post_id );
				if ( ! empty( $post ) && $this->permissions_helper->should_rewrite_and_republish_be_allowed( $post ) ) {
					$new_post_id = $this->post_duplicator->create_duplicate_for_rewrite_and_republish( $post );
					if ( ! \is_wp_error( $new_post_id ) ) {
						$counter ++;
					}
				}
			}
		}
		return \add_query_arg( 'bulk_rewriting', $counter, $redirect_to );
	}

	/**
	 * Handles the bulk action for the Clone feature.
	 *
	 * @param string $redirect_to The URL to redirect to.
	 * @param string $doaction    The action that has been called.
	 * @param array  $post_ids    The array of marked post IDs.
	 *
	 * @return string The URL to redirect to.
	 */
	public function clone_bulk_action_handler( $redirect_to, $doaction, $post_ids ) {
		if ( $doaction !== 'duplicate_post_bulk_clone' ) {
			return $redirect_to;
		}

		$counter = 0;
		if ( \is_array( $post_ids ) ) {
			foreach ( $post_ids as $post_id ) {
				$post = \get_post( $post_id );
				if ( ! empty( $post ) && ! $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
					if ( \intval( \get_option( 'duplicate_post_copychildren' ) !== 1 )
						|| ! \is_post_type_hierarchical( $post->post_type )
						|| ( \is_post_type_hierarchical( $post->post_type ) && ! Utils::has_ancestors_marked( $post, $post_ids ) )
					) {
						if ( ! \is_wp_error( \duplicate_post_create_duplicate( $post ) ) ) {
							$counter ++;
						}
					}
				}
			}
		}
		return \add_query_arg( 'bulk_cloned', $counter, $redirect_to );
	}
}
