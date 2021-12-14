<?php

namespace Yoast\WP\Duplicate_Post;

use WP_Post;

/**
 * Duplicate Post class to republish a rewritten post.
 *
 * @since 4.0
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

		$enabled_post_types = $this->permissions_helper->get_enabled_post_types();
		foreach ( $enabled_post_types as $enabled_post_type ) {
			/**
			 * Called in the REST API when submitting the post copy in the Block Editor.
			 * Runs the republishing of the copy onto the original.
			 */
			\add_action( "rest_after_insert_{$enabled_post_type}", [ $this, 'republish_after_rest_api_request' ] );
		}

		/**
		 * Called by `wp_insert_post()` when submitting the post copy, which runs in two cases:
		 * - In the Classic Editor, where there's only one request that updates everything.
		 * - In the Block Editor, only when there are custom meta boxes.
		 */
		\add_action( 'wp_insert_post', [ $this, 'republish_after_post_request' ], \PHP_INT_MAX, 2 );

		// Clean up after the redirect to the original post.
		\add_action( 'load-post.php', [ $this, 'clean_up_after_redirect' ] );
		// Clean up the original when the copy is manually deleted from the trash.
		\add_action( 'before_delete_post', [ $this, 'clean_up_when_copy_manually_deleted' ] );
		// Ensure scheduled Rewrite and Republish posts are properly handled.
		\add_action( 'future_to_publish', [ $this, 'republish_scheduled_post' ] );
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
		$options = [
			'label'                     => \__( 'Republish', 'duplicate-post' ),
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => false,
		];

		\register_post_status( 'dp-rewrite-republish', $options );
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
		if ( ! \array_key_exists( 'ID', $postarr ) || empty( $postarr['ID'] ) ) {
			return $data;
		}

		$post = \get_post( $postarr['ID'] );

		if ( ! $post || ! $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			return $data;
		}

		if ( $data['post_status'] === 'publish' ) {
			$data['post_status'] = 'dp-rewrite-republish';
		}

		return $data;
	}

	/**
	 * Executes the republish request.
	 *
	 * @param WP_Post $post The copy's post object.
	 *
	 * @return void
	 */
	public function republish_request( $post ) {
		if (
			! $post instanceof WP_Post
			|| ! $this->permissions_helper->is_rewrite_and_republish_copy( $post )
			|| ! $this->permissions_helper->is_copy_allowed_to_be_republished( $post )
		) {
			return;
		}

		$original_post = Utils::get_original( $post->ID );

		if ( ! $original_post ) {
			return;
		}

		$this->republish( $post, $original_post );

		// Trigger the redirect in the Classic Editor.
		if ( $this->is_classic_editor_post_request() ) {
			$this->redirect( $original_post->ID, $post->ID );
		}
	}

	/**
	 * Republishes the original post with the passed post, when using the Block Editor.
	 *
	 * @param WP_Post $post The copy's post object.
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
	 * @param int     $post_id The copy's post ID.
	 * @param WP_Post $post    The copy's post object.
	 *
	 * @return void
	 */
	public function republish_after_post_request( $post_id, $post ) {
		if ( $this->is_rest_request() ) {
			return;
		}

		$this->republish_request( $post );
	}

	/**
	 * Republishes the scheduled Rewrited and Republish post.
	 *
	 * @param WP_Post $copy The scheduled copy.
	 *
	 * @return void
	 */
	public function republish_scheduled_post( $copy ) {
		if ( ! $copy instanceof WP_Post || ! $this->permissions_helper->is_rewrite_and_republish_copy( $copy ) ) {
			return;
		}

		$original_post = Utils::get_original( $copy->ID );

		// If the original post was permanently deleted, we don't want to republish, so trash instead.
		if ( ! $original_post ) {
			$this->delete_copy( $copy->ID, null, false );

			return;
		}

		$this->republish( $copy, $original_post );
		$this->delete_copy( $copy->ID, $original_post->ID );
	}

	/**
	 * Cleans up the copied post and temporary metadata after the user has been redirected.
	 *
	 * @return void
	 */
	public function clean_up_after_redirect() {
		if ( ! empty( $_GET['dprepublished'] ) && ! empty( $_GET['dpcopy'] ) && ! empty( $_GET['post'] ) ) {
			$copy_id = \intval( \wp_unslash( $_GET['dpcopy'] ) );
			$post_id = \intval( \wp_unslash( $_GET['post'] ) );

			\check_admin_referer( 'dp-republish', 'dpnonce' );

			if ( \intval( \get_post_meta( $copy_id, '_dp_has_been_republished', true ) ) === 1 ) {
				$this->delete_copy( $copy_id, $post_id );
			}
			else {
				\wp_die( \esc_html__( 'An error occurred while deleting the Rewrite & Republish copy.', 'duplicate-post' ) );
			}
		}
	}

	/**
	 * Checks whether a request is the Classic Editor POST request.
	 *
	 * @return bool Whether the request is the Classic Editor POST request.
	 */
	public function is_classic_editor_post_request() {
		if ( $this->is_rest_request() || \wp_doing_ajax() ) {
			return false;
		}

		return isset( $_GET['meta-box-loader'] ) === false;
	}

	/**
	 * Determines whether the current request is a REST request.
	 *
	 * @return bool Whether or not the request is a REST request.
	 */
	public function is_rest_request() {
		return \defined( 'REST_REQUEST' ) && \REST_REQUEST;
	}

	/**
	 * Republishes the post by overwriting the original post.
	 *
	 * @param WP_Post $post          The Rewrite & Republish copy.
	 * @param WP_Post $original_post The original post.
	 *
	 * @return void
	 */
	public function republish( WP_Post $post, WP_Post $original_post ) {
		// Remove WordPress default filter so a new revision is not created on republish.
		\remove_action( 'post_updated', 'wp_save_post_revision', 10 );

		// Republish taxonomies and meta.
		$this->republish_post_taxonomies( $post );
		$this->republish_post_meta( $post );

		// Republish the post.
		$this->republish_post_elements( $post, $original_post );

		// Mark the copy as already published.
		\update_post_meta( $post->ID, '_dp_has_been_republished', '1' );

		// Re-enable the creation of a new revision.
		\add_action( 'post_updated', 'wp_save_post_revision', 10, 1 );
	}

	/**
	 * Deletes the copy and associated post meta, if applicable.
	 *
	 * @param int      $copy_id            The copy's ID.
	 * @param int|null $post_id            The original post's ID. Optional.
	 * @param bool     $permanently_delete Whether to permanently delete the copy. Defaults to true.
	 *
	 * @return void
	 */
	public function delete_copy( $copy_id, $post_id = null, $permanently_delete = true ) {
		/**
		 * Fires before deleting a Rewrite & Republish copy.
		 *
		 * @param int $copy_id The copy's ID.
		 * @param int $post_id The original post's ID..
		 */
		\do_action( 'duplicate_post_after_rewriting', $copy_id, $post_id );

		// Delete the copy bypassing the trash so it also deletes the copy post meta.
		\wp_delete_post( $copy_id, $permanently_delete );

		if ( ! \is_null( $post_id ) ) {
			// Delete the meta that marks the original post has having a copy.
			\delete_post_meta( $post_id, '_dp_has_rewrite_republish_copy' );
		}
	}

	/**
	 * Republishes the post elements overwriting the original post.
	 *
	 * @param WP_Post $post          The post object.
	 * @param WP_Post $original_post The original post.
	 *
	 * @return void
	 */
	protected function republish_post_elements( $post, $original_post ) {
		// Cast to array and not alter the copy's original object.
		$post_to_be_rewritten = clone $post;

		// Prepare post data for republishing.
		$post_to_be_rewritten->ID          = $original_post->ID;
		$post_to_be_rewritten->post_name   = $original_post->post_name;
		$post_to_be_rewritten->post_status = $this->determine_post_status( $post, $original_post );

		/**
		 * Yoast SEO and other plugins prevent from accidentally updating another post's
		 * data (e.g. the Yoast SEO metadata by checking the $_POST data ID with the post object ID.
		 * We need to overwrite the $_POST data ID to allow updating the original post.
		 */
		$_POST['ID'] = $original_post->ID;

		// Republish the original post.
		$rewritten_post_id = \wp_update_post( \wp_slash( $post_to_be_rewritten ) );

		if ( $rewritten_post_id === 0 ) {
			\wp_die( \esc_html__( 'An error occurred while republishing the post.', 'duplicate-post' ) );
		}
	}

	/**
	 * Republishes the post taxonomies overwriting the ones of the original post.
	 *
	 * @param WP_Post $post The copy's post object.
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
	 * @param WP_Post $post The copy's post object.
	 *
	 * @return void
	 */
	protected function republish_post_meta( $post ) {
		$original_post_id = Utils::get_original_post_id( $post->ID );

		$copy_meta_options = [
			'meta_excludelist' => Utils::get_default_filtered_meta_names(),
			'use_filters'      => false,
			'copy_thumbnail'   => true,
			'copy_template'    => true,
		];
		$this->post_duplicator->copy_post_meta_info( $original_post_id, $post, $copy_meta_options );
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
	 * Determines the post status to use when publishing the Rewrite & Republish copy.
	 *
	 * @param WP_Post $post          The post object.
	 * @param WP_Post $original_post The original post object.
	 *
	 * @return string The post status to use.
	 */
	protected function determine_post_status( $post, $original_post ) {
		if ( $original_post->post_status === 'trash' ) {
			return 'trash';
		}

		if ( $post->post_status === 'private' ) {
			return 'private';
		}

		return 'publish';
	}

	/**
	 * Deletes the original post meta that flags it as having a copy when the copy is manually deleted.
	 *
	 * @param int $post_id Post ID of a post that is going to be deleted.
	 *
	 * @return void
	 */
	public function clean_up_when_copy_manually_deleted( $post_id ) {
		$post = \get_post( $post_id );

		if ( ! $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			return;
		}

		$original_post_id = Utils::get_original_post_id( $post_id );
		\delete_post_meta( $original_post_id, '_dp_has_rewrite_republish_copy' );
	}
}
