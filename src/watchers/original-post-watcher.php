<?php

namespace Yoast\WP\Duplicate_Post\Watchers;

use WP_Post;
use Yoast\WP\Duplicate_Post\Permissions_Helper;

/**
 * Duplicate Post Original post watcher class.
 *
 * Watches the original post for changes.
 *
 * @since 4.0
 */
class Original_Post_Watcher {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * Initializes the class.
	 *
	 * @param Permissions_Helper $permissions_helper The Permissions helper object.
	 */
	public function __construct( Permissions_Helper $permissions_helper ) {
		$this->permissions_helper = $permissions_helper;

		$this->register_hooks();
	}

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_action( 'admin_notices', [ $this, 'add_admin_notice' ] );
		\add_action( 'enqueue_block_editor_assets', [ $this, 'add_block_editor_notice' ], 11 );
	}

	/**
	 * Generates the translated text for the notice.
	 *
	 * @return string The translated text for the notice.
	 */
	public function get_notice_text() {
		return \__(
			'The original post has been edited in the meantime. If you click "Republish", this rewritten post will replace the original post.',
			'duplicate-post'
		);
	}

	/**
	 * Shows a notice on the Classic editor.
	 *
	 * @return void
	 */
	public function add_admin_notice() {
		if ( ! $this->permissions_helper->is_classic_editor() ) {
			return;
		}

		$post = \get_post();

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		if ( $this->permissions_helper->has_original_changed( $post ) ) {
			print '<div id="message" class="notice notice-warning is-dismissible fade"><p>'
				. \esc_html( $this->get_notice_text() )
				. '</p></div>';
		}
	}

	/**
	 * Shows a notice on the Block editor.
	 *
	 * @return void
	 */
	public function add_block_editor_notice() {
		$post = \get_post();

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		if ( $this->permissions_helper->has_original_changed( $post ) ) {

			$notice = [
				'text'          => \wp_slash( $this->get_notice_text() ),
				'status'        => 'warning',
				'isDismissible' => true,
			];

			\wp_add_inline_script(
				'duplicate_post_edit_script',
				"duplicatePostNotices.has_original_changed_notice = '" . \wp_json_encode( $notice ) . "';",
				'before'
			);
		}
	}
}
