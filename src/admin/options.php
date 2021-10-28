<?php

namespace Yoast\WP\Duplicate_Post\Admin;

/**
 * Options class.
 *
 * @since 4.0
 */
class Options {

	/**
	 * Registers the settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		foreach ( \array_keys( $this->get_options() ) as $option ) {
			\register_setting( 'duplicate_post_group', $option );
		}
	}

	/**
	 * Gets the options for the specified tab.
	 *
	 * Also allows filtering on a particular fieldset.
	 *
	 * @param string $tab      The tab to get the options for.
	 * @param string $fieldset The fieldset to get the options for. Optional.
	 *
	 * @return array The options for the specified tab.
	 */
	public function get_options_for_tab( $tab, $fieldset = '' ) {
		$options = $this->get_options();

		$options = \array_filter(
			$options,
			static function ( $option ) use ( $tab ) {
				return \array_key_exists( 'tab', $option ) && $option['tab'] === $tab;
			}
		);

		if ( empty( $options ) ) {
			return [];
		}

		// If a fieldset is specified, filter out the corresponding options.
		if ( ! empty( $fieldset ) ) {
			$options = \array_filter(
				$options,
				static function ( $option ) use ( $fieldset ) {
					return \array_key_exists( 'fieldset', $option ) && $option['fieldset'] === $fieldset;
				}
			);
		}

		return $options;
	}

	/**
	 * Gets an option from the options array, based on its name.
	 *
	 * @param string $name The name of the option to retrieve.
	 *
	 * @return array The option. Empty array if it does not exist.
	 */
	public function get_option( $name ) {
		$options = $this->get_options();

		return \array_key_exists( $name, $options ) ? [ $name => $options[ $name ] ] : [];
	}

