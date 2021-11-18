<?php

namespace Yoast\WP\Duplicate_Post\UI;

use WP_Post;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Utils;

/**
 * Duplicate Post class to manage the custom column + quick edit.
 */
class Column {

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
	 * @param Permissions_Helper $permissions_helper The permissions helper.
	 * @param Asset_Manager      $asset_manager      The asset manager.
	 */
	public function __construct( Permissions_Helper $permissions_helper, Asset_Manager $asset_manager ) {
		$this->permissions_helper = $permissions_helper;
		$this->asset_manager      = $asset_manager;
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
		if ( \intval( \get_option( 'duplicate_post_show_original_column' ) ) === 1 ) {
			$enabled_post_types = $this->permissions_helper->get_enabled_post_types();
			if ( \count( $enabled_post_types ) ) {
				foreach ( $enabled_post_types as $enabled_post_type ) {
					\add_filter( "manage_{$enabled_post_type}_posts_columns", [ $this, 'add_original_column' ] );
					\add_action( "manage_{$enabled_post_type}_posts_custom_column", [ $this, 'show_original_item' ], 10, 2 );
				}
				\add_action( 'quick_edit_custom_box', [ $this, 'quick_edit_remove_original' ], 10, 2 );
				\add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
				\add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_styles' ] );
			}
		}
	}

	/**
	 * Adds Original item column to the post list.
	 *
	 * @param array $post_columns The post columns array.
	 *
	 * @return array The updated array.
	 */
	public function add_original_column( $post_columns ) {
		if ( \is_array( $post_columns ) ) {
			$post_columns['duplicate_post_original_item'] = \__( 'Original item', 'duplicate-post' );
		}
		return $post_columns;
	}

	/**
	 * Sets the text to be displayed in the Original item column for the current post.
	 *
	 * @param string $column_name The name for the current column.
	 * @param int    $post_id     The ID for the current post.
	 *
	 * @return void
	 */
	public function show_original_item( $column_name, $post_id ) {
		if ( $column_name === 'duplicate_post_original_item' ) {
			$column_content = '-';
			$data_attr      = ' data-no-original="1"';
			$original_item  = Utils::get_original( $post_id );
			if ( $original_item ) {
				$post      = \get_post( $post_id );
				$data_attr = '';

				if ( $post instanceof WP_Post
					&& $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
					$data_attr = ' data-copy-is-for-rewrite-and-republish="1"';
				}

				$column_content = Utils::get_edit_or_view_link( $original_item );
			}
			echo \sprintf(
				'<span class="duplicate_post_original_link"%s>%s</span>',
				$data_attr, // phpcs:ignore WordPress.Security.EscapeOutput
				$column_content // phpcs:ignore WordPress.Security.EscapeOutput
			);
		}
	}

	/**
	 * Adds original item checkbox + edit link in the Quick Edit.
	 *
	 * @param string $column_name The name for the current column.
	 *
	 * @return void
	 */
	public function quick_edit_remove_original( $column_name ) {
		if ( $column_name !== 'duplicate_post_original_item' ) {
			return;
		}
		\printf(
			'<fieldset class="inline-edit-col-left" id="duplicate_post_quick_edit_fieldset">
			<div class="inline-edit-col">
				<input type="checkbox"
				name="duplicate_post_remove_original"
				id="duplicate-post-remove-original"
				value="duplicate_post_remove_original"
				aria-describedby="duplicate-post-remove-original-description">
				<label for="duplicate-post-remove-original">
					<span class="checkbox-title">%s</span>
				</label>
				<span id="duplicate-post-remove-original-description" class="checkbox-title">%s</span>
			</div>
		</fieldset>',
			\esc_html__(
				'Delete reference to original item.',
				'duplicate-post'
			),
			\wp_kses(
				\__(
					'The original item this was copied from is: <span class="duplicate_post_original_item_title_span"></span>',
					'duplicate-post'
				),
				[
					'span' => [
						'class' => [],
					],
				]
			)
		);
	}

	/**
	 * Enqueues the Javascript file to inject column data into the Quick Edit.
	 *
	 * @param string $hook The current admin page.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( $hook === 'edit.php' ) {
			$this->asset_manager->enqueue_quick_edit_script();
		}
	}

	/**
	 * Enqueues the CSS file to for the Quick edit
	 *
	 * @param string $hook The current admin page.
	 *
	 * @return void
	 */
	public function admin_enqueue_styles( $hook ) {
		if ( $hook === 'edit.php' ) {
			$this->asset_manager->enqueue_styles();
		}
	}
}
