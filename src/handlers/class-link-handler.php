<?php
/**
 * Duplicate Post handler class for duplication actions from links.
 *
 * @package Duplicate_Post
 * @since 4.0
 */

namespace Yoast\WP\Duplicate_Post\Handlers;

use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Post_Duplicator;

/**
 * Represents the handler for duplication actions from links.
 */
class Link_Handler {

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
		\add_action( 'admin_action_duplicate_post_rewrite', [ $this, 'rewrite_link_action_handler' ] );
		\add_action( 'admin_action_duplicate_post_clone', [ $this, 'clone_link_action_handler' ] );
		\add_action( 'admin_action_duplicate_post_new_draft', [ $this, 'new_draft_link_action_handler' ] );
	}

	/**
	 * Handles the action for copying a post to a new draft.
	 *
	 * @return void
	 */
	public function new_draft_link_action_handler() {
		if ( ! $this->permissions_helper->is_current_user_allowed_to_copy() ) {
			\wp_die( \esc_html__( 'Current user is not allowed to copy posts.', 'duplicate-post' ) );
		}

		if ( ! ( isset( $_GET['post'] ) || isset( $_POST['post'] ) || // Input var okay.
			( isset( $_REQUEST['action'] ) && 'duplicate_post_new_draft' === $_REQUEST['action'] ) ) ) { // Input var okay.
			\wp_die( \esc_html__( 'No post to duplicate has been supplied!', 'duplicate-post' ) );
		}

		$id = ( isset( $_GET['post'] ) ? \intval( \wp_unslash( $_GET['post'] ) ) : \intval( \wp_unslash( $_POST['post'] ) ) ); // Input var okay.

		\check_admin_referer( 'duplicate_post_new_draft_' . $id ); // Input var okay.

		$post = \get_post( $id );

		if ( ! $post ) {
			\wp_die(
				\esc_html(
					\__( 'Copy creation failed, could not find original:', 'duplicate-post' ) . ' '
					. $id
				)
			);
		}

		if ( $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			\wp_die(
				\esc_html__( 'You cannot create a copy of a post which is intended for Rewrite & Republish.', 'duplicate-post' )
			);
		}

		$new_id = \duplicate_post_create_duplicate( $post, 'draft' );

		if ( \is_wp_error( $new_id ) ) {
			\wp_die(
				\esc_html__( 'Copy creation failed, could not create a copy.', 'duplicate-post' )
			);
		}

		\wp_safe_redirect(
			\add_query_arg(
				[
					'cloned' => 1,
					'ids'    => $post->ID,
				],
				\admin_url( 'post.php?action=edit&post=' . $new_id . ( isset( $_GET['classic-editor'] ) ? '&classic-editor' : '' ) )
			)
		);
		exit();
	}

	/**
	 * Handles the action for copying a post and redirecting to the post list.
	 *
	 * @return void
	 */
	public function clone_link_action_handler() {
		if ( ! $this->permissions_helper->is_current_user_allowed_to_copy() ) {
			\wp_die( \esc_html__( 'Current user is not allowed to copy posts.', 'duplicate-post' ) );
		}

		if ( ! ( isset( $_GET['post'] ) || isset( $_POST['post'] ) || // Input var okay.
			( isset( $_REQUEST['action'] ) && 'duplicate_post_clone' === $_REQUEST['action'] ) ) ) { // Input var okay.
			\wp_die( \esc_html__( 'No post to duplicate has been supplied!', 'duplicate-post' ) );
		}

		$id = ( isset( $_GET['post'] ) ? \intval( \wp_unslash( $_GET['post'] ) ) : \intval( \wp_unslash( $_POST['post'] ) ) ); // Input var okay.

		\check_admin_referer( 'duplicate_post_clone_' . $id ); // Input var okay.

		$post = \get_post( $id );

		if ( ! $post ) {
			\wp_die(
				\esc_html(
					\__( 'Copy creation failed, could not find original:', 'duplicate-post' ) . ' '
					. $id
				)
			);
		}

		if ( $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			\wp_die(
				\esc_html__( 'You cannot create a copy of a post which is intended for Rewrite & Republish.', 'duplicate-post' )
			);
		}

		$new_id = \duplicate_post_create_duplicate( $post );

		if ( \is_wp_error( $new_id ) ) {
			\wp_die(
				\esc_html__( 'Copy creation failed, could not create a copy.', 'duplicate-post' )
			);
		}

		$post_type = $post->post_type;
		$sendback  = \wp_get_referer();
		if ( ! $sendback || strpos( $sendback, 'post.php' ) !== false || strpos( $sendback, 'post-new.php' ) !== false ) {
			if ( 'attachment' === $post_type ) {
				$sendback = \admin_url( 'upload.php' );
			} else {
				$sendback = \admin_url( 'edit.php' );
				if ( ! empty( $post_type ) ) {
					$sendback = \add_query_arg( 'post_type', $post_type, $sendback );
				}
			}
		} else {
			$sendback = \remove_query_arg( [ 'trashed', 'untrashed', 'deleted', 'cloned', 'ids' ], $sendback );
		}

		// Redirect to the post list screen.
		\wp_safe_redirect(
			\add_query_arg(
				[
					'cloned' => 1,
					'ids'    => $post->ID,
				],
				$sendback
			)
		);
		exit();
	}

	/**
	 * Handles the action for copying a post for the Rewrite & Republish feature.
	 *
	 * @return void
	 */
	public function rewrite_link_action_handler() {
		if ( ! $this->permissions_helper->is_current_user_allowed_to_copy() ) {
			\wp_die( \esc_html__( 'Current user is not allowed to copy posts.', 'duplicate-post' ) );
		}

		if ( ! ( isset( $_GET['post'] ) || isset( $_POST['post'] ) || // Input var okay.
			( isset( $_REQUEST['action'] ) && 'duplicate_post_rewrite' === $_REQUEST['action'] ) ) ) { // Input var okay.
			\wp_die( \esc_html__( 'No post to duplicate has been supplied!', 'duplicate-post' ) );
		}

		$id = ( isset( $_GET['post'] ) ? \intval( \wp_unslash( $_GET['post'] ) ) : \intval( \wp_unslash( $_POST['post'] ) ) ); // Input var okay.

		\check_admin_referer( 'duplicate_post_rewrite_' . $id ); // Input var okay.

		$post = \get_post( $id );

		if ( ! $post ) {
			\wp_die(
				\esc_html(
					\__( 'Copy creation failed, could not find original:', 'duplicate-post' ) . ' '
					. $id
				)
			);
		}

		if ( ! $this->permissions_helper->should_rewrite_and_republish_be_allowed( $post ) ) {
			\wp_die(
				\esc_html__( 'You cannot create a copy for Rewrite & Republish if the original is not published or if it already has a copy.', 'duplicate-post' )
			);
		}

		$new_id = $this->post_duplicator->create_duplicate_for_rewrite_and_republish( $post );

		if ( \is_wp_error( $new_id ) ) {
			\wp_die(
				\esc_html__( 'Copy creation failed, could not create a copy.', 'duplicate-post' )
			);
		}

		\wp_safe_redirect(
			\add_query_arg(
				[
					'rewriting' => 1,
					'ids'       => $post->ID,
				],
				\admin_url( 'post.php?action=edit&post=' . $new_id . ( isset( $_GET['classic-editor'] ) ? '&classic-editor' : '' ) )
			)
		);
		exit();
	}
}
