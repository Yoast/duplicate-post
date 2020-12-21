<?php
/**
 * Duplicate Post plugin file.
 *
 * @package Yoast\WP\Duplicate_Post\Admin
 */

namespace Yoast\WP\Duplicate_Post\Admin;

use Yoast\WP\Duplicate_Post\UI\Asset_Manager;
use Yoast\WP\Duplicate_Post\Utils;

/**
 * Class Options_Page
 */
class Options_Page {
	/**
	 * The Options instance.
	 *
	 * @var Options
	 */
	protected $options;

	/**
	 * The Options_Form_Generator instance.
	 *
	 * @var Options_Form_Generator
	 */
	protected $generator;

	/**
	 * Holds the asset manager.
	 *
	 * @var Asset_Manager
	 */
	protected $asset_manager;

	/**
	 * Options_Page constructor.
	 *
	 * @param Options                $options       The Options class instance.
	 * @param Options_Form_Generator $generator     The Options_Form_Generator class instance.
	 * @param Asset_Manager          $asset_manager The Asset_Manager class instance.
	 */
	public function __construct( Options $options, Options_Form_Generator $generator, Asset_Manager $asset_manager ) {
		$this->options       = $options;
		$this->generator     = $generator;
		$this->asset_manager = $asset_manager;
	}

	/**
	 * Registers the necessary hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		if ( \is_admin() ) {
			\add_action( 'admin_menu', [ $this, 'register_menu' ] );
			\add_action( 'admin_init', [ $this->options, 'register_settings' ] );
		}
	}

	/**
	 * Enqueues the assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$this->asset_manager->enqueue_options_styles();
		$this->asset_manager->enqueue_options_script();
	}

	/**
	 * Registers the menu item.
	 *
	 * @return void
	 */
	public function register_menu() {
		$page_hook = \add_options_page(
			\__( 'Duplicate Post Options', 'duplicate-post' ),
			\__( 'Duplicate Post', 'duplicate-post' ),
			'manage_options',
			'duplicatepost',
			[ $this, 'generate_page' ]
		);

		\add_action( $page_hook, [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Generates the inputs for the specified tab / fieldset.
	 *
	 * @param string $tab      The tab to get the configuration for.
	 * @param string $fieldset The fieldset to get the configuration for. Optional.
	 *
	 * @return string The HTML output for the controls present on the tab / fieldset.
	 * @codeCoverageIgnore As this is a simple wrapper for two functions that are already tested elsewhere, we can skip testing.
	 */
	public function generate_tab_inputs( $tab, $fieldset = '' ) {
		$options = $this->options->get_options_for_tab( $tab, $fieldset );

		return $this->generator->generate_options_input( $options );
	}

	/**
	 * Generates an input for a single option.
	 *
	 * @param string $option The option configuration to base the input on.
	 *
	 * @return string The input HTML.
	 * @codeCoverageIgnore As this is a simple wrapper for two functions that are already tested elsewhere, we can skip testing.
	 */
	public function generate_input( $option ) {
		return $this->generator->generate_options_input( $this->options->get_option( $option ) );
	}

	/**
	 * Registers the proper capabilities.
	 *
	 * @return void
	 */
	public function register_capabilities() {
		if ( ! \current_user_can( 'promote_users' ) || ! $this->settings_updated() ) {
			return;
		}

		$roles = $this->get_duplicate_post_roles();

		foreach ( Utils::get_roles() as $name => $display_name ) {
			$role = \get_role( $name );

			if ( ! $role->has_cap( 'copy_posts' ) && \in_array( $name, $roles, true ) ) {
				/* If the role doesn't have the capability and it was selected, add it. */
				$role->add_cap( 'copy_posts' );
			}

			if ( $role->has_cap( 'copy_posts' ) && ! \in_array( $name, $roles, true ) ) {
				/* If the role has the capability and it wasn't selected, remove it. */
				$role->remove_cap( 'copy_posts' );
			}
		}
	}

	/**
	 * Generates the options page.
	 *
	 * @return void
	 * @codeCoverageIgnore
	 */
	public function generate_page() {
		$this->register_capabilities();

		require_once DUPLICATE_POST_PATH . 'src/admin/views/options.php';
	}

	/**
	 * Checks whether settings have been updated.
	 *
	 * @return bool Whether or not the settings have been updated.
	 */
	protected function settings_updated() {
		return isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true'; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Gets the registered custom roles.
	 *
	 * @return array The roles. Returns an empty array if there are none.
	 */
	protected function get_duplicate_post_roles() {
		$roles = \get_option( 'duplicate_post_roles' );

		if ( empty( $roles ) ) {
			$roles = [];
		}

		return $roles;
	}
}
