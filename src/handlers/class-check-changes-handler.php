<?php
/**
 * Duplicate Post handler class for changes overview.
 *
 * @package Duplicate_Post
 * @since 4.0
 */

namespace Yoast\WP\Duplicate_Post\Handlers;

use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Utils;

/**
 * Represents the handler for checking the changes between a copy and the original post.
 */
class Check_Changes_Handler {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * Initializes the class.
	 *
	 * @param Permissions_Helper $permissions_helper The Permissions Helper object.
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
		\add_action( 'admin_action_duplicate_post_check_changes', [ $this, 'check_changes_action_handler' ] );
	}

	/**
	 * Handles the action for displaying the changes between a copy and the original.
	 *
	 * @return void
	 */
	public function check_changes_action_handler() {
		if ( ! ( isset( $_GET['post'] ) || isset( $_POST['post'] ) || // Input var okay.
			( isset( $_REQUEST['action'] ) && 'duplicate_post_check_changes' === $_REQUEST['action'] ) ) ) { // Input var okay.
			\wp_die(
				\esc_html__( 'No post has been supplied!', 'duplicate-post' )
			);
			return;
		}

		$id = ( isset( $_GET['post'] ) ? \intval( \wp_unslash( $_GET['post'] ) ) : \intval( \wp_unslash( $_POST['post'] ) ) ); // Input var okay.

		\check_admin_referer( 'duplicate_post_check_changes_' . $id ); // Input var okay.

		$post = \get_post( $id );

		if ( ! $post ) {
			\wp_die(
				\esc_html(
					\sprintf(
						/* translators: %s: post ID. */
						\__( 'Changes overview failed, could not find post with ID %s.', 'duplicate-post' ),
						$id
					)
				)
			);
			return;
		}

		$original = Utils::get_original( $post );

		if ( ! $original ) {
			\wp_die(
				\esc_html(
					\__( 'Changes overview failed, could not find original post.', 'duplicate-post' )
				)
			);
			return;
		}
		$post_edit_link = \get_edit_post_link( $post->ID );

		$this->require_wordpress_header();
		?>
		<div class="wrap">
			<h1 class="long-header">
			<?php
				echo \sprintf(
						/* translators: %s: original item link (to view or edit) or title. */
					\esc_html__( 'Compare changes of duplicated post with the original (&#8220;%s&#8221;)', 'duplicate-post' ),
					Utils::get_edit_or_view_link( $original ) // phpcs:ignore WordPress.Security.EscapeOutput
				);
			?>
				</h1>
			<a href="<?php echo \esc_url( $post_edit_link ); ?>"><?php \esc_html_e( '&larr; Return to editor', 'default' ); ?></a>
			<div class="revisions">
				<div class="revisions-control-frame">
					<div class="revisions-controls"></div>
				</div>
				<div class="revisions-diff-frame">
					<div class="revisions-diff">
						<div class="diff">
						<?php
						$fields = [
							\__( 'Title', 'default' )   => 'post_title',
							\__( 'Content', 'default' ) => 'post_content',
							\__( 'Excerpt', 'default' ) => 'post_excerpt',
						];

						foreach ( $fields as $name => $field ) {
							$diff = \wp_text_diff( $original->$field, $post->$field );

							if ( ! $diff && 'post_title' === $field ) {
								// It's a better user experience to still show the Title, even if it didn't change.
								$diff  = '<table class="diff"><colgroup><col class="content diffsplit left"><col class="content diffsplit middle"><col class="content diffsplit right"></colgroup><tbody><tr>';
								$diff .= '<td>' . \esc_html( $original->post_title ) . '</td><td></td><td>' . \esc_html( $post->post_title ) . '</td>';
								$diff .= '</tr></tbody>';
								$diff .= '</table>';
							}

							if ( $diff ) {
								?>
								<h3><?php echo \esc_html( $name ); ?></h3>
								<?php
									echo $diff; // phpcs:ignore WordPress.Security.EscapeOutput
							}
						}
						?>

						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		$this->require_wordpress_footer();
	}

	/**
	 * Requires the WP admin header.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return void
	 */
	public function require_wordpress_header() {
		require_once ABSPATH . 'wp-admin/admin-header.php';
	}

	/**
	 * Requires the WP admin footer.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return void
	 */
	public function require_wordpress_footer() {
		require_once ABSPATH . 'wp-admin/admin-footer.php';
	}
}
