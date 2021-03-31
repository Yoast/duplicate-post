<?php

namespace Yoast\WP\Duplicate_Post\Admin;

use WP_Taxonomy;
use Yoast\WP\Duplicate_Post\Utils;

/**
 * Class Options_Form_Generator.
 */
class Options_Form_Generator {

	/**
	 * The Options_Inputs instance.
	 *
	 * @var Options_Inputs
	 */
	protected $options_inputs;

	/**
	 * Options_Form_Generator constructor.
	 *
	 * @param Options_Inputs $inputs The Options_Inputs instance.
	 */
	public function __construct( Options_Inputs $inputs ) {
		$this->options_inputs = $inputs;
	}

	/**
	 * Generates the HTML output of an input control, based on the passed options.
	 *
	 * @param array  $options       The options to base the input on.
	 * @param string $parent_option The parent option, used for grouped inputs. Optional.
	 *
	 * @return string The HTML output.
	 */
	public function generate_options_input( array $options, $parent_option = '' ) {
		$output = '';

		foreach ( $options as $option => $option_values ) {
			// Skip empty options.
			if ( empty( $option_values ) ) {
				continue;
			}

			// Check for support of the current WordPress version.
			if ( \array_key_exists( 'version', $option_values ) && \version_compare( \get_bloginfo( 'version' ), $option_values['version'] ) < 0 ) {
				continue;
			}

			if ( \array_key_exists( 'sub_options', $option_values ) ) {
				$output .= $this->generate_options_input( $option_values['sub_options'], $option );

				continue;
			}

			// If callback, call it.
			if ( \array_key_exists( 'callback', $option_values ) ) {
				$output .= $this->{$option_values['callback']}();

				continue;
			}

			if ( ! \array_key_exists( 'type', $option_values ) ) {
				continue;
			}

			$id = ( \array_key_exists( 'id', $option_values ) ? $option_values['id'] : $this->prepare_input_id( $option ) );

			if ( $parent_option !== '' ) {
				$id     = \sprintf( '%s-%s', $this->prepare_input_id( $parent_option ), $id );
				$option = \sprintf( '%s[%s]', $parent_option, $option );
			}

			switch ( $option_values['type'] ) {
				case 'checkbox':
					$output .= $this->options_inputs->checkbox(
						$option,
						$option_values['value'],
						$id,
						$this->is_checked( $option, $option_values, $parent_option )
					);

					$output .= \sprintf( '<label for="%s">%s</label>', $id, \esc_html( $option_values['label'] ) );
					break;
				case 'text':
					$output .= $this->options_inputs->text( $option, $option_values['value'], $id );
					break;
				case 'number':
					$output .= $this->options_inputs->number( $option, $option_values['value'], $id );

					break;
			}

			if ( \array_key_exists( 'description', $option_values ) ) {
				$output .= ' ' . $this->extract_description( $option_values['description'], $id );
			}

			$output .= '<br />';
		}

		return $output;
	}

	/**
	 * Sorts taxonomy objects based on being public, followed by being private
	 * and when the visibility is equal, on the taxonomy public name (case-sensitive).
	 *
	 * @param WP_Taxonomy $taxonomy1 First taxonomy object.
	 * @param WP_Taxonomy $taxonomy2 Second taxonomy object.
	 *
	 * @return int An integer less than, equal to, or greater than zero indicating respectively
	 *             the first taxonomy should be sorted before, at the same level or after the second taxonomy.
	 */
	public function sort_taxonomy_objects( $taxonomy1, $taxonomy2 ) {
		if ( $taxonomy1->public === true && $taxonomy2->public === false ) {
			return -1;
		}
		elseif ( $taxonomy1->public === false && $taxonomy2->public === true ) {
			return 1;
		}

		// Same visibility, sort by name.
		return \strnatcmp( $taxonomy1->labels->name, $taxonomy2->labels->name );
	}

	/**
	 * Extracts and formats the description associated with the input field.
	 *
	 * @param string|array $description The description string. Can be an array of strings.
	 * @param string       $id          The ID of the input field.
	 *
	 * @return string The description HTML for the input.
	 */
	public function extract_description( $description, $id ) {
		if ( ! \is_array( $description ) ) {
			return \sprintf( '<span id="%s-description">(%s)</span>', $id, \esc_html( $description ) );
		}

		return \sprintf( '<p id="%s-description">%s</p>', $id, \implode( '<br />', \array_map( '\esc_html', $description ) ) );
	}

