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
	 * User_Interface object.
	 *
	 * @var User_Interface
	 */
	private $user_interface;

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
	 * @var Post_Republisher
	 */
	private $post_republisher;

	/**
	 * Initializes the main class.
	 */
	public function __construct() {
		$this->user_interface   = new User_Interface();
		$this->post_duplicator  = new Post_Duplicator();
		$this->handler          = new Handler( $this->post_duplicator );
		$this->post_republisher = new Post_Republisher( $this->post_duplicator );
	}
}
