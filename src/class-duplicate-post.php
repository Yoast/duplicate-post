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
	 * Initializes the main class.
	 */
	public function __construct() {

		// Handle the user interface.
		new Duplicate_Post_User_Interface();

		// Republish a post marked for Rewrite & Republish.
		new Duplicate_Post_Republish();
	}
}
