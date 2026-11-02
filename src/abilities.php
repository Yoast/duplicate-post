<?php

namespace Yoast\WP\Duplicate_Post;

use WP_Error;
use WP_Post;

/**
 * Duplicate Post class to register the plugin's abilities.
 *
 * Abilities expose the plugin's copy actions to the Abilities API, so that clients
 * such as the REST API, MCP servers and AI agents can run them outside the admin.
 *
 * @since 4.8
 */
class Abilities {

	/**
	 * The namespace of the abilities, which is also the slug of their category.
	 *
	 * @var string
	 */
	public const ABILITY_NAMESPACE = 'duplicate-post';

	/**
	 * Post_Duplicator object.
	 *
	 * @var Post_Duplicator
	 */
	protected $post_duplicator;

	/**
	 * Post_Republisher object.
	 *
	 * @var Post_Republisher
	 */
	protected $post_republisher;

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * Initializes the class.
	 *
	 * @param Post_Duplicator    $post_duplicator    The Post_Duplicator object.
	 * @param Post_Republisher   $post_republisher   The Post_Republisher object.
	 * @param Permissions_Helper $permissions_helper The Permissions Helper object.
	 */
	public function __construct(
		Post_Duplicator $post_duplicator,
		Post_Republisher $post_republisher,
		Permissions_Helper $permissions_helper
	) {
		$this->post_duplicator    = $post_duplicator;
		$this->post_republisher   = $post_republisher;
		$this->permissions_helper = $permissions_helper;
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * The hooks only exist when the Abilities API is available, so no version check is needed.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_action( 'wp_abilities_api_categories_init', [ $this, 'register_category' ] );
		\add_action( 'wp_abilities_api_init', [ $this, 'register_abilities' ] );
	}

	/**
	 * Registers the ability category for the plugin.
	 *
	 * @return void
	 */
	public function register_category() {
		\wp_register_ability_category(
			self::ABILITY_NAMESPACE,
			[
				'label'       => \__( 'Duplicate Post', 'duplicate-post' ),
				'description' => \__( 'Abilities to copy posts and to rewrite and republish published posts.', 'duplicate-post' ),
			],
		);
	}

	/**
	 * Registers the abilities for the plugin.
	 *
	 * @return void
	 */
	public function register_abilities() {
		\wp_register_ability(
			self::ABILITY_NAMESPACE . '/clone',
			[
				'label'               => \__( 'Clone', 'duplicate-post' ),
				'description'         => \__( 'Creates a copy of a post, page or custom post type item, using the copy settings of Duplicate Post. The copy is not opened for editing.', 'duplicate-post' ),
				'category'            => self::ABILITY_NAMESPACE,
				'input_schema'        => $this->get_post_id_input_schema(
					\__( 'The ID of the post to copy.', 'duplicate-post' ),
				),
				'output_schema'       => $this->get_post_output_schema(
					\__( 'The ID of the created copy.', 'duplicate-post' ),
				),
				'execute_callback'    => [ $this, 'clone_post' ],
				'permission_callback' => [ $this, 'can_clone' ],
				'meta'                => [
					'annotations'  => [
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					],
					'public'       => true,
					'show_in_rest' => true,
				],
			],
		);

		\wp_register_ability(
			self::ABILITY_NAMESPACE . '/copy-to-new-draft',
			[
				'label'               => \__( 'Copy to a new draft', 'duplicate-post' ),
				'description'         => \__( 'Creates a draft copy of a post, page or custom post type item, using the copy settings of Duplicate Post. Use this when the copy is meant to be edited, and use Clone when it is not.', 'duplicate-post' ),
				'category'            => self::ABILITY_NAMESPACE,
				'input_schema'        => $this->get_post_id_input_schema(
					\__( 'The ID of the post to copy.', 'duplicate-post' ),
				),
				'output_schema'       => $this->get_post_output_schema(
					\__( 'The ID of the created draft copy.', 'duplicate-post' ),
				),
				'execute_callback'    => [ $this, 'copy_to_new_draft' ],
				'permission_callback' => [ $this, 'can_copy_to_new_draft' ],
				'meta'                => [
					'annotations'  => [
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					],
					'public'       => true,
					'show_in_rest' => true,
				],
			],
		);

		\wp_register_ability(
			self::ABILITY_NAMESPACE . '/rewrite',
			[
				'label'               => \__( 'Rewrite', 'duplicate-post' ),
				'description'         => \__( 'Creates a copy of a published post to rewrite it without touching the published version. The rewritten copy replaces the original when it is republished. Only one copy per post can exist at a time.', 'duplicate-post' ),
				'category'            => self::ABILITY_NAMESPACE,
				'input_schema'        => $this->get_post_id_input_schema(
					\__( 'The ID of the published post to rewrite.', 'duplicate-post' ),
				),
				'output_schema'       => $this->get_post_output_schema(
					\__( 'The ID of the created Rewrite & Republish copy.', 'duplicate-post' ),
				),
				'execute_callback'    => [ $this, 'rewrite' ],
				'permission_callback' => [ $this, 'can_rewrite' ],
				'meta'                => [
					'annotations'  => [
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					],
					'public'       => true,
					'show_in_rest' => true,
				],
			],
		);

		\wp_register_ability(
			self::ABILITY_NAMESPACE . '/republish',
			[
				'label'               => \__( 'Republish', 'duplicate-post' ),
				'description'         => \__( 'Replaces the original post with its Rewrite & Republish copy and deletes the copy. The ID of either the copy or the original post can be passed.', 'duplicate-post' ),
				'category'            => self::ABILITY_NAMESPACE,
				'input_schema'        => $this->get_post_id_input_schema(
					\__( 'The ID of the Rewrite & Republish copy to republish, or of the post it was made from.', 'duplicate-post' ),
				),
				'output_schema'       => $this->get_post_output_schema(
					\__( 'The ID of the republished post.', 'duplicate-post' ),
				),
				'execute_callback'    => [ $this, 'republish' ],
				'permission_callback' => [ $this, 'can_republish' ],
				'meta'                => [
					'annotations'  => [
						'readonly'    => false,
						'destructive' => true,
						'idempotent'  => false,
					],
					'public'       => true,
					'show_in_rest' => true,
				],
			],
		);
	}

