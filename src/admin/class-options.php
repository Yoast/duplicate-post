<?php

/**
 * Class Duplicate_Post_Options
 */
class Duplicate_Post_Options {

	/**
	 * Duplicate_Post_Options constructor.
	 */
	public function __construct() {
	}

	/**
	 * Registers the settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		foreach ( array_keys( $this->get_options() ) as $option ) {
			register_setting( 'duplicate_post_group', $option );
		}
	}

	/**
	 * Show options page.
	 *
	 * @global WP_Roles $wp_roles   WordPress User Roles.
	 * @global string   $wp_version The WordPress version string.
	 */
	public function duplicate_post_options() {
		$this->set_roles();


	}

	public function generate_options_input( array $options, $parent_option = '' ) {
		global $wp_version;

		$output = '';

		foreach ( $options as $option => $option_values ) {
			// Skip empty options.
			if ( empty( $option_values ) ) {
				continue;
			}

			if ( array_key_exists( 'version', $option_values ) && version_compare( $wp_version, $option_values['version'] ) < 0 ) {
				continue;
			}

			if ( array_key_exists( 'sub_options', $option_values ) ) {
				$output .= $this->generate_options_input( $option_values['sub_options'], $option );

				continue;
			}

			// If callback, call it.
			if ( array_key_exists( 'callback', $option_values ) ) {
				$output .= $this->{$option_values['callback']}();

				continue;
			}

			if ( ! array_key_exists( 'type', $option_values ) ) {
				continue;
			}

			$id = str_replace( '_', '-', $option );

			if ( $parent_option !== '' ) {
				$id     = sprintf( '%s-%s', $parent_option, $id );
				$option = sprintf( '%s[%s]', $parent_option, $option );
			}

			switch( $option_values['type'] ) {
				case 'checkbox':
					$output .= Duplicate_Post_Options_Inputs::checkbox( $option, $option_values['value'], $id );
					$output .= sprintf( '<label for="%s">%s</label>', $id, $option_values['label'] );
					break;
				case 'text':
					$output .= sprintf( '<label for="%s">%s</label>', $id, $option_values['label'] );
					$output .= Duplicate_Post_Options_Inputs::text( $option, $option_values['value'], $id );
					break;
				case 'number':
					$output .= sprintf( '<label for="%s">%s</label>', $id, $option_values['label'] );
					$output .= Duplicate_Post_Options_Inputs::number( $option, $option_values['value'], $id );

					break;
			}

			if ( array_key_exists( 'description', $option_values ) ) {
				$output .= $this->extract_description( $option_values['description'], $id );
			}

			$output .= '<br />';
		}

		return $output;
	}

	protected function extract_description( $description, $id ) {
		if ( ! is_array( $description ) ) {
			return sprintf( '<span id="%s-description">(%s)</span>', $id, $description );
		}

		return sprintf( '<p id="%s-description">(%s)</p>', $id, implode( $description, '<br />' ) );
	}

	/**
	 * Sets the proper roles and capabilities.
	 *
	 * @return void
	 */
	public function set_roles() {
		global $wp_roles;

		if ( ! current_user_can( 'promote_users' ) || ! $this->settings_updated() ) {
			return;
		}

		$dp_roles = $this->get_roles();

		foreach ( $wp_roles->get_names() as $name => $display_name ) {
			$role = get_role( $name );

			if ( ! $role->has_cap( 'copy_posts' ) && in_array( $name, $dp_roles, true ) ) {
				/* If the role doesn't have the capability and it was selected, add it. */
				$role->add_cap( 'copy_posts' );
			}

			if ( $role->has_cap( 'copy_posts' ) && ! in_array( $name, $dp_roles, true ) ) {
				/* If the role has the capability and it wasn't selected, remove it. */
				$role->remove_cap( 'copy_posts' );
			}
		}
	}

