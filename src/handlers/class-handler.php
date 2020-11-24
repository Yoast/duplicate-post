<?php
/**
 * Duplicate Post handler class for duplication actions.
 *
 * @package Duplicate_Post
 * @since 4.0
 */

namespace Yoast\WP\Duplicate_Post\Handlers;

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
	private $post_duplicator;

	/**
	 * The bulk actions handler.
	 *
	 * @var Bulk_Handler
	 */
	private $bulk_handler;

	/**
	 * The link actions handler.
	 *
	 * @var Link_Handler
	 */
	private $link_handler;

	/**
	 * The save_post action handler.
	 *
	 * @var Save_Post_Handler
	 */
	private $save_post_handler;

	/**
	 * Initializes the class.
	 *
	 * @param Post_Duplicator $post_duplicator The Post_Duplicator object.
	 */
	public function __construct( Post_Duplicator $post_duplicator ) {
		$this->post_duplicator   = $post_duplicator;
		$this->bulk_handler      = new Bulk_Handler( $post_duplicator );
		$this->link_handler      = new Link_Handler( $post_duplicator );
		$this->save_post_handler = new Save_Post_Handler();
	}
}
