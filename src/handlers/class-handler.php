<?php
/**
 * Duplicate Post handler class for duplication actions.
 *
 * @package Duplicate_Post
 * @since 4.0
 */

namespace Yoast\WP\Duplicate_Post\Handlers;

use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Post_Duplicator;

/**
 * Represents the handler for duplication actions.
 */
class Handler {

	/**
	 * Post_Duplicator object.
	 *
	 * @var Post_Duplicator
	 */
	protected $post_duplicator;

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * The bulk actions handler.
	 *
	 * @var Bulk_Handler
	 */
	protected $bulk_handler;

	/**
	 * The link actions handler.
	 *
	 * @var Link_Handler
	 */
	protected $link_handler;

	/**
	 * The save_post action handler.
	 *
	 * @var Save_Post_Handler
	 */
	protected $save_post_handler;

	/**
	 * Initializes the class.
	 *
	 * @param Post_Duplicator    $post_duplicator    The Post_Duplicator object.
	 * @param Permissions_Helper $permissions_helper The Permissions Helper object.
	 */
	public function __construct( Post_Duplicator $post_duplicator, Permissions_Helper $permissions_helper ) {
		$this->post_duplicator    = $post_duplicator;
		$this->permissions_helper = $permissions_helper;

		$this->bulk_handler      = new Bulk_Handler( $this->post_duplicator, $this->permissions_helper );
		$this->link_handler      = new Link_Handler( $this->post_duplicator, $this->permissions_helper );
		$this->save_post_handler = new Save_Post_Handler( $this->permissions_helper );
	}
}