	protected function get_options() {
		return [
			// Checkboxes
			'duplicate_post_copytitle'                    => [
				'tab'   => 'what-to-copy',
				'type'  => 'checkbox',
				'label' => esc_html( 'Title', 'default' ),
				'value' => 1,
			],
			'duplicate_post_copydate'                     => [
				'tab'   => 'what-to-copy',
				'type'  => 'checkbox',
				'label' => esc_html( 'Date', 'default' ),
				'value' => 1,
			],
			'duplicate_post_copystatus'                   => [
				'tab'   => 'what-to-copy',
				'type'  => 'checkbox',
				'label' => esc_html( 'Status', 'default' ),
				'value' => 1,
			],
			'duplicate_post_copyslug'                     => [
				'tab'   => 'what-to-copy',
				'type'  => 'checkbox',
				'label' => esc_html( 'Slug', 'default' ),
				'value' => 1,
			],
			'duplicate_post_copyexcerpt'                  => [
				'tab'   => 'what-to-copy',
				'type'  => 'checkbox',
				'label' => esc_html( 'Excerpt', 'default' ),
				'value' => 1,
			],
			'duplicate_post_copycontent'                  => [
				'tab'   => 'what-to-copy',
				'type'  => 'checkbox',
				'label' => esc_html( 'Content', 'default' ),
				'value' => 1,
			],
			'duplicate_post_copythumbnail'                => [
				'tab'   => 'what-to-copy',
				'type'  => 'checkbox',
				'label' => esc_html( 'Featured Image', 'default' ),
				'value' => 1,
			],
			'duplicate_post_copytemplate'                 => [
				'tab'   => 'what-to-copy',
				'type'  => 'checkbox',
				'label' => esc_html( 'Template', 'default' ),
				'value' => 1,
			],
			'duplicate_post_copyformat'                   => [
				'tab'   => 'what-to-copy',
				'type'  => 'checkbox',
				'label' => esc_html_x( 'Format', 'post format', 'default' ),
				'value' => 1,
			],
			'duplicate_post_copyauthor'                   => [
				'tab'   => 'what-to-copy',
				'type'  => 'checkbox',
				'label' => esc_html( 'Author', 'default' ),
				'value' => 1,
			],
			'duplicate_post_copypassword'                 => [
				'tab'   => 'what-to-copy',
				'type'  => 'checkbox',
				'label' => esc_html( 'Password', 'default' ),
				'value' => 1,
			],
			'duplicate_post_copyattachments'              => [
				'tab'         => 'what-to-copy',
				'type'        => 'checkbox',
				'label'       => esc_html( 'Attachments', 'default' ),
				'value'       => 1,
				'description' => esc_html( 'you probably want this unchecked, unless you have very special requirements', 'duplicate-post' ),
			],
			'duplicate_post_copychildren'                 => [
				'tab'   => 'what-to-copy',
				'type'  => 'checkbox',
				'label' => esc_html( 'Children', 'default' ),
				'value' => 1,
			],
			'duplicate_post_copycomments'                 => [
				'tab'         => 'what-to-copy',
				'type'        => 'checkbox',
				'label'       => esc_html( 'Comments', 'default' ),
				'value'       => 1,
				'description' => esc_html( 'except pingbacks and trackbacks', 'duplicate-post' ),
			],
			'duplicate_post_copymenuorder'                => [
				'tab'   => 'what-to-copy',
				'type'  => 'checkbox',
				'label' => esc_html( 'Menu order', 'default' ),
				'value' => 1,
			],
			'duplicate_post_title_prefix'                 => [
				'tab'         => 'what-to-copy',
				'type'        => 'text',
				'label'       => esc_html( 'Title prefix', 'duplicate-post' ),
				'value'       => form_option( 'duplicate_post_title_prefix' ),
				'description' => esc_html( 'Prefix to be added before the title, e.g. "Copy of" (blank for no prefix)', 'duplicate-post' ),
			],
			'duplicate_post_title_suffix'                 => [
				'tab'         => 'what-to-copy',
				'type'        => 'text',
				'label'       => esc_html( 'Title suffix', 'duplicate-post' ),
				'value'       => form_option( 'duplicate_post_title_suffix' ),
				'description' => esc_html( 'Suffix to be added after the title, e.g. "(dup)" (blank for no suffix)', 'duplicate-post' ),
			],
			// Number options
			'duplicate_post_increase_menu_order_by'       => [
				'tab'         => 'what-to-copy',
				'type'        => 'number',
				'label'       => esc_html( 'Increase menu order by', 'duplicate-post' ),
				'value'       => form_option( 'duplicate_post_increase_menu_order_by' ),
				'description' => esc_html( 'Add this number to the original menu order (blank or zero to retain the value)', 'duplicate-post' ),
			],

			// Text options
			'duplicate_post_blacklist'                    => [
				'tab'         => 'what-to-copy',
				'type'        => 'text',
				'label'       => esc_html( 'Do not copy these fields', 'duplicate-post' ),
				'value'       => form_option( 'duplicate_post_blacklist' ),
				'description' => [
					esc_html( 'Comma-separated list of meta fields that must not be copied.', 'duplicate-post' ),
					esc_html( 'You can use * to match zero or more alphanumeric characters or underscores: e.g. field*', 'duplicate-post' ),
				],
			],
			// Custom
			'duplicate_post_taxonomies_blacklist'         => [
				'tab'      => 'what-to-copy',
				'callback' => 'generate_taxonomy_exclusion_list',
			],
			'duplicate_post_roles'                        => [
				'tab'      => 'permissions',
				'callback' => 'generate_roles_permission_list',
			],
			'duplicate_post_types_enabled'                => [
				'tab'      => 'permissions',
				'callback' => 'generate_post_types_list',
			],
			// Checkbox
			'duplicate_post_show_row'                     => [
				'tab'   => 'display',
				'type'  => 'checkbox',
				'label' => esc_html( 'Post list', 'duplicate-post' ),
				'value' => 1,
			],
			'duplicate_post_show_adminbar'                => [
				'tab'         => 'display',
				'type'        => 'checkbox',
				'label'       => esc_html( 'Admin bar', 'duplicate-post' ),
				'value'       => 1,
				'description' => esc_html( 'now works on Edit screen too - check this option to use with Gutenberg enabled', 'duplicate-post' ),
			],
			'duplicate_post_show_submitbox'               => [
				'tab'   => 'display',
				'type'  => 'checkbox',
				'label' => esc_html( 'Edit screen', 'duplicate-post' ),
				'value' => 1,
			],
			'duplicate_post_show_bulkactions'             => [
				'tab'     => 'display',
				'type'    => 'checkbox',
				'label'   => esc_html( 'Bulk Actions', 'default' ),
				'value'   => 1,
				'version' => '4.7',
			],
			'duplicate_post_show_original_meta_box'       => [
				'tab'         => 'display',
				'type'        => 'checkbox',
				'label'       => esc_html( 'In a metabox in the Edit screen [Classic editor]', 'duplicate-post' ),
				'value'       => 1,
				'description' => [
					esc_html( "you'll also be able to delete the reference to the original item with a checkbox", 'duplicate-post' ),
				],
			],
			'duplicate_post_show_original_column'         => [
				'tab'         => 'display',
				'type'        => 'checkbox',
				'label'       => esc_html( 'In a column in the Post list', 'duplicate-post' ),
				'value'       => 1,
				'description' => [
					esc_html( "you'll also be able to delete the reference to the original item with a checkbox in Quick Edit", 'duplicate-post' ),
				],
			],
			'duplicate_post_show_original_in_post_states' => [
				'tab'   => 'display',
				'type'  => 'checkbox',
				'label' => esc_html( 'After the title in the Post list', 'duplicate-post' ),
				'value' => 1,
			],
			'duplicate_post_show_notice'                  => [
				'tab'   => 'display',
				'type'  => 'checkbox',
				'label' => esc_html( 'Show update notice', 'duplicate-post' ),
				'value' => 1,
			],
			'duplicate_post_show_link'                    => [
				'tab'         => 'display',
				'sub_options' => [
					'new_draft'         => [
						'type'  => 'checkbox',
						'label' => esc_html( 'New Draft', 'duplicate-post' ),
						'value' => 1,
					],
					'clone'             => [
						'type'  => 'checkbox',
						'label' => esc_html( 'Clone', 'duplicate-post' ),
						'value' => 1,
					],
					'rewrite_republish' => [
						'type'  => 'checkbox',
						'label' => esc_html( 'Rewrite & Republish', 'duplicate-post' ),
						'value' => 1,
					],
				],
			],
		];
	}

	/**
	 * Gets the registered custom roles.
	 *
	 * @return array The roles. Returns an empty array if there are none.
	 */
	protected function get_roles() {
		$roles = get_option( 'duplicate_post_roles' );

		if ( empty( $roles ) ) {
			$roles = [];
		}

		return $roles;
	}

	/**
	 * Checks whether settings have been updated.
	 *
	 * @return bool Whether or not the settings have been updated.
	 */
	protected function settings_updated() {
		return isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true'; // phpcs:ignore WordPress.Security.NonceVerification
	}

	protected function generate_taxonomy_exclusion_list() {
	}

	protected function generate_roles_permission_list() {
	}

	protected function generate_post_types_list() {
	}
}
