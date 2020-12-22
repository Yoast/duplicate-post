<?php
/**
 * Duplicate Post class to manage the post submitbox.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\UI;

use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Utils;

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
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
		if ( \intval( Utils::get_option( 'duplicate_post_show_link_in', 'submitbox' ) ) === 0 ) {
			return;
		}

		if ( \intval( Utils::get_option( 'duplicate_post_show_link', 'new_draft' ) ) === 1 ) {
			\add_action( 'post_submitbox_start', [ $this, 'add_new_draft_post_button' ] );
		}

		if ( \intval( Utils::get_option( 'duplicate_post_show_link', 'rewrite_republish' ) ) === 1 ) {
			\add_action( 'post_submitbox_start', [ $this, 'add_rewrite_and_republish_post_button' ] );
		}

		\add_filter( 'gettext', [ $this, 'change_republish_strings_classic_editor' ], 10, 2 );
		\add_filter( 'gettext_with_context', [ $this, 'change_schedule_strings_classic_editor' ], 10, 3 );
		\add_filter( 'post_updated_messages', [ $this, 'change_scheduled_notice_classic_editor' ], 10, 1 );

		\add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_classic_editor_scripts' ] );
	}

	/**
	 * Enqueues the necessary JavaScript code for the classic editor.
	 *
	 * @return void
	 */
	public function enqueue_classic_editor_scripts() {
		if ( $this->should_change_rewrite_republish_copy( \get_post() ) ) {
			\wp_enqueue_script(
				'duplicate_post_strings',
				\plugins_url(
					\sprintf( 'js/dist/duplicate-post-strings-%s.js', Utils::flatten_version( DUPLICATE_POST_CURRENT_VERSION ) ),
					DUPLICATE_POST_FILE
				),
				[
					'wp-element',
					'wp-i18n',
				],
				DUPLICATE_POST_CURRENT_VERSION,
				true
			);
		}
	}

	/**
	 * Adds a button in the post/page edit screen to create a clone
	 *
	 * @param \WP_Post|null $post The post object that's being edited.
	 *
	 * @return void
	 */
	public function add_new_draft_post_button( $post = null ) {
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
				$this->permissions_helper->should_links_be_displayed( $post ),
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
		if ( \is_null( $post ) ) {
			if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$id   = \intval( \wp_unslash( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				$post = \get_post( $id );
			}
		}

		if ( ! \is_null( $post ) && $post->post_status === 'publish' ) {
			/** This filter is documented in class-row-actions.php */
			if ( \apply_filters(
				'duplicate_post_show_link',
				$this->permissions_helper->should_links_be_displayed( $post ),
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

	/**
	 * Changes the 'Publish' copies in the submitbox to 'Republish' if a post is intended for republishing.
	 *
	 * @param string $translation The translated text.
	 * @param string $text        The text to translate.
	 *
	 * @return string The to-be-used copy of the text.
	 */
	public function change_republish_strings_classic_editor( $translation, $text ) {
		if ( $this->should_change_rewrite_republish_copy( \get_post() ) && $text === 'Publish' ) {
			return \__( 'Republish', 'duplicate-post' );
		}

		return $translation;
	}

	/**
	 * Changes the 'Schedule' copy in the submitbox to 'Schedule republish' if a post is intended for republishing.
	 *
	 * @param string $translation The translated text.
	 * @param string $text        The text to translate.
	 *
	 * @return string The to-be-used copy of the text.
	 */
	public function change_schedule_strings_classic_editor( $translation, $text ) {
		if ( $this->should_change_rewrite_republish_copy( \get_post() ) && $text === 'Schedule' ) {
			return \__( 'Schedule republish', 'duplicate-post' );
		}

		return $translation;
	}

	/**
	 * Changes the post-scheduled notice when a post or page intended for republishing is scheduled.
	 *
	 * @param array[] $messages Post updated messaged.
	 *
	 * @return array[] The to-be-used messages.
	 */
	public function change_scheduled_notice_classic_editor( $messages ) {
		$post = \get_post();
		if ( ! $this->should_change_rewrite_republish_copy( $post ) ) {
			return $messages;
		}

		$permalink      = \get_permalink( $post->ID );
		$scheduled_date = \get_the_time( \get_option( 'date_format' ), $post );
		$scheduled_time = \get_the_time( \get_option( 'time_format' ), $post );

		if ( $post->post_type === 'post' ) {
			$messages['post'][9] = \sprintf(
			/* translators: 1: The post title with a link to the frontend page, 2: The scheduled date and time. */
				\esc_html__(
					'This rewritten post %1$s is now scheduled to replace the original post. It will be published on %2$s.',
					'duplicate-post'
				),
				'<a href="' . $permalink . '">' . $post->post_title . '</a>',
				'<strong>' . $scheduled_date . ' ' . $scheduled_time . '</strong>'
			);
			return $messages;
		}

		if ( $post->post_type === 'page' ) {
			$messages['page'][9] = \sprintf(
					/* translators: 1: The page title with a link to the frontend page, 2: The scheduled date and time. */
				\esc_html__(
					'This rewritten page %1$s is now scheduled to replace the original page. It will be published on %2$s.',
					'duplicate-post'
				),
				'<a href="' . $permalink . '">' . $post->post_title . '</a>',
				'<strong>' . $scheduled_date . ' ' . $scheduled_time . '</strong>'
			);
		}

		return $messages;
	}

	/**
	 * Determines if the Rewrite & Republish copies for the post should be used.
	 *
	 * @param \WP_Post $post The current post object.
	 *
	 * @return bool True if the Rewrite & Republish copies should be used.
	 */
	public function should_change_rewrite_republish_copy( $post ) {
		global $pagenow;
		if ( ! \in_array( $pagenow, [ 'post.php', 'post-new.php' ], true ) ) {
			return false;
		}

		if ( \is_null( $post ) ) {
			return false;
		}

		return $this->permissions_helper->is_rewrite_and_republish_copy( $post );
	}
}
