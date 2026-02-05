<?php

namespace Yoast\WP\Duplicate_Post\Handlers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Yoast\WP\Duplicate_Post\Permissions_Helper;

/**
 * Duplicate Post handler class for REST API endpoints.
 *
 * @since 4.6
 */
class Rest_API_Handler {

	/**
	 * The REST API namespace.
	 *
	 * @var string
	 */
	public const REST_NAMESPACE = 'duplicate-post/v1';

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
		\add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Registers the REST API routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		\register_rest_route(
			self::REST_NAMESPACE,
			'/original/(?P<post_id>\d+)',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'remove_original' ],
				'permission_callback' => [ $this, 'can_remove_original' ],
				'args'                => [
					'post_id' => [
						'description'       => \__( 'The ID of the post to remove the original reference from.', 'duplicate-post' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => static function ( $param ) {
							return \is_numeric( $param ) && (int) $param > 0;
						},
						'sanitize_callback' => 'absint',
					],
				],
			],
		);
	}

	/**
	 * Checks if the current user can remove the original reference.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return bool|WP_Error True if the user can remove the original, WP_Error otherwise.
	 */
	public function can_remove_original( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'post_id' );
		$post    = \get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'rest_post_not_found',
				\__( 'Post not found.', 'duplicate-post' ),
				[ 'status' => 404 ],
			);
		}

		if ( ! \current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'rest_forbidden',
				\__( 'You do not have permission to edit this post.', 'duplicate-post' ),
				[ 'status' => 403 ],
			);
		}

		if ( $this->permissions_helper->is_rewrite_and_republish_copy( $post ) ) {
			return new WP_Error(
				'rest_forbidden',
				\__( 'Cannot remove original reference from a Rewrite & Republish copy.', 'duplicate-post' ),
				[ 'status' => 403 ],
			);
		}

		return true;
	}

	/**
	 * Removes the original reference from a post.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response|WP_Error The REST response or error.
	 */
	public function remove_original( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'post_id' );

		$deleted = \delete_post_meta( $post_id, '_dp_original' );

		if ( ! $deleted ) {
			return new WP_Error(
				'rest_cannot_delete',
				\__( 'Could not remove the original reference.', 'duplicate-post' ),
				[ 'status' => 500 ],
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => \__( 'Original reference removed successfully.', 'duplicate-post' ),
			],
			200,
		);
	}
}
