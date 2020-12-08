<?php
/**
 * Duplicate Post class to manage the post submitbox.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\UI;

use Yoast\WP\Duplicate_Post\Permissions_Helper;

/**
 * Represents the Post_Submitbox class.
 */
class Post_Submitbox {

	/**
	 * Holds the object to create the action link to duplicate.
	 *
	 * @var Link_Builder
	 */
	protected $link_builder;

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * Initializes the class.
	 *
	 * @param Link_Builder       $link_builder       The link builder.
	 * @param Permissions_Helper $permissions_helper The permissions helper.
	 */
	public function __construct( Link_Builder $link_builder, Permissions_Helper $permissions_helper ) {
		$this->link_builder       = $link_builder;
		$this->permissions_helper = $permissions_helper;

		$this->register_hooks();
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_action( 'post_submitbox_start', [ $this, 'add_new_draft_post_button' ] );
		\add_action( 'post_submitbox_start', [ $this, 'add_rewrite_and_republish_post_button' ] );
	}

	/**
	 * Adds a button in the post/page edit screen to create a clone
	 *
	 * @param \WP_Post|null $post The post object that's being edited.
	 *
	 * @return void
	 */
	public function add_new_draft_post_button( $post = null ) {
		if ( \intval( \get_option( 'duplicate_post_show_submitbox' ) ) !== 1 ) {
			return;
		}

		if ( \is_null( $post ) ) {
			if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$id   = \intval( \wp_unslash( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				$post = \get_post( $id );
			}
		}

		if ( ! \is_null( $post ) ) {
			/** This filter is documented in class-row-action.php */
			if ( \apply_filters(
				'duplicate_post_show_link',
				$this->permissions_helper->should_link_be_displayed( $post ),
				$post
			) ) {
				?>
				<div id="duplicate-action">
					<a class="submitduplicate duplication"
						href="<?php echo \esc_url( $this->link_builder->build_new_draft_link( $post ) ); ?>"><?php \esc_html_e( 'Copy to a new draft', 'duplicate-post' ); ?>
					</a>
				</div>
				<?php
			}
		}
	}

	/**
	 * Adds a button in the post/page edit screen to create a clone for Rewrite & Republish.
	 *
	 * @param \WP_Post|null $post The post object that's being edited.
	 *
	 * @return void
	 */
	public function add_rewrite_and_republish_post_button( $post = null ) {
		if ( \intval( \get_option( 'duplicate_post_show_submitbox' ) ) !== 1 ) {
			return;
		}

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
				$this->permissions_helper->should_link_be_displayed( $post ),
				$post
			) ) {
				?>
				<div id="rewrite-republish-action">
					<a class="submitduplicate duplication" href="<?php echo \esc_url( $this->link_builder->build_rewrite_and_republish_link( $post ) ); ?>"><?php \esc_html_e( 'Rewrite & Republish', 'duplicate-post' ); ?>
					</a>
				</div>
				<?php
			}
		}
	}
}
