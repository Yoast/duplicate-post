<?php
/**
 * Duplicate Post class to republish a rewritten post.
 *
 * @package Duplicate_Post
 * @since 4.0
 */

namespace Yoast\WP\Duplicate_Post;

/**
 * Represents the Post Republisher class.
 */
class Post_Republisher {

	/**
	 * Post_Duplicator object.
	 *
	 * @var Post_Duplicator
	 */
	private $post_duplicator;

	/**
	 * Initializes the class.
	 *
	 * @param Post_Duplicator $post_duplicator The Post_Duplicator object.
	 */
	public function __construct( Post_Duplicator $post_duplicator ) {
		$this->post_duplicator = $post_duplicator;
		$this->register_hooks();
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	private function register_hooks() {
		\add_action( 'init', array( $this, 'register_post_statuses' ) );
		\add_filter( 'wp_insert_post_data', array( $this, 'filter_post_data_before_wp_insert' ), 10, 2 );

		$enabled_post_types = Utils::get_enabled_post_types();
		foreach ( $enabled_post_types as $enabled_post_type ) {
			\add_action( "rewrite_republish_{$enabled_post_type}", array( $this, 'duplicate_post_republish' ), 10, 2 );
		}
	}

	/**
	 * Handles the republishing flow.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 *
	 * @return void
	 */
	public function duplicate_post_republish( $post_id, $post ) {
		$this->republish_post_elements( $post_id, $post );
	}

	/**
	 * Republishes the post elements overwriting the original post.
	 *
	 * @param int      $post_copy_id Post ID.
	 * @param \WP_Post $post_copy    Post object.
	 *
	 * @return void
	 */
	private function republish_post_elements( $post_copy_id, $post_copy ) {

		$original_post_id = Utils::get_rewrite_republish_copy_id( $post_copy_id );

		if ( ! $original_post_id ) {
			return;
		}

		// Republish taxonomies first.
		$this->post_duplicator->copy_post_taxonomies( $original_post_id, $post_copy, [] );

		// Prepare post data for republishing.
		$post_to_be_rewritten              = $post_copy;
		$post_to_be_rewritten->ID          = $original_post_id;
		$post_to_be_rewritten->post_name   = \get_post_field( 'post_name', $post_to_be_rewritten->ID );
		$post_to_be_rewritten->post_status = 'publish';

		// Republish original post.
		$rewritten_post_id = \wp_update_post( \wp_slash( (array) $post_to_be_rewritten ), true );

		if ( 0 === $rewritten_post_id || \is_wp_error( $rewritten_post_id ) ) {
			// Error handling here.
			die( 'An error occurred.' );
		}

		// Deleting the copy bypassing the trash also deletes the post copy meta.
		\wp_delete_post( $post_copy_id, true );

		// Add nonce verification here.
		\wp_safe_redirect(
			\add_query_arg(
				array(
					'republished' => 1,
				),
				\admin_url( 'post.php?action=edit&post=' . $original_post_id )
			)
		);
		exit();
	}

	/**
	 * Adds custom post statuses.
	 *
	 * @return void
	 */
	public function register_post_statuses() {
		$republish_args = array(
			'label'    => __( 'Republish', 'duplicate-post' ),
			'internal' => true,
		);
		\register_post_status( 'rewrite_republish', $republish_args );

		$schedule_args = array(
			'label'    => __( 'Future Republish', 'duplicate-post' ),
			'internal' => true,
		);
		\register_post_status( 'rewrite_schedule', $schedule_args );
	}

	/**
	 * Adds custom statuses to the copied post.
	 *
	 * @param array $data    An array of slashed, sanitized, and processed post data.
	 * @param array $postarr An array of sanitized (and slashed) but otherwise unmodified post data.
	 *
	 * @return array An array of slashed, sanitized, and processed attachment post data.
	 */
	public function filter_post_data_before_wp_insert( $data, $postarr ) {
		if ( ! isset( $postarr['ID'] ) || ! Utils::get_rewrite_republish_copy_id( $postarr['ID'] ) ) {
			return $data;
		}

		if ( $data['post_status'] === 'publish' ) {
			$data['post_status'] = 'rewrite_republish';
		}

		if ( $data['post_status'] === 'future' ) {
			$data['post_status'] = 'rewrite_schedule';
		}

		return $data;
	}
}
