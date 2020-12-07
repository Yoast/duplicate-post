<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests\UI;

use Brain\Monkey;
use Mockery;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Tests\TestCase;
use Yoast\WP\Duplicate_Post\UI\Block_Editor;
use Yoast\WP\Duplicate_Post\UI\Link_Builder;

/**
 * Test the Block_Editor class.
 */
class Block_Editor_Test extends TestCase {

	/**
	 * Holds the object to create the action link to duplicate.
	 *
	 * @var Link_Builder
	 */
	protected $link_builder;

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper
	 */
	protected $permissions_helper;

	/**
	 * The instance.
	 *
	 * @var Block_Editor
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->link_builder       = Mockery::mock( Link_Builder::class );
		$this->permissions_helper = Mockery::mock( Permissions_Helper::class );

		$this->instance = new Block_Editor( $this->link_builder, $this->permissions_helper );
	}

	/**
	 * Tests if the needed attributes are set correctly.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::__construct
	 */
	public function test_constructor() {
		$this->assertAttributeInstanceOf( Link_Builder::class, 'link_builder', $this->instance );
		$this->assertAttributeInstanceOf( Permissions_Helper::class, 'permissions_helper', $this->instance );
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::register_hooks
	 */
	public function test_register_hooks() {
		$this->instance->register_hooks();

		$this->assertNotFalse( \has_action( 'admin_enqueue_scripts', [ $this->instance, 'should_previously_used_keyword_assessment_run' ] ), 'Does not have expected admin_enqueue_scripts action' );
		$this->assertNotFalse( \has_action( 'enqueue_block_editor_assets', [ $this->instance, 'enqueue_block_editor_scripts' ] ), 'Does not have expected enqueue_block_editor_assets action' );
	}

	/**
	 * Tests the should_previously_used_keyword_assessment_run function when the assessment should be disabled.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::should_previously_used_keyword_assessment_run
	 */
	public function test_should_previously_used_keyword_assessment_run_yes() {
		global $pagenow;
		$pagenow         = 'post.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Intended, to be able to test the method.
		$post            = Mockery::mock( \WP_Post::class );
		$post->ID        = 123;
		$skip_assessment = '1';

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturn( $skip_assessment );

		$this->instance->should_previously_used_keyword_assessment_run();
		$this->assertNotFalse( \has_filter( 'wpseo_previously_used_keyword_active' ) );
	}

	/**
	 * Tests the should_previously_used_keyword_assessment_run function when the assessment should not be disabled.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::should_previously_used_keyword_assessment_run
	 */
	public function test_should_previously_used_keyword_assessment_run_no() {
		global $pagenow;
		$pagenow         = 'post.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Intended, to be able to test the method.
		$post            = Mockery::mock( \WP_Post::class );
		$post->ID        = 123;
		$skip_assessment = '';

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturn( $skip_assessment );

		$this->instance->should_previously_used_keyword_assessment_run();
		$this->assertFalse( \has_filter( 'wpseo_previously_used_keyword_active' ) );
	}

	/**
	 * Tests the get_new_draft_permalink function when a link is returned.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::get_new_draft_permalink
	 */
	public function test_get_new_draft_permalink_successful() {
		$post = Mockery::mock( \WP_Post::class );
		$url  = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_new_draft&post=201&_wpnonce=94038b7dee';

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper->expects( 'should_link_be_displayed' )
			->with( $post )
			->andReturnTrue();

		$this->link_builder->expects( 'build_new_draft_link' )
			->with( $post )
			->andReturn( $url );

		$this->assertSame( $url, $this->instance->get_new_draft_permalink() );
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) > 0 );
	}

	/**
	 * Tests the get_new_draft_permalink function when a link should not be displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::get_new_draft_permalink
	 */
	public function test_get_new_draft_permalink_unsuccessful() {
		$post = Mockery::mock( \WP_Post::class );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper->expects( 'should_link_be_displayed' )
			->with( $post )
			->andReturnFalse();

		$this->link_builder->expects( 'build_new_draft_link' )
			->with( $post )
			->never();

		$this->assertSame( '', $this->instance->get_new_draft_permalink() );
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) > 0 );
	}

	/**
	 * Tests the get_rewrite_republish_permalink function when a link is returned.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::get_rewrite_republish_permalink
	 */
	public function test_get_rewrite_republish_permalink_successful() {
		$post              = Mockery::mock( \WP_Post::class );
		$post->post_status = 'publish';
		$url               = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_rewrite&post=201&_wpnonce=5e7abf68c9';

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper->expects( 'should_link_be_displayed' )
			->with( $post )
			->andReturnTrue();

		$this->link_builder->expects( 'build_rewrite_and_republish_link' )
			->with( $post )
			->andReturn( $url );

		$this->assertSame( $url, $this->instance->get_rewrite_republish_permalink() );
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) > 0 );
	}

	/**
	 * Tests the get_rewrite_republish_permalink function when the post is not published.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::get_rewrite_republish_permalink
	 */
	public function test_get_rewrite_republish_permalink_unsuccessful_not_published() {
		$post              = Mockery::mock( \WP_Post::class );
		$post->post_status = 'draft';

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper->expects( 'should_link_be_displayed' )
			->with( $post )
			->never();

		$this->link_builder->expects( 'build_rewrite_and_republish_link' )
			->with( $post )
			->never();

		$this->assertSame( '', $this->instance->get_rewrite_republish_permalink() );
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) === 0 );
	}

	/**
	 * Tests the get_rewrite_republish_permalink function when the link should not be displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::get_rewrite_republish_permalink
	 */
	public function test_get_rewrite_republish_permalink_unsuccessful_link_should_not_be_displayed() {
		$post              = Mockery::mock( \WP_Post::class );
		$post->post_status = 'publish';

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper->expects( 'should_link_be_displayed' )
			->with( $post )
			->andReturnFalse();

		$this->link_builder->expects( 'build_rewrite_and_republish_link' )
			->with( $post )
			->never();

		$this->assertSame( '', $this->instance->get_rewrite_republish_permalink() );
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) > 0 );
	}
}
