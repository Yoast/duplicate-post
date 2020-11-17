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
	 * Handler object.
	 *
	 * @var Handler
	 */
	private $handler;

	/**
	 * Initializes the class.
	 *
	 * @param Handler $handler The handler object.
	 */
	public function __construct( $handler ) {
		global $pagenow;
		$this->pagenow = $pagenow;

		$this->handler                          = $handler;
		$this->rewrite_and_republish_row_action = new Row_Action();
		$this->link_builder                     = new Rewrite_Republish_Link_Builder();

		$this->register_hooks();
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	private function register_hooks() {
		\add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_scripts' ] );
		\add_filter( 'post_row_actions', [ $this->rewrite_and_republish_row_action, 'add_action_link' ], 10, 2 );
		\add_filter( 'page_row_actions', [ $this->rewrite_and_republish_row_action, 'add_action_link' ], 10, 2 );
		\add_action( 'admin_enqueue_scripts', [ $this, 'should_previously_used_keyword_assessment_run' ], 9 );
		\add_action( 'admin_init', [ $this, 'add_bulk_filters' ] );
		\add_action( 'post_submitbox_start', [ $this, 'add_rewrite_and_republish_post_button' ] );
		\add_filter( 'removable_query_args', [ $this, 'add_removable_query_args' ] );
		\add_action( 'admin_notices', [ $this, 'single_action_admin_notice' ] );
		\add_action( 'admin_notices', [ $this, 'bulk_action_admin_notice' ] );
		\add_action( 'wp_before_admin_bar_render', [ $this, 'admin_bar_render' ] );
	}

	/**
	 * Adds 'rewriting' to the removable query args.
	 *
	 * @ignore
	 *
	 * @param array $removable_query_args Array of query args keys.
	 * @return array
	 */
	public function add_removable_query_args( $removable_query_args ) {
		$removable_query_args[] = 'rewriting';
		$removable_query_args[] = 'bulk_rewriting';
		return $removable_query_args;
	}

	/**
	 * Disables the Yoast SEO PreviouslyUsedKeyword assessment for posts duplicated for Rewrite & Republish.
	 *
	 * @return void
	 */
	public function should_previously_used_keyword_assessment_run() {
		if ( ! \in_array( $this->pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
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
			array(
				'wp-blocks',
				'wp-element',
				'wp-i18n',
			),
			DUPLICATE_POST_CURRENT_VERSION,
			true
		);

		\wp_localize_script(
			'duplicate_post_edit_script',
			'duplicatePostRewriteRepost',
			[
				'permalink' => $this->get_rewrite_republish_permalink(),
				'rewriting' => ( ! empty( $_REQUEST['rewriting'] ) ) ? 1 : 0,  // phpcs:ignore WordPress.Security.NonceVerification
			]
		);
	}

	/**
	 * Generates a rewrite and republish permalink for the current post.
	 *
	 * @return string The permalink. Returns empty if the post hasn't been published yet.
	 */
	public function get_rewrite_republish_permalink() {
		if ( ! $this->post ) {
			$this->post = \get_post();
		}

		// phpcs:ignore WordPress.PHP.YodaConditions
		if ( $this->post->post_status !== 'publish' ) {
			return '';
		}

		return $this->link_builder->build_link( $this->post );
	}

	/**
	 * Shows Rewrite and Republish link in the Toolbar.
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
			$this->postpost = $wp_the_query->get_queried_object();
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

		$wp_admin_bar->add_menu(
			array(
				'id'     => 'rewrite_republish',
				'parent' => 'new_draft',
				'title'  => \esc_attr__( 'Rewrite & Republish', 'duplicate-post' ),
				'href'   => $this->get_rewrite_republish_permalink(),
			)
		);
	}

	/**
	 * Adds the handlers for bulk actions.
	 *
	 * @ignore
	 */
	public function add_bulk_filters() {
		if ( intval( \get_option( 'duplicate_post_show_bulkactions' ) ) !== 1 ) {
			return;
		}
		if ( ! \duplicate_post_is_current_user_allowed_to_copy() ) {
			return;
		}
		$duplicate_post_types_enabled = \get_option( 'duplicate_post_types_enabled', array( 'post', 'page' ) );
		if ( ! is_array( $duplicate_post_types_enabled ) ) {
			$duplicate_post_types_enabled = array( $duplicate_post_types_enabled );
		}
		foreach ( $duplicate_post_types_enabled as $duplicate_post_type_enabled ) {
			add_filter( "bulk_actions-edit-{$duplicate_post_type_enabled}", [ $this, 'register_bulk_action' ] );
			add_filter( "handle_bulk_actions-edit-{$duplicate_post_type_enabled}", [ $this->handler, 'bulk_action_handler' ], 10, 3 );
		}
	}

	/**
	 * Adds a button in the post/page edit screen to create a clone
	 *
	 * @param \WP_Post|null $post The post object that's being edited.
	 */
	public function add_rewrite_and_republish_post_button( $post = null ) {
		if ( is_null( $post ) ) {
			if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$id   = intval( \wp_unslash( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				$post = \get_post( $id );
			}
		}

		if ( ! is_null( $post ) && $post->post_status === 'publish' ) {
			/** This filter is documented in duplicate-post-admin.php */
			if ( \apply_filters(
				'duplicate_post_show_link',
				\duplicate_post_is_current_user_allowed_to_copy() && \duplicate_post_is_post_type_enabled( $post->post_type ),
				$post
			) ) {
				?>
				<div id="rewrite-republish-action">
					<a class="submitduplicate duplication" href="<?php echo \esc_url( $this->link_builder->build_link( $this->post ) ); ?>"><?php \esc_html_e( 'Rewrite & Republish', 'duplicate-post' ); ?>
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
		$bulk_actions['duplicate_post_rewrite_republish'] = esc_html__( 'Rewrite & Republish', 'duplicate-post' );

		return $bulk_actions;
	}

	/**
	 * Shows a notice after the copy has succeeded.
	 */
	public function single_action_admin_notice() {
		if ( ! empty( $_REQUEST['rewriting'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div id="message" class="notice notice-warning fade"><p>';
			esc_html_e(
				'You can now start rewriting your post in this duplicate of the original post. If you click "Republish", your changes will be merged into the original post and you’ll be redirected there.',
				'duplicate-post'
			);
			echo '</p></div>';
			\remove_query_arg( 'rewriting' );
		}
	}

	/**
	 * Shows a notice after the copy has succeeded.
	 */
	public function bulk_action_admin_notice() {
		if ( ! empty( $_REQUEST['bulk_rewriting'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$copied_posts = intval( $_REQUEST['bulk_rewriting'] ); // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div id="message" class="notice notice-success fade"><p>';
			printf(
				\esc_html(
				/* translators: %s: Number of posts copied. */
					_n(
						'%s item copied.',
						'%s items copied.',
						$copied_posts,
						'duplicate-post'
					)
				) . ' ',
				\esc_html( $copied_posts )
			);
			esc_html_e(
				'You can now start rewriting your posts in the duplicates of the original posts. Once you choose to republish them your changes will be merged back into the original post.',
				'duplicate-post'
			);
			echo '</p></div>';
			\remove_query_arg( 'bulk_rewriting' );
		}
	}
}
