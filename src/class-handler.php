<?php
/**
 * Duplicate Post handler class for duplication actions.
 *
 * @package Duplicate_Post
 * @since 4.0
 */

namespace Yoast\WP\Duplicate_Post;

/**
 * Represents the handler for duplication actions.
 */
class Handler {

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
		\add_action( 'admin_action_duplicate_post_rewrite', [ $this, 'rewrite_link_action_handler' ] );
		\add_action( 'admin_action_duplicate_post_clone', [ $this, 'clone_link_action_handler' ] );
		\add_action( 'admin_action_duplicate_post_new_draft', [ $this, 'new_draft_link_action_handler' ] );
		\add_action( 'admin_init', [ $this, 'add_bulk_handlers' ] );
	}

	/**
	 * Handles the action for copying a post to a new draft.
	 *
	 * @return void
	 */
	public function new_draft_link_action_handler() {
		if ( ! Utils::is_current_user_allowed_to_copy() ) {
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

		if ( Utils::is_rewrite_and_republish_copy( $post ) ) {
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
		if ( ! Utils::is_current_user_allowed_to_copy() ) {
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

		if ( Utils::is_rewrite_and_republish_copy( $post ) ) {
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
		$sendback  = wp_get_referer();
		if ( ! $sendback || strpos( $sendback, 'post.php' ) !== false || strpos( $sendback, 'post-new.php' ) !== false ) {
			if ( 'attachment' === $post_type ) {
				$sendback = admin_url( 'upload.php' );
			} else {
				$sendback = admin_url( 'edit.php' );
				if ( ! empty( $post_type ) ) {
					$sendback = add_query_arg( 'post_type', $post_type, $sendback );
				}
			}
		} else {
			$sendback = remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'cloned', 'ids' ), $sendback );
		}

		// Redirect to the post list screen.
		wp_safe_redirect(
			add_query_arg(
				array(
					'cloned' => 1,
					'ids'    => $post->ID,
				),
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
		if ( ! Utils::is_current_user_allowed_to_copy() ) {
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

		if ( $post->post_status !== 'publish' ) {
			\wp_die(
				\esc_html__( 'You cannot create a copy for Rewrite & Republish if the original is not published.', 'duplicate-post' )
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

	/**
	 * Hooks the handler for the Rewrite & Republish action for all the selected post types.
	 *
	 * @return void
	 */
	public function add_bulk_handlers() {
		$duplicate_post_types_enabled = Utils::get_enabled_post_types();

		foreach ( $duplicate_post_types_enabled as $duplicate_post_type_enabled ) {
			\add_filter( "handle_bulk_actions-edit-{$duplicate_post_type_enabled}", [ $this, 'bulk_action_handler' ], 10, 3 );
		}
	}

	/**
	 * Handles the bulk action for the Rewrite & Republish feature.
	 *
	 * @param string $redirect_to The URL to redirect to.
	 * @param string $doaction    The action that has been called.
	 * @param array  $post_ids    The array of marked post IDs.
	 *
	 * @return string The URL to redirect to.
	 */
	public function bulk_action_handler( $redirect_to, $doaction, array $post_ids ) {
		$redirect_to = $this->clone_bulk_action_handler( $redirect_to, $doaction, $post_ids );
		$redirect_to = $this->rewrite_bulk_action_handler( $redirect_to, $doaction, $post_ids );
		return $redirect_to;
	}

	/**
	 * Handles the bulk action for the Rewrite & Republish feature.
	 *
	 * @param string $redirect_to The URL to redirect to.
	 * @param string $doaction    The action that has been called.
	 * @param array  $post_ids    The array of marked post IDs.
	 *
	 * @return string The URL to redirect to.
	 */
	public function rewrite_bulk_action_handler( $redirect_to, $doaction, array $post_ids ) {
		if ( $doaction !== 'duplicate_post_bulk_rewrite_republish' ) {
			return $redirect_to;
		}

		$counter = 0;
		foreach ( $post_ids as $post_id ) {
			$post = \get_post( $post_id );
			if ( ! empty( $post ) && $post->post_status === 'publish' && ! Utils::is_rewrite_and_republish_copy( $post ) ) {
				$new_post_id = $this->post_duplicator->create_duplicate_for_rewrite_and_republish( $post );
				if ( ! \is_wp_error( $new_post_id ) ) {
					$counter++;
				}
			}
		}
		$redirect_to = \add_query_arg( 'bulk_rewriting', $counter, $redirect_to );
		return $redirect_to;
	}

	/**
	 * Handles the bulk action for the Clone feature.
	 *
	 * @param string $redirect_to The URL to redirect to.
	 * @param string $doaction    The action that has been called.
	 * @param array  $post_ids    The array of marked post IDs.
	 *
	 * @return string The URL to redirect to.
	 */
	public function clone_bulk_action_handler( $redirect_to, $doaction, $post_ids ) {
		if ( $doaction !== 'duplicate_post_bulk_clone' ) {
			return $redirect_to;
		}

		$counter = 0;
		foreach ( $post_ids as $post_id ) {
			$post = \get_post( $post_id );
			if ( ! empty( $post ) && ! Utils::is_rewrite_and_republish_copy( $post ) ) {
				if ( \intval( \get_option( 'duplicate_post_copychildren' ) !== 1 )
					|| ! \is_post_type_hierarchical( $post->post_type )
					|| ( \is_post_type_hierarchical( $post->post_type ) && ! Utils::has_ancestors_marked( $post, $post_ids ) )
				) {
					if ( ! \is_wp_error( \duplicate_post_create_duplicate( $post ) ) ) {
						$counter++;
					}
				}
			}
		}
		$redirect_to = \add_query_arg( 'cloned', $counter, $redirect_to );
		return $redirect_to;
	}
}
