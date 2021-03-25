<?php

namespace Yoast\WP\Duplicate_Post\UI;

use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Utils;

/**
 * Duplicate Post class to manage the bulk actions menu.
 */
class Bulk_Actions {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * Initializes the class.
	 *
	 * @param Permissions_Helper $permissions_helper The permissions helper.
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
		if ( \intval( Utils::get_option( 'duplicate_post_show_link_in', 'bulkactions' ) ) === 0 ) {
			return;
		}

		\add_action( 'admin_init', [ $this, 'add_bulk_filters' ] );
	}

	/**
	 * Hooks the function to add the Rewrite & Republish option in the bulk actions for the selected post types.
	 *
	 * @return void
	 */
	public function add_bulk_filters() {
		if ( ! $this->permissions_helper->is_current_user_allowed_to_copy() ) {
			return;
		}

		$duplicate_post_types_enabled = $this->permissions_helper->get_enabled_post_types();
		foreach ( $duplicate_post_types_enabled as $duplicate_post_type_enabled ) {
			\add_filter( "bulk_actions-edit-{$duplicate_post_type_enabled}", [ $this, 'register_bulk_action' ] );
		}
	}

	/**
	 * Adds 'Rewrite & Republish' to the bulk action dropdown.
	 *
	 * @param array $bulk_actions The bulk actions array.
	 *
	 * @return array The bulk actions array.
	 */
	public function register_bulk_action( $bulk_actions ) {
		$is_draft_or_trash = isset( $_REQUEST['post_status'] ) && \in_array( $_REQUEST['post_status'], [ 'draft', 'trash' ], true );

		if ( \intval( Utils::get_option( 'duplicate_post_show_link', 'clone' ) ) === 1 ) {
			$bulk_actions['duplicate_post_bulk_clone'] = \esc_html__( 'Clone', 'duplicate-post' );
		}

		if ( ! $is_draft_or_trash
			&& \intval( Utils::get_option( 'duplicate_post_show_link', 'rewrite_republish' ) ) === 1 ) {
			$bulk_actions['duplicate_post_bulk_rewrite_republish'] = \esc_html__( 'Rewrite & Republish', 'duplicate-post' );
		}

		return $bulk_actions;
	}
}
