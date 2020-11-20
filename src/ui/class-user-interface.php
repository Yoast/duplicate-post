<?php
/**
 * Duplicate Post user interface.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\UI;

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
	 * Holds the object to create the action link to duplicate.
	 *
	 * @var Link_Builder
	 */
	protected $link_builder;

	/**
	 * Initializes the class.
	 */
	public function __construct() {
		global $pagenow;
		$this->pagenow = $pagenow;

		$this->link_builder   = new Link_Builder();
		$this->row_action     = new Row_Action( $this->link_builder );
		$this->post_submitbox = new Post_Submitbox( $this->link_builder );
		$this->block_editor   = new Block_Editor( $this->link_builder );
		$this->admin_bar      = new Admin_Bar( $this->link_builder );
		$this->bulk_actions   = new Bulk_Actions();
		$this->admin_notices  = new Admin_Notices();

		$this->register_hooks();
	}

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_action( 'admin_enqueue_scripts', [ $this, 'should_previously_used_keyword_assessment_run' ], 9 );
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
}