	/**
	 * Determines whether the current user can clone the passed post.
	 *
	 * @param array<string, int> $input The ability input.
	 *
	 * @return bool|WP_Error True if the post can be cloned, a WP_Error object otherwise.
	 */
	public function can_clone( $input ) {
		return $this->can_copy( $input, '' );
	}

	/**
	 * Determines whether the current user can copy the passed post to a new draft.
	 *
	 * @param array<string, int> $input The ability input.
	 *
	 * @return bool|WP_Error True if the post can be copied, a WP_Error object otherwise.
	 */
	public function can_copy_to_new_draft( $input ) {
		return $this->can_copy( $input, 'draft' );
	}

	/**
	 * Determines whether the current user can create a Rewrite & Republish copy of the passed post.
	 *
	 * @param array<string, int> $input The ability input.
	 *
	 * @return bool|WP_Error True if the copy can be created, a WP_Error object otherwise.
	 */
	public function can_rewrite( $input ) {
		$post = $this->get_post( $input );

		if ( \is_wp_error( $post ) ) {
			return $post;
		}

		$allowed = $this->can_copy_post_type( $post );

		if ( \is_wp_error( $allowed ) ) {
			return $allowed;
		}

		if ( ! $this->permissions_helper->should_rewrite_and_republish_be_allowed( $post ) ) {
			return new WP_Error(
				'duplicate_post_rewrite_not_allowed',
				\__( 'You cannot create a copy for Rewrite & Republish if the original is not published or if it already has a copy.', 'duplicate-post' ),
				[ 'status' => 403 ],
			);
		}

		return true;
	}

	/**
	 * Determines whether the current user can republish the Rewrite & Republish copy of the passed post.
	 *
	 * @param array<string, int> $input The ability input.
	 *
	 * @return bool|WP_Error True if the copy can be republished, a WP_Error object otherwise.
	 */
	public function can_republish( $input ) {
		$copy = $this->get_rewrite_and_republish_copy( $input );

		if ( \is_wp_error( $copy ) ) {
			return $copy;
		}

		$original = Utils::get_original( $copy->ID );

		if ( ! $original instanceof WP_Post ) {
			return new WP_Error(
				'duplicate_post_original_not_found',
				\__( 'The post this copy was made from no longer exists.', 'duplicate-post' ),
				[ 'status' => 404 ],
			);
		}

		if ( ! \current_user_can( 'edit_post', $original->ID ) ) {
			return new WP_Error(
				'duplicate_post_cannot_republish',
				\__( 'You are not allowed to republish this post.', 'duplicate-post' ),
				[ 'status' => 403 ],
			);
		}

		return true;
	}

	/**
	 * Creates a copy of the passed post, keeping the status from the copy settings.
	 *
	 * @param array<string, int> $input The ability input.
	 *
	 * @return array<string, int|string>|WP_Error The copy data, or a WP_Error object on failure.
	 */
	public function clone_post( $input ) {
		return $this->create_copy( $input, false );
	}

