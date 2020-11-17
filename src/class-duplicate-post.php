<?php
/**
 * Duplicate Post main class.
 *
 * @package Duplicate_Post
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
	 * Initializes the main class.
	 */
	public function __construct() {
		$this->post_duplicator = new Post_Duplicator();
		$this->handler         = new Handler( $this->post_duplicator );

		// Handle the user interface.
		new User_Interface( $this->handler );
	}
}
