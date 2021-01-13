<?php
/**
 * Duplicate Post class to manage the classic editor UI.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\UI;

use WP_Post;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Utils;

/**
 * Represents the Classic_Editor class.
 */
class Classic_Editor {

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
	 * Holds the asset manager.
	 *
	 * @var Asset_Manager
	 */
	protected $asset_manager;

	/**
	 * Initializes the class.
	 *
	 * @param Link_Builder       $link_builder       The link builder.
	 * @param Permissions_Helper $permissions_helper The permissions helper.
	 * @param Asset_Manager      $asset_manager      The asset manager.
	 */
	public function __construct( Link_Builder $link_builder, Permissions_Helper $permissions_helper, Asset_Manager $asset_manager ) {
		$this->link_builder       = $link_builder;
		$this->permissions_helper = $permissions_helper;
		$this->asset_manager      = $asset_manager;
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_action( 'post_submitbox_misc_actions', [ $this, 'add_check_changes_link' ], 90 );

		if ( \intval( Utils::get_option( 'duplicate_post_show_link_in', 'submitbox' ) ) === 1 ) {
			if ( \intval( Utils::get_option( 'duplicate_post_show_link', 'new_draft' ) ) === 1 ) {
				\add_action( 'post_submitbox_start', [ $this, 'add_new_draft_post_button' ] );
			}

			if ( \intval( Utils::get_option( 'duplicate_post_show_link', 'rewrite_republish' ) ) === 1 ) {
				\add_action( 'post_submitbox_start', [ $this, 'add_rewrite_and_republish_post_button' ] );
			}
		}

		\add_filter( 'gettext', [ $this, 'change_republish_strings_classic_editor' ], 10, 2 );
		\add_filter( 'gettext_with_context', [ $this, 'change_schedule_strings_classic_editor' ], 10, 3 );
		\add_filter( 'post_updated_messages', [ $this, 'change_scheduled_notice_classic_editor' ], 10, 1 );

		\add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_classic_editor_scripts' ] );
		\add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_classic_editor_styles' ] );

		// Remove slug editing from Classic Editor.
		\add_action( 'add_meta_boxes', [ $this, 'remove_slug_meta_box' ], 10, 2 );
		\add_filter( 'get_sample_permalink_html', [ $this, 'remove_sample_permalink_slug_editor' ], 10, 5 );
	}

	/**
	 * Enqueues the necessary JavaScript code for the Classic editor.
	 *
	 * @return void
	 */
	public function enqueue_classic_editor_scripts() {
		if ( $this->permissions_helper->is_classic_editor() && isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$id   = \intval( \wp_unslash( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$post = \get_post( $id );

			if ( ! \is_null( $post ) && $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
				$this->asset_manager->enqueue_strings_script();
			}
		}
	}

	/**
	 * Enqueues the necessary styles for the Classic editor.
	 *
	 * @return void
	 */
	public function enqueue_classic_editor_styles() {
		if ( $this->permissions_helper->is_classic_editor()
			&& isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$id   = \intval( \wp_unslash( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$post = \get_post( $id );

			if ( ! \is_null( $post ) && $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
				$this->asset_manager->enqueue_styles();
			}
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

		if ( $post instanceof WP_Post && $this->permissions_helper->should_links_be_displayed( $post ) ) {
			?>
			<div id="duplicate-action">
				<a class="submitduplicate duplication"
					href="<?php echo \esc_url( $this->link_builder->build_new_draft_link( $post ) ); ?>"><?php \esc_html_e( 'Copy to a new draft', 'duplicate-post' ); ?>
				</a>
			</div>
			<?php
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

		if (
			$post instanceof WP_Post
			&& $this->permissions_helper->should_rewrite_and_republish_be_allowed( $post )
			&& $this->permissions_helper->should_links_be_displayed( $post )
		) {
			?>
			<div id="rewrite-republish-action">
				<a class="submitduplicate duplication" href="<?php echo \esc_url( $this->link_builder->build_rewrite_and_republish_link( $post ) ); ?>"><?php \esc_html_e( 'Rewrite & Republish', 'duplicate-post' ); ?>
				</a>
			</div>
			<?php
		}
	}

	/**
	 * Adds a message in the post/page edit screen to create a clone for Rewrite & Republish.
	 *
	 * @param \WP_Post|null $post The post object that's being edited.
	 *
	 * @return void
	 */
	public function add_check_changes_link( $post = null ) {
		if ( \is_null( $post ) ) {
			if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$id   = \intval( \wp_unslash( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				$post = \get_post( $id );
			}
		}

		if ( $post instanceof WP_Post && $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			?>
				<div id="check-changes-action">
					<?php \esc_html_e( 'Do you want to compare your changes with the original version before merging? Please save any changes first.', 'duplicate-post' ); ?>
					<br><br>
					<a class='button' href=<?php echo \esc_url( $this->link_builder->build_check_link( $post ) ); ?>>
						<?php \esc_html_e( 'Compare', 'duplicate-post' ); ?>
					</a>
				</div>
				<?php
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
		if ( $this->should_change_rewrite_republish_copy( \get_post() ) ) {
			if ( $text === 'Publish' ) {
				return \__( 'Republish', 'duplicate-post' );
			}

			if ( $text === 'Publish on: %s' ) {
				/* translators: %s: Date on which the post is to be republished. */
				return \__( 'Republish on: %s', 'duplicate-post' );
			}
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

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		return $this->permissions_helper->is_rewrite_and_republish_copy( $post );
	}

	/**
	 * Removes the slug meta box in the Classic Editor when the post is a Rewrite & Republish copy.
	 *
	 * @param string   $post_type Post type.
	 * @param \WP_Post $post      Post object.
	 *
	 * @return void
	 */
	public function remove_slug_meta_box( $post_type, $post ) {
		if ( $post instanceof WP_Post && $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			\remove_meta_box( 'slugdiv', $post_type, 'normal' );
		}
	}

	/**
	 * Removes the sample permalink slug editor in the Classic Editor when the post is a Rewrite & Republish copy.
	 *
	 * @param string   $return    Sample permalink HTML markup.
	 * @param int      $post_id   Post ID.
	 * @param string   $new_title New sample permalink title.
	 * @param string   $new_slug  New sample permalink slug.
	 * @param \WP_Post $post      Post object.
	 *
	 * @return string The filtered HTML of the sample permalink slug editor.
	 */
	public function remove_sample_permalink_slug_editor( $return, $post_id, $new_title, $new_slug, $post ) {
		if ( ! $post instanceof WP_Post ) {
			return $return;
		}

		if ( $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			return '';
		}

		return $return;
	}
}