	/**
	 * Creates a draft copy of the passed post.
	 *
	 * @param array<string, int> $input The ability input.
	 *
	 * @return array<string, int|string>|WP_Error The copy data, or a WP_Error object on failure.
	 */
	public function copy_to_new_draft( $input ) {
		return $this->create_copy( $input, true );
	}

	/**
	 * Creates a Rewrite & Republish copy of the passed post.
	 *
	 * @param array<string, int> $input The ability input.
	 *
	 * @return array<string, int|string>|WP_Error The copy data, or a WP_Error object on failure.
	 */
	public function rewrite( $input ) {
		$post = $this->get_post( $input );

		if ( \is_wp_error( $post ) ) {
			return $post;
		}

		$new_post_id = $this->post_duplicator->create_duplicate_for_rewrite_and_republish( $post );

		if ( \is_wp_error( $new_post_id ) ) {
			return $this->copy_creation_failed( $new_post_id );
		}

		return $this->prepare_post_response( $new_post_id );
	}

	/**
	 * Republishes the Rewrite & Republish copy of the passed post onto the original.
	 *
	 * @param array<string, int> $input The ability input.
	 *
	 * @return array<string, int|string>|WP_Error The republished post data, or a WP_Error object on failure.
	 */
	public function republish( $input ) {
		$copy = $this->get_rewrite_and_republish_copy( $input );

		if ( \is_wp_error( $copy ) ) {
			return $copy;
		}

		$original = Utils::get_original( $copy->ID );

		if ( ! $original instanceof WP_Post ) {
			return new WP_Error(
				'duplicate_post_original_not_found',
				\__( 'The post this copy was made from no longer exists.', 'duplicate-post' ),
				[ 'status' => 404 ],
			);
		}

		$this->post_republisher->republish( $copy, $original );
		$this->post_republisher->delete_copy( $copy->ID, $original->ID );

		return $this->prepare_post_response( $original->ID );
	}

	/**
	 * Determines whether the current user can copy the passed post.
	 *
	 * @param array<string, int> $input  The ability input.
	 * @param string             $status The intended destination status.
	 *
	 * @return bool|WP_Error True if the post can be copied, a WP_Error object otherwise.
	 */
	protected function can_copy( $input, $status ) {
		$post = $this->get_post( $input );

		if ( \is_wp_error( $post ) ) {
			return $post;
		}

		$allowed = $this->can_copy_post_type( $post );

		if ( \is_wp_error( $allowed ) ) {
			return $allowed;
		}

		if ( $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			return new WP_Error(
				'duplicate_post_cannot_copy',
				\__( 'You cannot create a copy of a post which is intended for Rewrite & Republish.', 'duplicate-post' ),
				[ 'status' => 403 ],
			);
		}

		/** This filter is documented in admin-functions.php. */
		if ( ! \apply_filters( 'duplicate_post_allow', true, $post, $status, '' ) ) {
			return new WP_Error(
				'duplicate_post_cannot_copy',
				\__( 'You aren\'t allowed to duplicate this post', 'duplicate-post' ),
				[ 'status' => 403 ],
			);
		}

		return true;
	}

	/**
	 * Determines whether the current user can copy posts of the passed post's type.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return bool|WP_Error True if posts of this type can be copied, a WP_Error object otherwise.
	 */
	protected function can_copy_post_type( WP_Post $post ) {
		if ( ! $this->permissions_helper->is_current_user_allowed_to_copy() ) {
			return new WP_Error(
				'duplicate_post_cannot_copy',
				\__( 'Current user is not allowed to copy posts.', 'duplicate-post' ),
				[ 'status' => 403 ],
			);
		}

		if ( ! $this->permissions_helper->is_post_type_enabled( $post->post_type ) ) {
			return new WP_Error(
				'duplicate_post_post_type_not_enabled',
				\__( 'Copy features for this post type are not enabled in options page', 'duplicate-post' ),
				[ 'status' => 403 ],
			);
		}

		return true;
	}

