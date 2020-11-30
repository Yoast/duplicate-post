<?php
/**
 * Duplicate Post user interface.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post;

/**
 * Represents the Duplicate Post User Interface class.
 */
class User_Interface {

	/**
	 * Holds the post.
	 *
	 * @var \WP_Post
	 */
	private $post = null;

	/**
	 * Holds the global `$pagenow` variable's value.
	 *
	 * @var string
	 */
	private $pagenow;

	/**
	 * Holds the object to manage the row actions for the post.
	 *
	 * @var Row_Action
	 */
	protected $rewrite_and_republish_row_action;

	/**
	 * Holds the object to create the action link to duplicate.
	 *
	 * @var Link_Builder
	 */
	protected $link_builder;

	/**
	 * Admin notices object.
	 *
	 * @var Admin_Notices
	 */
	private $admin_notices;

	/**
	 * Initializes the class.
	 */
	public function __construct() {
		global $pagenow;
		$this->pagenow = $pagenow;

		$this->link_builder                     = new Rewrite_Republish_Link_Builder();
		$this->rewrite_and_republish_row_action = new Row_Action( $this->link_builder );
		$this->admin_notices                    = new Admin_Notices();

		$this->register_hooks();
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_scripts' ] );
		\add_action( 'admin_enqueue_scripts', [ $this, 'should_previously_used_keyword_assessment_run' ], 9 );

		\add_action( 'post_submitbox_start', [ $this, 'add_rewrite_and_republish_post_button' ], 11 );
		\add_action( 'wp_before_admin_bar_render', [ $this, 'admin_bar_render' ] );

		\add_action( 'admin_init', [ $this, 'add_bulk_filters' ] );
	}

