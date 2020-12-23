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
		\add_action( 'init', [ $this, 'register_post_statuses' ] );
		\add_filter( 'wp_insert_post_data', [ $this, 'change_post_copy_status' ], 1, 2 );
		\add_filter( 'display_post_states', [ $this, 'add_rewrite_schedule_display_state' ], 9, 2 );

		$enabled_post_types = $this->permissions_helper->get_enabled_post_types();
		foreach ( $enabled_post_types as $enabled_post_type ) {
			// Called in the REST API when submitting the post copy in the Block Editor.
			// Runs the republishing of the copy onto the original.
			\add_action( "rest_after_insert_{$enabled_post_type}", [ $this, 'republish_after_rest_api_request' ] );
		}
		// Called by `wp_insert_post()` when submitting the post copy, which runs in two cases:
		// - In the Classic Editor, where there's only one request that updates everything.
		// - In the Block Editor, only when there are custom meta boxes.
		\add_action( 'wp_insert_post', [ $this, 'republish_after_post_request' ], \PHP_INT_MAX, 2 );
		// Clean up after the redirect to the original post.
		\add_action( 'load-post.php', [ $this, 'clean_up_after_redirect' ] );
	}

	/**
	 * Adds custom post statuses.
	 *
	 * These post statuses are meant for internal use. However, we can't use the
	 * `internal` status because the REST API posts controller allows all registered
	 * statuses but the `internal` one.
	 *
	 * @return void
	 */
	public function register_post_statuses() {
		$custom_post_statuses = [
			'dp-rewrite-republish' => [
				'label'                     => __( 'Republish', 'duplicate-post' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
			],
			'dp-rewrite-schedule'  => [
				'label'                     => __( 'Future Republish', 'duplicate-post' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
			],
		];

		foreach ( $custom_post_statuses as $custom_post_status => $options ) {
			\register_post_status( $custom_post_status, $options );
		}
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
		$post = \get_post( $postarr['ID'] );

		if ( ! $post || ! $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			return $data;
		}

		if ( $data['post_status'] === 'publish' ) {
			$data['post_status'] = 'dp-rewrite-republish';
		}

		if ( $data['post_status'] === 'future' ) {
			$data['post_status'] = 'dp-rewrite-schedule';
		}

		return $data;
	}

	/**
	 * Executes the republish request.
	 *
	 * @param \WP_Post $post The copy's post object.
	 *
	 * @return void
	 */
	public function republish_request( $post ) {
		if (
			! $this->permissions_helper->is_rewrite_and_republish_copy( $post )
			|| ! $this->permissions_helper->is_copy_allowed_to_be_republished( $post )
		) {
			return;
		}

		$original_post = Utils::get_original( $post );

		if ( ! $original_post ) {
			return;
		}

		// Republish taxonomies and meta.
		$this->republish_post_taxonomies( $post );
		$this->republish_post_meta( $post );

		// Republish the post.
		$this->republish_post_elements( $post, $original_post->ID );

		\do_action( 'duplicate_post_after_rewriting', $post, $original_post );

		// Trigger the redirect in the Classic Editor.
		if ( $this->is_classic_editor_post_request() ) {
			$this->redirect( $original_post->ID, $post->ID );
		}
	}

	/**
	 * Republishes the original post with the passed post, when using the Block Editor.
	 *
	 * @param \WP_Post $post The copy's post object.
	 *
	 * @return void
	 */
	public function republish_after_rest_api_request( $post ) {
		$this->republish_request( $post );
	}

	/**
	 * Republishes the original post with the passed post, when using the Classic Editor.
	 *
	 * Runs also in the Block Editor to save the custom meta data only when there
	 * are custom meta boxes.
	 *
	 * @param int      $post_id The copy's post ID.
	 * @param \WP_Post $post    The copy's post object.
	 *
	 * @return void
	 */
	public function republish_after_post_request( $post_id, $post ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		$this->republish_request( $post );
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
		// Determine the new post status.
		$new_post_status = $post_to_be_rewritten['post_status'] === 'private' ? 'private' : 'publish';
		// Prepare post data for republishing.
		$post_to_be_rewritten['ID']          = $original_post_id;
		$post_to_be_rewritten['post_name']   = \get_post_field( 'post_name', $original_post_id );
		$post_to_be_rewritten['post_status'] = $new_post_status;

		// Yoast SEO and other plugins prevent from accidentally updating another
		// post's data (e.g. the Yoast SEO metadata) by checking the $_POST data
		// ID with the post object ID. We need to overwrite the $_POST data ID
		// to allow updating the original post.
		$_POST['ID'] = $original_post_id;
		// Republish the original post.
		$rewritten_post_id = \wp_update_post( \wp_slash( $post_to_be_rewritten ) );

		if ( 0 === $rewritten_post_id ) {
			\wp_die( \esc_html__( 'An error occurred while republishing the post.', 'duplicate-post' ) );
		}
	}

	/**
	 * Republishes the post taxonomies overwriting the ones of the original post.
	 *
	 * @param \WP_Post $post The copy's post object.
	 *
	 * @return void
	 */
	protected function republish_post_taxonomies( $post ) {
		$original_post_id = Utils::get_original_post_id( $post->ID );

		$copy_taxonomies_options = [
			'taxonomies_excludelist' => [],
			'use_filters'            => false,
			'copy_format'            => true,
		];
		$this->post_duplicator->copy_post_taxonomies( $original_post_id, $post, $copy_taxonomies_options );
	}

	/**
	 * Republishes the post meta overwriting the ones of the original post.
	 *
	 * @param \WP_Post $post The copy's post object.
	 *
	 * @return void
	 */
	protected function republish_post_meta( $post ) {
		$original_post_id = Utils::get_original_post_id( $post->ID );

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
		$this->post_duplicator->copy_post_meta_info( $original_post_id, $post, $copy_meta_options );
	}

	/**
	 * Deletes the copied post and temporary metadata.
	 *
	 * @return void
	 */
	public function clean_up_after_redirect() {
		if ( ! empty( $_GET['dprepublished'] ) && ! empty( $_GET['dpcopy'] ) && ! empty( $_GET['post'] ) ) {
			$copy_id = \intval( \wp_unslash( $_GET['dpcopy'] ) );
			$post_id = \intval( \wp_unslash( $_GET['post'] ) );

			\check_admin_referer( 'dp-republish', 'dpnonce' );

			// Delete the copy bypassing the trash so it also deletes the copy post meta.
			\wp_delete_post( $copy_id, true );
			// Delete the meta that marks the original post has having a copy.
			\delete_post_meta( $post_id, '_dp_has_rewrite_republish_copy' );
		}
	}

	/**
	 * Redirects the user to the original post.
	 *
	 * @param int $original_post_id The ID of the original post to redirect to.
	 * @param int $copy_id          The ID of the copy post.
	 *
	 * @return void
	 */
	protected function redirect( $original_post_id, $copy_id ) {
		\wp_safe_redirect(
			\add_query_arg(
				[
					'dprepublished' => 1,
					'dpcopy'        => $copy_id,
					'dpnonce'       => \wp_create_nonce( 'dp-republish' ),
				],
				\admin_url( 'post.php?action=edit&post=' . $original_post_id )
			)
		);
		exit();
	}

	/**
	 * Checks whether a request is the Classic Editor POST request.
	 *
	 * @return bool Whether the request is the Classic Editor POST request.
	 */
	public function is_classic_editor_post_request() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		return isset( $_GET['meta-box-loader'] ) === false; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Adds the default post display states used in the posts list table.
	 *
	 * @param string[] $post_states An array of post display states.
	 * @param \WP_Post  $post        The current post object.
	 *
	 * @return string[] An array of filtered display post states.
	 */
	public function add_rewrite_schedule_display_state( $post_states, $post ) {
		if ( $post->post_status === 'dp-rewrite-schedule' ) {
			$post_states['dp-rewrite-schedule'] = \esc_html__( 'Scheduled', 'duplicate-post' );
		}

		return $post_states;
	}
}
