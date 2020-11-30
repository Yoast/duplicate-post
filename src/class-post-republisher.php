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
	public function register_hooks() {
		\add_action( 'init', [ $this, 'register_post_statuses' ] );
		\add_filter( 'wp_insert_post_data', [ $this, 'change_post_copy_status' ], 10, 2 );

		$enabled_post_types = Utils::get_enabled_post_types();
		foreach ( $enabled_post_types as $enabled_post_type ) {
			// Classic editor: Post transition action, called when a post transitions to the rewrite_republish status.
			\add_action( "rewrite_republish_{$enabled_post_type}", [ $this, 'republish_classic' ], 10, 2 );
			// Block editor: called after a single post is completely created or updated via the REST API.
			\add_action( "rest_after_insert_{$enabled_post_type}", [ $this, 'republish_gutenberg' ] );
		}
	}

	/**
	 * Adds custom post statuses.
	 *
	 * @return void
	 */
	public function register_post_statuses() {
		$republish_args = [
			'label'  => __( 'Republish', 'duplicate-post' ),
			'public' => true,
		];
		\register_post_status( 'rewrite_republish', $republish_args );

		$schedule_args = [
			'label'  => __( 'Future Republish', 'duplicate-post' ),
			'public' => true,
		];
		\register_post_status( 'rewrite_schedule', $schedule_args );

		$rewrite_args = [
			'label'  => __( 'Rewrite Draft', 'duplicate-post' ),
			'public' => true,
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
	 * Republishes the original post with the passed post, when using the Block editor.
	 *
	 * @param \WP_Post $post_data The copy's post object.
	 *
	 * @return void
	 */
	public function republish_gutenberg( $post_data ) {
		if ( $post_data->post_status !== 'rewrite_republish' ) {
			return;
		}

		$post_id          = $post_data->ID;
		$original_post_id = Utils::get_original_post_id( $post_id );

		if ( ! $original_post_id ) {
			return;
		}

		// Republish taxonomies and meta.
		$this->republish_post_taxonomies( $post_id, $post_data );
		$this->republish_post_meta( $post_id, $post_data );
		// Republish the post.
		$this->republish_post_elements( $post_data, $original_post_id );
		// Investigate whether the clean-up needs to be done on the Gutenberg JS side.
		// $this->clean_up( $post_id, $original_post_id );
	}

	/**
	 * Republishes the original post with the passed post and redirects the user, when using the Classic editor.
	 *
	 * Runs on the post transition status to `rewrite_republish` in `wp_insert_post()`
	 * when submitting the post copy.
	 *
	 * @param int      $post_id   The copy's post ID.
	 * @param \WP_Post $post_data The copy's post object.
	 *
	 * @return void
	 */
	public function republish_classic( $post_id, $post_data ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		$original_post_id = Utils::get_original_post_id( $post_id );

		if ( ! $original_post_id ) {
			return;
		}

		// Republish taxonomies and meta.
		$this->republish_post_taxonomies( $post_id, $post_data );
		$this->republish_post_meta( $post_id, $post_data );
		// Republish the post.
		$this->republish_post_elements( $post_data, $original_post_id );
		// Delete the copy and redirect.
		$this->clean_up( $post_id, $original_post_id );
		$this->redirect( $original_post_id );
	}

	/**
	 * Republishes the post elements overwriting the original post.
	 *
	 * @param \WP_Post $post             The post object.
	 * @param int      $original_post_id The original post's ID.
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
	 * @param int      $post_id   The copy's post ID.
	 * @param \WP_Post $post_data The copy's post object.
	 *
	 * @return void
	 */
	protected function republish_post_taxonomies( $post_id, $post_data ) {
		$original_post_id = Utils::get_original_post_id( $post_id );

		$copy_taxonomies_options = [
			'taxonomies_excludelist' => [],
			'use_filters'            => false,
			'copy_format'            => true,
		];
		$this->post_duplicator->copy_post_taxonomies( $original_post_id, $post_data, $copy_taxonomies_options );
	}

	/**
	 * Republishes the post meta overwriting the ones of the original post.
	 *
	 * @param int      $post_id   The copy's post ID.
	 * @param \WP_Post $post_data The copy's post object.
	 *
	 * @return void
	 */
	protected function republish_post_meta( $post_id, $post_data ) {
		$original_post_id = Utils::get_original_post_id( $post_id );

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
			'copy_template'    => true,
		];
		$this->post_duplicator->copy_post_meta_info( $original_post_id, $post_data, $copy_meta_options );
	}

	/**
	 * Deletes the copied post and temporary metadata.
	 *
	 * @param int $post_copy_id     The copy's ID.
	 * @param int $original_post_id The original post ID.
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
	 * @param int $original_post_id The original post to redirect to.
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
