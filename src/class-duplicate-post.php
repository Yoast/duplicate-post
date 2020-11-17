<?php
/**
 * Duplicate Post main class.
 *
 * @package Duplicate_Post
 * @since 4.0
 */

namespace Yoast\WP\Duplicate_Post;

/**
 * Represents the Duplicate Post main class.
 */
class Duplicate_Post {

	/**
	 * Post_Duplicator object.
	 *
	 * @var Post_Duplicator
	 */
	private $post_duplicator;

	/**
	 * Handler object.
	 *
	 * @var Handler
	 */
	private $handler;

	/**
	 * User_Interface object.
	 *
	 * @var User_Interface
	 */
	private $user_interface;

	/**
	 * Initializes the main class.
	 */
	public function __construct() {
		$this->post_duplicator = new Post_Duplicator();
		$this->handler         = new Handler( $this->post_duplicator );
		$this->user_interface  = new User_Interface( $this->handler );
	}
}
