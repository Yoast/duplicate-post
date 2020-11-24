<?php
/**
 * Duplicate Post main class.
 *
 * @package Duplicate_Post
 * @since 4.0
 */

namespace Yoast\WP\Duplicate_Post;

use Yoast\WP\Duplicate_Post\Handlers\Handler;
use Yoast\WP\Duplicate_Post\UI\User_Interface;

/**
 * Represents the Duplicate Post main class.
 */
class Duplicate_Post {

	/**
	 * Permissions_Helper object.
	 *
	 * @var Permissions_Helper
	 */
	private $permissions_helper;

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
	 * Initializes the main class.
	 */
	public function __construct() {
		$this->permissions_helper = new Permissions_Helper();
		$this->user_interface     = new User_Interface( $this->permissions_helper );
		$this->post_duplicator    = new Post_Duplicator();
		$this->handler            = new Handler( $this->post_duplicator, $this->permissions_helper );
	}
}
