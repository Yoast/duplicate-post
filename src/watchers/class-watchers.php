<?php
/**
 * Duplicate Post user interface.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\Watchers;

use Yoast\WP\Duplicate_Post\Permissions_Helper;

/**
 * Represents the Duplicate Post User Interface class.
 */
class Watchers {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * Holds the original post watcher.
	 *
	 * @var Original_Post_Watcher
	 */
	protected $original_post_watcher;

	/**
	 * Holds the copied post watcher.
	 *
	 * @var Copied_Post_Watcher
	 */
	protected $copied_post_watcher;

	/**
	 * Holds the bulk actions watcher.
	 *
	 * @var Bulk_Actions_Watcher
	 */
	protected $bulk_actions_watcher;

	/**
	 * Holds the link actions watcher.
	 *
	 * @var Link_Actions_Watcher
	 */
	protected $link_actions_watcher;

	/**
	 * Holds the republished post watcher.
	 *
	 * @var Republished_Post_Watcher
	 */
	protected $republished_post_watcher;

	/**
	 * Initializes the class.
	 *
	 * @param Permissions_Helper $permissions_helper The permissions helper object.
	 */
	public function __construct( Permissions_Helper $permissions_helper ) {
		$this->permissions_helper       = $permissions_helper;
		$this->copied_post_watcher      = new Copied_Post_Watcher( $this->permissions_helper );
		$this->original_post_watcher    = new Original_Post_Watcher( $this->permissions_helper );
		$this->bulk_actions_watcher     = new Bulk_Actions_Watcher();
		$this->link_actions_watcher     = new Link_Actions_Watcher( $this->permissions_helper );
		$this->republished_post_watcher = new Republished_Post_Watcher( $this->permissions_helper );
	}
}