	/**
	 * Disables the Yoast SEO PreviouslyUsedKeyword assessment for posts duplicated for Rewrite & Republish.
	 *
	 * @return void
	 */
	public function should_previously_used_keyword_assessment_run() {
		if ( ! \in_array( $this->pagenow, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		if ( ! $this->post ) {
			$this->post = \get_post();
		}

		$skip_assessment = \get_post_meta( $this->post->ID, '_dp_is_rewrite_republish_copy', true );

		if ( ! empty( $skip_assessment ) ) {
			\add_filter( 'wpseo_previously_used_keyword_active', '__return_false' );
		}
	}

	/**
	 * Enqueues the necessary JavaScript code for the block editor.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_scripts() {
		\wp_enqueue_script(
			'duplicate_post_edit_script',
			\plugins_url( \sprintf( 'js/dist/duplicate-post-edit-%s.js', Utils::flatten_version( DUPLICATE_POST_CURRENT_VERSION ) ), DUPLICATE_POST_FILE ),
			[
				'wp-blocks',
				'wp-element',
				'wp-i18n',
			],
			DUPLICATE_POST_CURRENT_VERSION,
			true
		);

		\wp_localize_script(
			'duplicate_post_edit_script',
			'duplicatePostRewriteRepost',
			[
				'permalink'        => $this->get_rewrite_republish_permalink(),
				'rewriting'        => ( ! empty( $_REQUEST['rewriting'] ) ) ? 1 : 0, // phpcs:ignore WordPress.Security.NonceVerification
				'originalEditURL'  => $this->get_original_post_edit_url(),
				'republished'      => ( ! empty( $_REQUEST['republished'] ) ) ? 1 : 0, // phpcs:ignore WordPress.Security.NonceVerification
				'republishedText'  => ( ! empty( $_REQUEST['republished'] ) ) ? Utils::get_republished_notice_text() : '', // phpcs:ignore WordPress.Security.NonceVerification
			]
		);
	}

	/**
	 * Generates a URL to the original post edit screen.
	 *
	 * @return string The URL. Empty if the copy post doesn't have an original.
	 */
	public function get_original_post_edit_url() {
		if ( ! $this->post ) {
			$this->post = \get_post();
		}

		$original_post_id = Utils::get_original_post_id( $this->post->ID );

		if ( ! $original_post_id ) {
			return '';
		}

		return \add_query_arg(
			[
				'republished' => 1,
			],
			\admin_url( 'post.php?action=edit&post=' . $original_post_id )
		);
	}

	/**
	 * Generates a Rewrite & Republish permalink for the current post.
	 *
	 * @return string The permalink. Returns empty if the post hasn't been published yet.
	 */
	public function get_rewrite_republish_permalink() {
		if ( ! $this->post ) {
			$this->post = \get_post();
		}

		if ( $this->post->post_status !== 'publish' ) {
			return '';
		}

		return $this->link_builder->build_link( $this->post );
	}

	/**
	 * Shows Rewrite & Republish link in the Toolbar.
	 *
	 * @global \WP_Query     $wp_the_query
	 * @global \WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance.
	 */
	public function admin_bar_render() {
		global $wp_the_query;
		global $wp_admin_bar;

		if ( \is_admin() ) {
			$this->post = \get_post();
		} else {
			$this->post = $wp_the_query->get_queried_object();
		}

		if ( empty( $this->post ) ) {
			return;
		}

		/** This filter is documented in duplicate-post-admin.php */
		if ( ! \apply_filters( 'duplicate_post_show_link', \duplicate_post_is_current_user_allowed_to_copy(), $this->post ) ) {
			return;
		}

		if ( ! \duplicate_post_is_valid_post_edit_screen() || ! \duplicate_post_can_copy_to_draft( $this->post ) ) {
			return;
		}

		if ( $this->post->post_status !== 'publish' ) {
			return;
		}

		$wp_admin_bar->add_menu(
			[
				'id'     => 'rewrite_republish',
				'parent' => 'new_draft',
				'title'  => \esc_attr__( 'Rewrite & Republish', 'duplicate-post' ),
				'href'   => $this->get_rewrite_republish_permalink(),
			]
		);
	}

	/**
	 * Hooks the function to add the Rewrite & Republish option in the bulk actions for the selected post types.
	 *
	 * @return void
	 */
	public function add_bulk_filters() {
		if ( \intval( \get_option( 'duplicate_post_show_bulkactions' ) ) !== 1 ) {
			return;
		}
		if ( ! \duplicate_post_is_current_user_allowed_to_copy() ) {
			return;
		}

		$duplicate_post_types_enabled = Utils::get_enabled_post_types();
		foreach ( $duplicate_post_types_enabled as $duplicate_post_type_enabled ) {
			\add_filter( "bulk_actions-edit-{$duplicate_post_type_enabled}", [ $this, 'register_bulk_action' ] );
		}
	}

	/**
	 * Adds a button in the post/page edit screen to create a clone.
	 *
	 * @param \WP_Post|null $post The post object that's being edited.
	 *
	 * @return void
	 */
	public function add_rewrite_and_republish_post_button( $post = null ) {
		if ( \is_null( $post ) ) {
			if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$id   = \intval( \wp_unslash( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				$post = \get_post( $id );
			}
		}

		if ( ! \is_null( $post ) && $post->post_status === 'publish' ) {
			/** This filter is documented in duplicate-post-admin.php */
			if ( \apply_filters(
				'duplicate_post_show_link',
				\duplicate_post_is_current_user_allowed_to_copy() && \duplicate_post_is_post_type_enabled( $post->post_type ),
				$post
			) ) {
				?>
				<div id="rewrite-republish-action">
					<a class="submitduplicate duplication" href="<?php echo esc_url( $this->link_builder->build_link( $this->post ) ); ?>"><?php \esc_html_e( 'Rewrite & Republish', 'duplicate-post' ); ?>
					</a>
				</div>
				<?php
			}
		}
	}

	/**
	 * Adds 'Rewrite & Republish' to the bulk action dropdown.
	 *
	 * @param array $bulk_actions The bulk actions array.
	 * @return array The bulk actions array.
	 */
	public function register_bulk_action( $bulk_actions ) {
		$bulk_actions['duplicate_post_rewrite_republish'] = \esc_html__( 'Rewrite & Republish', 'duplicate-post' );

		return $bulk_actions;
	}
}
