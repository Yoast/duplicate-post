<?php
/**
 * Duplicate Post user interface.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\UI;

use Yoast\WP\Duplicate_Post\Permissions_Helper;

/**
 * Represents the Duplicate Post User Interface class.
 */
class User_Interface {

	/**
	 * Holds the post.
	 *
	 * @var \WP_Post
	 */
	private $post = null;

	/**
	 * Holds the global `$pagenow` variable's value.
	 *
	 * @var string
	 */
	private $pagenow;

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * Holds the object to manage the row actions for the post.
	 *
	 * @var Row_Action
	 */
	protected $row_action;

	/**
	 * Holds the object to manage the post submitbox links.
	 *
	 * @var Post_Submitbox
	 */
	protected $post_submitbox;

	/**
	 * Holds the object to manage the block editor links.
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
	 * Admin notices object.
	 *
	 * @var Admin_Notices
	 */
	protected $admin_notices;

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
	 * Initializes the class.
	 *
	 * @param Permissions_Helper $permissions_helper The permissions helper object.
	 */
	public function __construct( Permissions_Helper $permissions_helper ) {
		global $pagenow;
		$this->pagenow = $pagenow;

		$this->permissions_helper = $permissions_helper;
		$this->link_builder       = new Link_Builder();
		$this->row_action         = new Row_Action( $this->link_builder, $this->permissions_helper );
		$this->post_submitbox     = new Post_Submitbox( $this->link_builder, $this->permissions_helper );
		$this->block_editor       = new Block_Editor( $this->link_builder, $this->permissions_helper );
		$this->admin_bar          = new Admin_Bar( $this->link_builder, $this->permissions_helper );
		$this->bulk_actions       = new Bulk_Actions( $this->permissions_helper );
		$this->admin_notices      = new Admin_Notices();
		$this->post_states        = new Post_States();
		$this->metabox            = new Metabox( $this->permissions_helper );
		$this->column             = new Column( $this->permissions_helper );

		$this->register_hooks();
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_action( 'admin_enqueue_scripts', [ $this, 'should_previously_used_keyword_assessment_run' ], 9 );
		\add_action( 'init', [ $this, 'register_styles' ] );
	}

	/**
	 * Disables the Yoast SEO PreviouslyUsedKeyword assessment for posts duplicated for Rewrite & Republish.
	 *
	 * @return void
	 */
	public function should_previously_used_keyword_assessment_run() {
		if ( ! \in_array( $this->pagenow, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		if ( ! $this->post ) {
			$this->post = \get_post();
		}

		$skip_assessment = \get_post_meta( $this->post->ID, '_dp_is_rewrite_republish_copy', true );

		if ( ! empty( $skip_assessment ) ) {
			\add_filter( 'wpseo_previously_used_keyword_active', '__return_false' );
		}
	}

	/**
	 * Registers the styles.
	 */
	public function register_styles() {
		\wp_register_style( 'duplicate-post', \plugins_url( '/duplicate-post.css', DUPLICATE_POST_FILE ), [], DUPLICATE_POST_CURRENT_VERSION );
	}
}