	/**
	 * Creates a copy of the post passed in the ability input.
	 *
	 * @param array<string, int> $input       The ability input.
	 * @param bool               $force_draft Whether the copy should be a draft, regardless of the copy settings.
	 *
	 * @return array<string, int|string>|WP_Error The copy data, or a WP_Error object on failure.
	 */
	protected function create_copy( $input, $force_draft ) {
		$post = $this->get_post( $input );

		if ( \is_wp_error( $post ) ) {
			return $post;
		}

		$options = $this->post_duplicator->get_configured_options();

		if ( $force_draft ) {
			$options['copy_status'] = false;
		}

		$new_post_id = $this->post_duplicator->create_duplicate( $post, $options );

		if ( \is_wp_error( $new_post_id ) ) {
			return $this->copy_creation_failed( $new_post_id );
		}

		$this->post_duplicator->copy_post_taxonomies( $new_post_id, $post, $options );
		$this->post_duplicator->copy_post_meta_info( $new_post_id, $post, $options );

		return $this->prepare_post_response( $new_post_id );
	}

	/**
	 * Returns the post passed in the ability input.
	 *
	 * @param array<string, int> $input The ability input.
	 *
	 * @return WP_Post|WP_Error The post object, or a WP_Error object if it doesn't exist.
	 */
	protected function get_post( $input ) {
		$post = \get_post( $input['post_id'] );

		if ( ! $post instanceof WP_Post ) {
			return new WP_Error(
				'duplicate_post_post_not_found',
				\__( 'The post could not be found.', 'duplicate-post' ),
				[ 'status' => 404 ],
			);
		}

		return $post;
	}

	/**
	 * Returns the Rewrite & Republish copy for the post passed in the ability input.
	 *
	 * The input can hold either the ID of the copy itself or the ID of the post it was made from.
	 *
	 * @param array<string, int> $input The ability input.
	 *
	 * @return WP_Post|WP_Error The copy's post object, or a WP_Error object if there is none.
	 */
	protected function get_rewrite_and_republish_copy( $input ) {
		$post = $this->get_post( $input );

		if ( \is_wp_error( $post ) ) {
			return $post;
		}

		if ( $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			return $post;
		}

		$copy = $this->permissions_helper->get_rewrite_and_republish_copy( $post );

		if ( ! $copy instanceof WP_Post ) {
			return new WP_Error(
				'duplicate_post_copy_not_found',
				\__( 'This post does not have a copy for Rewrite & Republish.', 'duplicate-post' ),
				[ 'status' => 404 ],
			);
		}

		return $copy;
	}

	/**
	 * Returns the ability output for the passed post ID.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array<string, int|string>|WP_Error The post data, or a WP_Error object if the post doesn't exist.
	 */
	protected function prepare_post_response( $post_id ) {
		$post = \get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return new WP_Error(
				'duplicate_post_post_not_found',
				\__( 'The post could not be found.', 'duplicate-post' ),
				[ 'status' => 404 ],
			);
		}

		return [
			'post_id'   => (int) $post->ID,
			'title'     => $post->post_title,
			'status'    => $post->post_status,
			'edit_link' => (string) \get_edit_post_link( $post->ID, 'raw' ),
		];
	}

	/**
	 * Wraps the error returned when a copy could not be created.
	 *
	 * @param WP_Error $error The error returned by the duplicator.
	 *
	 * @return WP_Error The error to return from the ability.
	 */
	protected function copy_creation_failed( WP_Error $error ) {
		return new WP_Error(
			'duplicate_post_copy_creation_failed',
			\__( 'Copy creation failed, could not create a copy.', 'duplicate-post' ),
			[
				'status' => 500,
				'error'  => $error->get_error_message(),
			],
		);
	}

	/**
	 * Returns the input schema for an ability that takes a single post ID.
	 *
	 * @param string $description The description of the post ID.
	 *
	 * @return array<string, array|bool|string> The input schema.
	 */
	protected function get_post_id_input_schema( $description ) {
		return [
			'type'                 => 'object',
			'properties'           => [
				'post_id' => [
					'type'        => 'integer',
					'description' => $description,
					'minimum'     => 1,
				],
			],
			'required'             => [ 'post_id' ],
			'additionalProperties' => false,
		];
	}

	/**
	 * Returns the output schema for an ability that returns a post.
	 *
	 * @param string $description The description of the post ID.
	 *
	 * @return array<string, array|string> The output schema.
	 */
	protected function get_post_output_schema( $description ) {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id'   => [
					'type'        => 'integer',
					'description' => $description,
				],
				'title'     => [
					'type'        => 'string',
					'description' => \__( 'The title of the post.', 'duplicate-post' ),
				],
				'status'    => [
					'type'        => 'string',
					'description' => \__( 'The status of the post.', 'duplicate-post' ),
				],
				'edit_link' => [
					'type'        => 'string',
					'description' => \__( 'The URL to edit the post, empty if the current user cannot edit it.', 'duplicate-post' ),
				],
			],
		];
	}
}
