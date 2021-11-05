<?php

namespace Yoast\WP\Duplicate_Post\UI;

use Yoast\WP\Duplicate_Post\Permissions_Helper;

/**
 * Duplicate Post user interface.
 */
class User_Interface {

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * Holds the object to manage the row actions for the post.
	 *
	 * @var Row_Actions
	 */
	protected $row_actions;

	/**
	 * Holds the object to manage the classic editor UI.
	 *
	 * @var Classic_Editor
	 */
	protected $classic_editor;

	/**
	 * Holds the object to manage the block editor UI.
	 *
	 * @var Block_Editor
	 */
	protected $block_editor;

	/**
	 * Holds the object to manage the admin bar links.
	 *
	 * @var Admin_Bar
	 */
	protected $admin_bar;

	/**
	 * Holds the object to manage the bulk actions dropdown.
	 *
	 * @var Bulk_Actions
	 */
	protected $bulk_actions;

	/**
	 * Post states object.
	 *
	 * @var Post_States
	 */
	protected $post_states;

	/**
	 * Metabox object.
	 *
	 * @var Metabox
	 */
	protected $metabox;

	/**
	 * Newsletter object.
	 *
	 * @var Newsletter
	 */
	protected $newsletter;

	/**
	 * Column object.
	 *
	 * @var Column
	 */
	protected $column;

	/**
	 * Holds the object to create the action link to duplicate.
	 *
	 * @var Link_Builder
	 */
	protected $link_builder;

	/**
	 * Holds the object to create the action link to duplicate.
	 *
	 * @var Asset_Manager
	 */
	protected $asset_manager;

	/**
	 * Initializes the class.
	 *
	 * @param Permissions_Helper $permissions_helper The permissions helper object.
	 */
	public function __construct( Permissions_Helper $permissions_helper ) {
		$this->permissions_helper = $permissions_helper;
		$this->link_builder       = new Link_Builder();
		$this->asset_manager      = new Asset_Manager();
		$this->asset_manager->register_hooks();

		$this->admin_bar      = new Admin_Bar( $this->link_builder, $this->permissions_helper, $this->asset_manager );
		$this->block_editor   = new Block_Editor( $this->link_builder, $this->permissions_helper, $this->asset_manager );
		$this->bulk_actions   = new Bulk_Actions( $this->permissions_helper );
		$this->column         = new Column( $this->permissions_helper, $this->asset_manager );
		$this->metabox        = new Metabox( $this->permissions_helper );
		$this->newsletter     = new Newsletter();
		$this->post_states    = new Post_States( $this->permissions_helper );
		$this->classic_editor = new Classic_Editor( $this->link_builder, $this->permissions_helper, $this->asset_manager );
		$this->row_actions    = new Row_Actions( $this->link_builder, $this->permissions_helper );

		$this->admin_bar->register_hooks();
		$this->block_editor->register_hooks();
		$this->bulk_actions->register_hooks();
		$this->column->register_hooks();
		$this->metabox->register_hooks();
		$this->post_states->register_hooks();
		$this->classic_editor->register_hooks();
		$this->row_actions->register_hooks();
	}
}
