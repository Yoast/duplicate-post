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
	 * Holds the current post object.
	 *
	 * @var \WP_Post
	 */
	private $post;

	/**
	 * Holds the original post object.
	 *
	 * @var \WP_Post
	 */
	private $original;

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
		global $wp_version;

		if ( ! ( isset( $_GET['post'] ) || isset( $_POST['post'] ) || // Input var okay.
			( isset( $_REQUEST['action'] ) && 'duplicate_post_check_changes' === $_REQUEST['action'] ) ) ) { // Input var okay.
			\wp_die(
				\esc_html__( 'No post has been supplied!', 'duplicate-post' )
			);
			return;
		}

		$id = ( isset( $_GET['post'] ) ? \intval( \wp_unslash( $_GET['post'] ) ) : \intval( \wp_unslash( $_POST['post'] ) ) ); // Input var okay.

		\check_admin_referer( 'duplicate_post_check_changes_' . $id ); // Input var okay.

		$this->post = \get_post( $id );

		if ( ! $this->post ) {
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

		$this->original = Utils::get_original( $this->post );

		if ( ! $this->original ) {
			\wp_die(
				\esc_html(
					\__( 'Changes overview failed, could not find original post.', 'duplicate-post' )
				)
			);
			return;
		}
		$post_edit_link = \get_edit_post_link( $this->post->ID );

		$this->require_wordpress_header();
		?>
		<div class="wrap">
			<h1 class="long-header">
			<?php
				echo \sprintf(
						/* translators: %s: original item link (to view or edit) or title. */
					\esc_html__( 'Compare changes of duplicated post with the original (&#8220;%s&#8221;)', 'duplicate-post' ),
					Utils::get_edit_or_view_link( $this->original ) // phpcs:ignore WordPress.Security.EscapeOutput
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
							'post_title'   => \__( 'Title', 'default' ),
							'post_content' => \__( 'Content', 'default' ),
							'post_excerpt' => \__( 'Excerpt', 'default' ),
						];

						$args = array(
							'show_split_view' => true,
							'title_left'      => __( 'Removed', 'default' ),
							'title_right'     => __( 'Added', 'default' ),
						);

						if ( \version_compare( $wp_version, '5.7' ) < 0 ) {
							unset( $args['title_left'] );
							unset( $args['title_right'] );
						}

						$post_array = \get_post( $this->post, \ARRAY_A );
						/** This filter is documented in wp-admin/includes/revision.php */
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reason: we want to use a WP filter from the revision feature.
						$fields = \apply_filters( '_wp_post_revision_fields', $fields, $post_array );

						foreach ( $fields as $field => $name ) {
							/** This filter is documented in wp-admin/includes/revision.php */
							// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reason: we want to use a WP filter from the revision feature.
							$content_from = apply_filters( "_wp_post_revision_field_{$field}", $this->original->$field, $field, $this->original, 'from' );

							/** This filter is documented in wp-admin/includes/revision.php */
							// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reason: we want to use a WP filter from the revision feature.
							$content_to = \apply_filters( "_wp_post_revision_field_{$field}", $this->post->$field, $field, $this->post, 'to' );

							$diff = \wp_text_diff( $content_from, $content_to, $args );

							if ( ! $diff && 'post_title' === $field ) {
								// It's a better user experience to still show the Title, even if it didn't change.
								$diff  = '<table class="diff"><colgroup><col class="content diffsplit left"><col class="content diffsplit middle"><col class="content diffsplit right"></colgroup><tbody><tr>';
								$diff .= '<td>' . \esc_html( $this->original->post_title ) . '</td><td></td><td>' . \esc_html( $this->post->post_title ) . '</td>';
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
		global $post;
		\set_current_screen( 'revision' );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The revision screen expects $post to be set.
		$post = $this->post;
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
