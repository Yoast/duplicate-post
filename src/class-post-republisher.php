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
	 * Holds the post copy ID.
	 *
	 * @var int
	 */
	private $post_copy_id;

	/**
	 * Holds the post copy pbject.
	 *
	 * @var \WP_Post
	 */
	private $post_copy;

	/**
	 * Holds the original post ID.
	 *
	 * @var int
	 */
	private $original_post_id;

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
	public function register_hooks() {
		\add_action( 'init', [ $this, 'register_post_statuses' ] );
		\add_filter( 'wp_insert_post_data', [ $this, 'change_post_copy_status' ], 10, 2 );

		$enabled_post_types = Utils::get_enabled_post_types();
		foreach ( $enabled_post_types as $enabled_post_type ) {
			// Post transistion action, called when a post transistions to the rewrite_republish status.
//			\add_action( "rewrite_republish_{$enabled_post_type}", [ $this, 'duplicate_post_republish' ], 10, 2 );
		}
			\add_action( "pre_post_update", [ $this, 'duplicate_post_republish_post_data' ], 10, 2 );
	}

	/**
	 * Adds custom post statuses.
	 *
	 * @return void
	 */
	public function register_post_statuses() {
		$republish_args = [
			'label'    => __( 'Republish', 'duplicate-post' ),
			'internal' => true,
		];
		\register_post_status( 'rewrite_republish', $republish_args );

		$schedule_args = [
			'label'    => __( 'Future Republish', 'duplicate-post' ),
			'internal' => true,
		];
		\register_post_status( 'rewrite_schedule', $schedule_args );

		$rewrite_args = [
			'label'    => __( 'Rewrite Draft', 'duplicate-post' ),
			'internal' => true,
		];
		\register_post_status( 'rewrite_draft', $rewrite_args );
	}

	/**
	 * Changes the post copy status.
	 *
	 * Runs on the `wp_insert_post_data` hook in `wp_insert_post()` when
	 * submitting the post copy.
	 *
	 * @param array $data    An array of slashed, sanitized, and processed post data.
	 * @param array $postarr An array of sanitized (and slashed) but otherwise unmodified post data.
	 *
	 * @return array An array of slashed, sanitized, and processed attachment post data.
	 */
	public function change_post_copy_status( $data, $postarr ) {
		if ( ! isset( $postarr['ID'] ) || ! Utils::is_copy_for_rewrite_republish( $postarr['ID'] ) ) {
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

	/**
	 * Republishes the original post data with the passed post data and redirects the user, when using the Classic editor.
	 *
	 * @param int   $post_id   The copy's post ID.
	 * @param array $post_data The post data array.
	 *
	 * @return void
	 */
	public function duplicate_post_republish_post_data( $post_id, $post_data ) {
		if ( $post_data['post_status'] !== 'rewrite_republish' ) {
			return;
		}

		// Update the basic post data in the original post
		$original_post_id = Utils::get_original_post_id( $post_id );

		if ( ! $original_post_id ) {
			return;
		}

		$this->republish_post_elements( $post_data, $original_post_id );
		$this->clean_up( $post_id, $original_post_id );

		if ( ! defined( 'GUTENBERG_VERSION' ) ) {
			$this->redirect( $original_post_id );
		}
	}

	/**
	 * Handles the republishing flow.
	 *
	 * Runs on the post transition status from `draft` to `rewrite_republish` in
	 * `wp_insert_post()` when submitting the post copy.
	 *
	 * @param int      $post_copy_id The post copy ID.
	 * @param \WP_Post $post_copy    The post copy object.
	 *
	 * @return void
	 */
	public function duplicate_post_republish( $post_copy_id, $post_copy ) {
		$this->post_copy_id     = $post_copy_id;
		$this->post_copy        = $post_copy;
		$this->original_post_id = Utils::get_original_post_id( $post_copy_id );

		if ( ! $this->original_post_id ) {
			return;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		// Republish taxonomies and meta first.
		$this->republish_post_taxonomies();
		$this->republish_post_meta();

		// Republish the post.
		$this->republish_post_elements();
		$this->clean_up_and_redirect();
	}

	/**
	 * Republishes the post elements overwriting the original post.
	 *
	 * @param $post				The post object.
	 * @param $original_post_id The original post's ID.
	 *
	 * @return void
	 */
	protected function republish_post_elements( $post, $original_post_id ) {
		// Cast to array and not alter the original object.
		$post_to_be_rewritten = (array) $post;
		// Prepare post data for republishing.
		$post_to_be_rewritten['ID']          = $original_post_id;
		$post_to_be_rewritten['post_name']   = \get_post_field( 'post_name', $original_post_id );
		$post_to_be_rewritten['post_status'] = 'publish';

		// Republish original post.
		$_POST['ID']       = $original_post_id;
		$rewritten_post_id = \wp_update_post( \wp_slash( $post_to_be_rewritten ), true );

		if ( 0 === $rewritten_post_id || \is_wp_error( $rewritten_post_id ) ) {
			// Error handling here.
			die( 'An error occurred.' );
		}
	}

	/**
	 * Republishes the post taxonomies overwriting the ones of the original post.
	 *
	 * @return void
	 */
	protected function republish_post_taxonomies() {
		$copy_taxonomies_options = [
			'taxonomies_excludelist' => [],
			'use_filters'            => false,
			'copy_format'            => true,
		];
		$this->post_duplicator->copy_post_taxonomies( $this->original_post_id, $this->post_copy, $copy_taxonomies_options );
	}

	/**
	 * Republishes the post meta overwriting the ones of the original post.
	 *
	 * @return void
	 */
	protected function republish_post_meta() {
		// Note that the WP SEO metadata get saved on the `wp_insert_post` hook.
		$copy_meta_options = [
			'meta_excludelist' => [
				'_edit_lock',
				'_edit_last',
				'_dp_original',
				'_dp_is_rewrite_republish_copy',
			],
			'use_filters'      => false,
			'copy_thumbnail'   => true,
			'copy_template'    => false, // The function wp_insert_post handles the page template internally.
		];
		$this->post_duplicator->copy_post_meta_info( $this->original_post_id, $this->post_copy, $copy_meta_options );
	}

	/**
	 * Deletes the copied post and temporary metadata.
	 *
	 * @param $post_copy_id		The copy's ID.
	 * @param $original_post_id The original post ID.
	 *
	 * @return void
	 */
	protected function clean_up( $post_copy_id, $original_post_id ) {
		// Deleting the copy bypassing the trash also deletes the post copy meta.
		\wp_delete_post( $post_copy_id, true );
		// Delete the meta that marks the original post has having a copy.
		\delete_post_meta( $original_post_id, '_dp_has_rewrite_republish_copy' );
	}

	/**
	 * Redirects the user to the original post.
	 *
	 * @param $original_post_id The original post to redirect to.
	 *
	 * @return void
	 */
	protected function redirect( $original_post_id ) {
		// Add nonce verification here.
		\wp_safe_redirect(
			\add_query_arg(
				[
					'republished' => 1,
				],
				\admin_url( 'post.php?action=edit&post=' . $original_post_id )
			)
		);
		exit();
	}
}