	/**
	 * Gets the list of registered options.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return array The options.
	 */
	public function get_options() {
		return [
			'duplicate_post_copytitle'                    => [
				'tab'      => 'what-to-copy',
				'fieldset' => 'elements-to-copy',
				'type'     => 'checkbox',
				'label'    => \__( 'Title', 'duplicate-post' ),
				'value'    => 1,
			],
			'duplicate_post_copydate'                     => [
				'tab'      => 'what-to-copy',
				'fieldset' => 'elements-to-copy',
				'type'     => 'checkbox',
				'label'    => \__( 'Date', 'duplicate-post' ),
				'value'    => 1,
			],
			'duplicate_post_copystatus'                   => [
				'tab'      => 'what-to-copy',
				'fieldset' => 'elements-to-copy',
				'type'     => 'checkbox',
				'label'    => \__( 'Status', 'duplicate-post' ),
				'value'    => 1,
			],
			'duplicate_post_copyslug'                     => [
				'tab'      => 'what-to-copy',
				'fieldset' => 'elements-to-copy',
				'type'     => 'checkbox',
				'label'    => \__( 'Slug', 'duplicate-post' ),
				'value'    => 1,
			],
			'duplicate_post_copyexcerpt'                  => [
				'tab'      => 'what-to-copy',
				'fieldset' => 'elements-to-copy',
				'type'     => 'checkbox',
				'label'    => \__( 'Excerpt', 'duplicate-post' ),
				'value'    => 1,
			],
			'duplicate_post_copycontent'                  => [
				'tab'      => 'what-to-copy',
				'fieldset' => 'elements-to-copy',
				'type'     => 'checkbox',
				'label'    => \__( 'Content', 'duplicate-post' ),
				'value'    => 1,
			],
			'duplicate_post_copythumbnail'                => [
				'tab'      => 'what-to-copy',
				'fieldset' => 'elements-to-copy',
				'type'     => 'checkbox',
				'label'    => \__( 'Featured Image', 'duplicate-post' ),
				'value'    => 1,
			],
			'duplicate_post_copytemplate'                 => [
				'tab'      => 'what-to-copy',
				'fieldset' => 'elements-to-copy',
				'type'     => 'checkbox',
				'label'    => \__( 'Template', 'duplicate-post' ),
				'value'    => 1,
			],
			'duplicate_post_copyformat'                   => [
				'tab'      => 'what-to-copy',
				'fieldset' => 'elements-to-copy',
				'type'     => 'checkbox',
				'label'    => \__( 'Post format', 'duplicate-post' ),
				'value'    => 1,
			],
			'duplicate_post_copyauthor'                   => [
				'tab'      => 'what-to-copy',
				'fieldset' => 'elements-to-copy',
				'type'     => 'checkbox',
				'label'    => \__( 'Author', 'duplicate-post' ),
				'value'    => 1,
			],
			'duplicate_post_copypassword'                 => [
				'tab'      => 'what-to-copy',
				'fieldset' => 'elements-to-copy',
				'type'     => 'checkbox',
				'label'    => \__( 'Password', 'duplicate-post' ),
				'value'    => 1,
			],
			'duplicate_post_copyattachments'              => [
				'tab'         => 'what-to-copy',
				'fieldset'    => 'elements-to-copy',
				'type'        => 'checkbox',
				'label'       => \__( 'Attachments', 'duplicate-post' ),
				'value'       => 1,
				'description' => \__( 'you probably want this unchecked, unless you have very special requirements', 'duplicate-post' ),
			],
			'duplicate_post_copychildren'                 => [
				'tab'      => 'what-to-copy',
				'fieldset' => 'elements-to-copy',
				'type'     => 'checkbox',
				'label'    => \__( 'Children', 'duplicate-post' ),
				'value'    => 1,
			],
			'duplicate_post_copycomments'                 => [
				'tab'         => 'what-to-copy',
				'fieldset'    => 'elements-to-copy',
				'type'        => 'checkbox',
				'label'       => \__( 'Comments', 'duplicate-post' ),
				'value'       => 1,
				'description' => \__( 'except pingbacks and trackbacks', 'duplicate-post' ),
			],
			'duplicate_post_copymenuorder'                => [
				'tab'      => 'what-to-copy',
				'fieldset' => 'elements-to-copy',
				'type'     => 'checkbox',
				'label'    => \__( 'Menu order', 'duplicate-post' ),
				'value'    => 1,
			],
			'duplicate_post_title_prefix'                 => [
				'tab'         => 'what-to-copy',
				'type'        => 'text',
				'label'       => \__( 'Title prefix', 'duplicate-post' ),
				'value'       => \get_option( 'duplicate_post_title_prefix' ),
				'description' => [ \__( 'Prefix to be added before the title, e.g. "Copy of" (blank for no prefix)', 'duplicate-post' ) ],
			],
			'duplicate_post_title_suffix'                 => [
				'tab'         => 'what-to-copy',
				'type'        => 'text',
				'label'       => \__( 'Title suffix', 'duplicate-post' ),
				'value'       => \get_option( 'duplicate_post_title_suffix' ),
				'description' => [ \__( 'Suffix to be added after the title, e.g. "(dup)" (blank for no suffix)', 'duplicate-post' ) ],
			],
			'duplicate_post_increase_menu_order_by'       => [
				'tab'         => 'what-to-copy',
				'type'        => 'number',
				'label'       => \__( 'Increase menu order by', 'duplicate-post' ),
				'value'       => \get_option( 'duplicate_post_increase_menu_order_by' ),
				'description' => [ \__( 'Add this number to the original menu order (blank or zero to retain the value)', 'duplicate-post' ) ],
			],
			'duplicate_post_blacklist'                    => [
				'tab'         => 'what-to-copy',
				'type'        => 'text',
				'label'       => \__( 'Do not copy these fields', 'duplicate-post' ),
				'value'       => \get_option( 'duplicate_post_blacklist' ),
				'description' => [
					\__( 'Comma-separated list of meta fields that must not be copied.', 'duplicate-post' ),
					\__( 'You can use * to match zero or more alphanumeric characters or underscores: e.g. field*', 'duplicate-post' ),
				],
			],
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
			'duplicate_post_show_original_meta_box'       => [
				'tab'         => 'display',
				'fieldset'    => 'show-original',
				'type'        => 'checkbox',
				'label'       => \__( 'In a metabox in the Edit screen', 'duplicate-post' ),
				'value'       => 1,
				'description' => [
					\__( "You'll also be able to delete the reference to the original item with a checkbox", 'duplicate-post' ),
				],
			],
			'duplicate_post_show_original_column'         => [
				'tab'         => 'display',
				'fieldset'    => 'show-original',
				'type'        => 'checkbox',
				'label'       => \__( 'In a column in the Post list', 'duplicate-post' ),
				'value'       => 1,
				'description' => [
					\__( "You'll also be able to delete the reference to the original item with a checkbox in Quick Edit", 'duplicate-post' ),
				],
			],
			'duplicate_post_show_original_in_post_states' => [
				'tab'      => 'display',
				'fieldset' => 'show-original',
				'type'     => 'checkbox',
				'label'    => \__( 'After the title in the Post list', 'duplicate-post' ),
				'value'    => 1,
			],
			'duplicate_post_show_notice'                  => [
				'tab'   => 'display',
				'type'  => 'checkbox',
				'label' => \__( 'Show update notice', 'duplicate-post' ),
				'value' => 1,
			],
			'duplicate_post_show_link'                    => [
				'tab'         => 'display',
				'fieldset'    => 'show-links',
				'sub_options' => [
					'new_draft'         => [
						'type'  => 'checkbox',
						'label' => \__( 'New Draft', 'duplicate-post' ),
						'value' => 1,
					],
					'clone'             => [
						'type'  => 'checkbox',
						'label' => \__( 'Clone', 'duplicate-post' ),
						'value' => 1,
					],
					'rewrite_republish' => [
						'type'  => 'checkbox',
						'label' => \__( 'Rewrite & Republish', 'duplicate-post' ),
						'value' => 1,
					],
				],
			],
			'duplicate_post_show_link_in' => [
				'tab'         => 'display',
				'fieldset'    => 'show-links-in',
				'sub_options' => [
					'row'         => [
						'type'     => 'checkbox',
						'label'    => \__( 'Post list', 'duplicate-post' ),
						'value'    => 1,
					],
					'adminbar' => [
						'type'        => 'checkbox',
						'label'       => \__( 'Admin bar', 'duplicate-post' ),
						'value'       => 1,
					],
					'submitbox' => [
						'type'  => 'checkbox',
						'label' => \__( 'Edit screen', 'duplicate-post' ),
						'value' => 1,
					],
					'bulkactions' => [
						'type'  => 'checkbox',
						'label' => \__( 'Bulk Actions', 'duplicate-post' ),
						'value' => 1,
					],
				],
			],
		];
	}
}
