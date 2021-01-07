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
	 * Holds the array of copy IDs.
	 *
	 * @var array
	 */
	protected $copy_ids;

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
		\add_filter( 'parse_query', [ $this, 'filter_rewrite_and_republish_copies' ] );
		\add_filter( 'wp_count_posts', [ $this, 'filter_rewrite_and_republish_counts' ] );
	}

	/**
	 * Filters out Rewrite & Republish copies from the post list when Elementor is active.
	 *
	 * @param \WP_Query $query The current WordPress query.
	 *
	 * @return \WP_Query The updated post WordPress query.
	 */
	public function filter_rewrite_and_republish_copies( \WP_Query $query ) {
		if ( ! $this->should_filter() ) {
			return $query;
		}

		$post_not_in = $query->get( 'post__not_in', [] );
		$post_not_in = array_merge( $post_not_in, $this->get_copy_ids() );

		$query->set( 'post__not_in', $post_not_in );

		return $query;
	}

	/**
	 * Filters out the Rewrite and Republish posts from the post counts.
	 *
	 * @param object $counts The current post counts.
	 *
	 * @return object The updated post counts.
	 */
	public function filter_rewrite_and_republish_counts( $counts ) {
		if ( ! $this->should_filter() ) {
			return $counts;
		}

		$counts->draft = $counts->draft - count( $this->get_copy_ids() );

		return $counts;
	}

	/**
	 * Queries the database to get the IDs of all Rewrite and Republish copies.
	 *
	 * @return array The IDs of the copies.
	 */
	protected function get_copy_ids() {
		global $wpdb;

		if ( ! is_null( $this->copy_ids ) ) {
			return $this->copy_ids;
		}

		$query = $wpdb->prepare(
			'SELECT post_id FROM ' . $wpdb->postmeta . ' WHERE meta_key = %s', '_dp_is_rewrite_republish_copy'
		);

		$this->copy_ids = $wpdb->get_col( $query );

		return $this->copy_ids;
	}

	/**
	 * Determines whether the filter should be applied.
	 *
	 * @return bool Whether the filter should be applied.
	 */
	protected function should_filter() {
		$current_screen = \get_current_screen();

		return ( \is_admin() && $current_screen->base === 'edit' && $this->permissions_helper->is_elementor_active() );
	}
}
