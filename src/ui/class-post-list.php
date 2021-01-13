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
	protected $copy_ids = [];

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
		\add_filter( 'wp_count_posts', [ $this, 'filter_rewrite_and_republish_counts' ], 10, 2 );
	}

	/**
	 * Filters out Rewrite & Republish copies from the post list when Elementor is active.
	 *
	 * @param \WP_Query $query The current WordPress query.
	 *
	 * @return \WP_Query The updated post WordPress query.
	 */
	public function filter_rewrite_and_republish_copies( $query ) {
		if ( ! $this->should_filter() || ( $query instanceof \WP_Query === false ) ) {
			return $query;
		}

		$post_not_in = $query->get( 'post__not_in', [] );
		$post_not_in = array_merge( $post_not_in, \array_keys( $this->get_copy_ids( $query->get( 'post_type' ) ) ) );

		$query->set( 'post__not_in', $post_not_in );

		return $query;
	}

	/**
	 * Filters out the Rewrite and Republish posts from the post counts.
	 *
	 * @param object $counts    The current post counts.
	 * @param string $post_type The post type.
	 *
	 * @return object The updated post counts.
	 */
	public function filter_rewrite_and_republish_counts( $counts, $post_type ) {
		if ( ! $this->should_filter() ) {
			return $counts;
		}

		$copies = $this->get_copy_ids( $post_type );

		foreach ( $copies as $item ) {
			$status = $item->post_status;
			if ( \property_exists( $counts, $status ) ) {
				$counts->$status--;
			}
		}

		return $counts;
	}

	/**
	 * Queries the database to get the IDs of all Rewrite and Republish copies.
	 *
	 * @param string $post_type The post type to fetch the copy IDs for.
	 *
	 * @return array The IDs of the copies.
	 */
	protected function get_copy_ids( $post_type ) {
		global $wpdb;

		if ( empty( $post_type ) ) {
			return [];
		}

		if ( \array_key_exists( $post_type, $this->copy_ids ) ) {
			return $this->copy_ids[ $post_type ];
		}

		$this->copy_ids[ $post_type ] = [];

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT post_id, post_status FROM ' . $wpdb->postmeta . ' AS pm ' .
				'JOIN ' . $wpdb->posts . ' AS p ON pm.post_id = p.ID ' .
				'WHERE meta_key = %s AND post_type = %s',
				'_dp_is_rewrite_republish_copy',
				$post_type
			),
			OBJECT_K
		);

		if ( \is_array( $results ) ) {
			$this->copy_ids[ $post_type ] = $results;
		}

		return $this->copy_ids[ $post_type ];
	}

	/**
	 * Determines whether the filter should be applied.
	 *
	 * @return bool Whether the filter should be applied.
	 */
	protected function should_filter() {
		if ( ! \is_admin() || ! \function_exists( '\get_current_screen' ) ) {
			return false;
		}

		$current_screen = \get_current_screen();

		if ( \is_null( $current_screen ) ) {
			return false;
		}

		return ( $current_screen->base === 'edit' && $this->permissions_helper->is_elementor_active() );
	}
}