	/**
	 * Generates a list of checkboxes for registered taxonomies.
	 *
	 * @return string The generated taxonomies list.
	 */
	public function generate_taxonomy_exclusion_list() {
		$taxonomies = \get_taxonomies( [], 'objects' );

		\usort( $taxonomies, [ $this, 'sort_taxonomy_objects' ] );

		$taxonomies_blacklist = \get_option( 'duplicate_post_taxonomies_blacklist' );

		if ( ! \is_array( $taxonomies_blacklist ) ) {
			$taxonomies_blacklist = [];
		}

		$output = '';

		foreach ( $taxonomies as $taxonomy ) {
			if ( $taxonomy->name === 'post_format' ) {
				continue;
			}

			$is_public = ( $taxonomy->public ) ? 'public' : 'private';
			$name      = \esc_attr( $taxonomy->name );

			$output .= \sprintf( '<div class="taxonomy_%s">', $is_public );
			$output .= $this->generate_options_input(
				[
					'duplicate_post_taxonomies_blacklist[]' => [
						'type'    => 'checkbox',
						'id'      => 'duplicate-post-' . $this->prepare_input_id( $name ),
						'value'   => $name,
						'checked' => \in_array( $taxonomy->name, $taxonomies_blacklist, true ),
						'label'   => $taxonomy->labels->name . ' [' . $taxonomy->name . ']',
					],
				]
			);
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Generates a list of checkboxes for registered roles.
	 *
	 * @return string The generated roles list.
	 */
	public function generate_roles_permission_list() {
		$post_types        = \get_post_types( [ 'show_ui' => true ], 'objects' );
		$edit_capabilities = [ 'edit_posts' => true ];

		foreach ( $post_types as $post_type ) {
			$edit_capabilities[ $post_type->cap->edit_posts ] = true;
		}

		$output = '';

		foreach ( Utils::get_roles() as $name => $display_name ) {
			$role = \get_role( $name );

			if ( \count( \array_intersect_key( $role->capabilities, $edit_capabilities ) ) > 0 ) {
				$output .= $this->generate_options_input(
					[
						'duplicate_post_roles[]' => [
							'type'    => 'checkbox',
							'id'      => 'duplicate-post-' . $this->prepare_input_id( $name ),
							'value'   => $name,
							'checked' => $role->has_cap( 'copy_posts' ),
							'label'   => \translate_user_role( $display_name ),
						],
					]
				);
			}
		}

		return $output;
	}

	/**
	 * Generates a list of checkboxes for registered post types.
	 *
	 * @return string The generated post types list.
	 */
	public function generate_post_types_list() {
		$post_types        = \get_post_types( [ 'show_ui' => true ], 'objects' );
		$hidden_post_types = $this->get_hidden_post_types();
		$output            = '';

		foreach ( $post_types as $post_type_object ) {
			if ( \in_array( $post_type_object->name, $hidden_post_types, true ) ) {
				continue;
			}

			$name = \esc_attr( $post_type_object->name );

			$output .= $this->generate_options_input(
				[
					'duplicate_post_types_enabled[]' => [
						'type'    => 'checkbox',
						'id'      => 'duplicate-post-' . $this->prepare_input_id( $name ),
						'value'   => $name,
						'checked' => $this->is_post_type_enabled( $post_type_object->name ),
						'label'   => $post_type_object->labels->name,
					],
				]
			);
		}

		return $output;
	}

	/**
	 * Determines whether the passed option should result in a checked checkbox or not.
	 *
	 * @param string $option        The option to search for.
	 * @param array  $option_values The option's values.
	 * @param string $parent_option The parent option. Optional.
	 *
	 * @return bool Whether or not the checkbox should be checked.
	 */
	public function is_checked( $option, $option_values, $parent_option = '' ) {
		if ( \array_key_exists( 'checked', $option_values ) ) {
			return $option_values['checked'];
		}

		// Check for serialized options.
		$saved_option = ! empty( $parent_option ) ? \get_option( $parent_option ) : \get_option( $option );

		if ( ! \is_array( $saved_option ) ) {
			return (int) $saved_option === 1;
		}

		// Clean up the sub-option's name.
		$cleaned_option = \trim( \str_replace( $parent_option, '', $option ), '[]' );

		return \array_key_exists( $cleaned_option, $saved_option ) && (int) $saved_option[ $cleaned_option ] === 1;
	}

	/**
	 * Prepares the passed ID so it's properly formatted.
	 *
	 * @param string $id The ID to prepare.
	 *
	 * @return string The prepared input ID.
	 */
	public function prepare_input_id( $id ) {
		return \str_replace( '_', '-', $id );
	}

	/**
	 * Checks whether or not a post type is enabled.
	 *
	 * @codeCoverageIgnore As this is a simple wrapper for a function that is also being used elsewhere, we can skip testing for now.
	 *
	 * @param string $post_type The post type.
	 *
	 * @return bool Whether or not the post type is enabled.
	 */
	public function is_post_type_enabled( $post_type ) {
		$duplicate_post_types_enabled = \get_option( 'duplicate_post_types_enabled', [ 'post', 'page' ] );
		if ( ! \is_array( $duplicate_post_types_enabled ) ) {
			$duplicate_post_types_enabled = [ $duplicate_post_types_enabled ];
		}
		return \in_array( $post_type, $duplicate_post_types_enabled, true );
	}

	/**
	 * Generates a list of post types that should be hidden from the options page.
	 *
	 * @return array The array of names of the post types to hide.
	 */
	public function get_hidden_post_types() {
		$hidden_post_types = [
			'attachment',
			'wp_block',
		];

		if ( Utils::is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$hidden_post_types[] = 'product';
		}

		return $hidden_post_types;
	}
}
