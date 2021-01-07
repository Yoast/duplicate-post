<?php
/**
 * Duplicate Post class to manage the post list.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\UI;

use Yoast\WP\Duplicate_Post\Permissions_Helper;

/**
 * Represents the Post_List class.
 */
class Post_List {

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
		\add_filter( 'parse_query', [ $this, 'filter_rewrite_and_republish_copies' ], 10 );
	}

	/**
	 * Filters out Rewrite & Republish copies from the post list when Elementor is active.
	 *
	 * @param \WP_Query $query The current WordPress query.
	 *
	 * @return \WP_Query The updated post WordPress query.
	 */
	public function filter_rewrite_and_republish_copies( \WP_Query $query ) {
		if ( ! \is_admin() ) {
			return $query;
		}

		$current_screen = \get_current_screen();
		if ( $current_screen->base !== 'edit' ) {
			return $query;
		}

		if ( $this->permissions_helper->is_elementor_active() ) {
			$query->set(
				'meta_query',
				[
					[
						'key'     => '_dp_is_rewrite_republish_copy',
						'compare' => 'NOT EXISTS',
					],
				]
			);
		}
		return $query;
	}
}
