<?php
/**
 * Duplicate Post class to manage the metabox.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\UI;

use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Utils;

/**
 * Represents the Metabox class.
 */
class Metabox {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * Initializes the class.
	 *
	 * @param Permissions_Helper $permissions_helper The permissions helper.
	 */
	public function __construct( Permissions_Helper $permissions_helper ) {
		$this->permissions_helper = $permissions_helper;
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
		if ( \intval( \get_option( 'duplicate_post_show_original_meta_box' ) ) === 1 ) {
			\add_action( 'add_meta_boxes', [ $this, 'add_custom_metabox' ] );
		}
	}

	/**
	 * Adds a metabox to Edit screen.
	 *
	 * @return void
	 */
	public function add_custom_metabox() {
		$screens = $this->permissions_helper->get_enabled_post_types();
		if ( ! \is_array( $screens ) ) {
			$screens = [ $screens ];
		}
		foreach ( $screens as $screen ) {
			\add_meta_box(
				'duplicate_post_show_original',
				\__( 'Duplicate Post', 'duplicate-post' ),
				[ $this, 'custom_metabox_html' ],
				$screen,
				'side'
			);
		}
	}

	/**
	 * Outputs the HTML for the metabox.
	 *
	 * @param \WP_Post $post The current post.
	 *
	 * @return void
	 */
	public function custom_metabox_html( $post ) {
		$original_item = Utils::get_original( $post );
		if ( $post instanceof \WP_Post && $original_item instanceof \WP_Post ) {
			if ( ! $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
				?>
			<p>
				<input type="checkbox"
					name="duplicate_post_remove_original"
					id="duplicate-post-remove-original"
					value="duplicate_post_remove_original"
					aria-describedby="duplicate-post-remove-original-description">
				<label for="duplicate-post-remove-original">
					<?php \esc_html_e( 'Delete reference to original item.', 'duplicate-post' ); ?>
				</label>
			</p>
				<?php
			}
			?>
			<p id="duplicate-post-remove-original-description">
				<?php
				\printf(
					\wp_kses(
					/* translators: %s: post title */
						\__(
							'The original item this was copied from is: <span class="duplicate_post_original_item_title_span">%s</span>',
							'duplicate-post'
						),
						[
							'span' => [
								'class' => [],
							],
						]
					),
					Utils::get_edit_or_view_link( $original_item )  // phpcs:ignore WordPress.Security.EscapeOutput
				);
				?>
			</p>
			<?php
		} else {
			?>
			<script>
				(function(jQuery){
					jQuery('#duplicate_post_show_original').hide();
				})(jQuery);
			</script>
			<?php
		}
	}
}
