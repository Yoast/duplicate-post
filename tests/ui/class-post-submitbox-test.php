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
use Yoast\WP\Duplicate_Post\UI\Post_Submitbox;
use Yoast\WP\Duplicate_Post\UI\Link_Builder;

/**
 * Test the Post_Submitbox class.
 */
class Post_Submitbox_Test extends TestCase {

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
	 * @var Post_Submitbox
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->link_builder       = Mockery::mock( Link_Builder::class );
		$this->permissions_helper = Mockery::mock( Permissions_Helper::class );

		$this->instance = new Post_Submitbox( $this->link_builder, $this->permissions_helper );
	}

	/**
	 * Tests if the needed attributes are set correctly.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_Submitbox::__construct
	 */
	public function test_constructor() {
		$this->assertAttributeInstanceOf( Link_Builder::class, 'link_builder', $this->instance );
		$this->assertAttributeInstanceOf( Permissions_Helper::class, 'permissions_helper', $this->instance );
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_Submitbox::register_hooks
	 */
	public function test_register_hooks() {
		$this->instance->register_hooks();

		$this->assertNotFalse( \has_action( 'post_submitbox_start', [ $this->instance, 'add_new_draft_post_button' ] ), 'Does not have expected post_submitbox_start action' );
		$this->assertNotFalse( \has_action( 'post_submitbox_start', [ $this->instance, 'add_rewrite_and_republish_post_button' ] ), 'Does not have expected post_submitbox_start action' );
	}

	/**
	 * Tests the add_new_draft_post_button function when a button is displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_Submitbox::add_new_draft_post_button
	 */
	public function test_add_new_draft_post_button_successful() {
		$post            = Mockery::mock( \WP_Post::class );
		$post->post_type = 'post';
		$url             = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_new_draft&post=201&_wpnonce=94038b7dee';

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_submitbox' )
			->andReturn( '1' );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post )
			->never();

		$this->permissions_helper
			->expects( 'should_link_be_displayed' )
			->with( $post )
			->andReturnTrue();

		$this->link_builder
			->expects( 'build_new_draft_link' )
			->with( $post )
			->andReturn( $url );

		$this->setOutputCallback( function() {} );
		$this->instance->add_new_draft_post_button( $post );
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) > 0 );
	}

	/**
	 * Tests the add_new_draft_post_button function when a button is displayed and the post ID comes from $_GET.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_Submitbox::add_new_draft_post_button
	 */
	public function test_add_new_draft_post_button_successful_post_from_GET() {
		$_GET['post']    = '123';
		$post            = Mockery::mock( \WP_Post::class );
		$post->post_type = 'post';
		$url             = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_new_draft&post=123&_wpnonce=94038b7dee';

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_submitbox' )
			->andReturn( '1' );

		Monkey\Functions\expect( '\get_post' )
			->with( 123 )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'should_link_be_displayed' )
			->with( $post )
			->andReturnTrue();

		$this->link_builder
			->expects( 'build_new_draft_link' )
			->with( $post )
			->andReturn( $url );

		$this->setOutputCallback( function() {} );
		$this->instance->add_new_draft_post_button();
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) > 0 );
	}

	/**
	 * Tests the add_new_draft_post_button function when no post could be retrieved
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_Submitbox::add_new_draft_post_button
	 */
	public function test_add_new_draft_post_button_unsuccessful_no_post() {
		unset( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Intended, to be able to test the method.

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_submitbox' )
			->andReturn( '1' );

		Monkey\Functions\expect( '\get_post' )
			->never();

		$this->permissions_helper
			->expects( 'should_link_be_displayed' )
			->never();

		$this->link_builder
			->expects( 'build_new_draft_link' )
			->never();

		$this->setOutputCallback( function() {} );
		$this->instance->add_new_draft_post_button();
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) === 0 );
	}

	/**
	 * Tests the add_new_draft_post_button function when the link cannot be displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_Submitbox::add_new_draft_post_button
	 */
	public function test_add_new_draft_post_button_unsuccessful_no_link_allowed() {
		$post            = Mockery::mock( \WP_Post::class );
		$post->post_type = 'post';

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_submitbox' )
			->andReturn( '1' );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post )
			->never();

		$this->permissions_helper
			->expects( 'should_link_be_displayed' )
			->with( $post )
			->andReturnFalse();

		$this->link_builder
			->expects( 'build_new_draft_link' )
			->never();

		$this->setOutputCallback( function() {} );
		$this->instance->add_new_draft_post_button( $post );
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) > 0 );
	}

	/**
	 * Tests the add_rewrite_and_republish_post_button function when a button is displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_Submitbox::add_rewrite_and_republish_post_button
	 */
	public function test_add_rewrite_and_republish_post_button_successful() {
		$post              = Mockery::mock( \WP_Post::class );
		$post->post_type   = 'post';
		$post->post_status = 'publish';
		$url               = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_rewrite&post=201&_wpnonce=94038b7dee';

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_submitbox' )
			->andReturn( '1' );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post )
			->never();

		$this->permissions_helper
			->expects( 'should_link_be_displayed' )
			->with( $post )
			->andReturnTrue();

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->with( $post )
			->andReturn( $url );

		$this->setOutputCallback( function() {} );
		$this->instance->add_rewrite_and_republish_post_button( $post );
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) > 0 );
	}

	/**
	 * Tests the add_rewrite_and_republish_post_button function when a button is displayed and the post ID comes from $_GET.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_Submitbox::add_rewrite_and_republish_post_button
	 */
	public function test_add_rewrite_and_republish_post_button_post_from_GET() {
		$_GET['post']      = '123';
		$post              = Mockery::mock( \WP_Post::class );
		$post->post_type   = 'post';
		$post->post_status = 'publish';
		$url               = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_rewrite&post=201&_wpnonce=94038b7dee';

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_submitbox' )
			->andReturn( '1' );

		Monkey\Functions\expect( '\get_post' )
			->with( 123 )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'should_link_be_displayed' )
			->with( $post )
			->andReturnTrue();

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->with( $post )
			->andReturn( $url );

		$this->setOutputCallback( function() {} );
		$this->instance->add_rewrite_and_republish_post_button();
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) > 0 );
	}

	/**
	 * Tests the add_rewrite_and_republish_post_button function when no post could be retrieved.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_Submitbox::add_rewrite_and_republish_post_button
	 */
	public function test_add_rewrite_and_republish_post_button_no_post() {
		unset( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Intended, to be able to test the method.

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_submitbox' )
			->andReturn( '1' );

		Monkey\Functions\expect( '\get_post' )
			->never();

		$this->permissions_helper
			->expects( 'should_link_be_displayed' )
			->never();

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->never();

		$this->setOutputCallback( function() {} );
		$this->instance->add_rewrite_and_republish_post_button();
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) === 0 );
	}

	/**
	 * Tests the add_rewrite_and_republish_post_button function when the link cannot be displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_Submitbox::add_rewrite_and_republish_post_button
	 */
	public function test_add_rewrite_and_republish_post_button_unsuccessful_is_for_rewrite_and_republish() {
		$post              = Mockery::mock( \WP_Post::class );
		$post->post_type   = 'post';
		$post->post_status = 'publish';

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_submitbox' )
			->andReturn( '1' );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post )
			->never();

		$this->permissions_helper
			->expects( 'should_link_be_displayed' )
			->with( $post )
			->andReturnFalse();

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->never();

		$this->setOutputCallback( function() {} );
		$this->instance->add_rewrite_and_republish_post_button( $post );
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) > 0 );
	}

	/**
	 * Tests the add_rewrite_and_republish_post_button function when the post is not published.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Post_Submitbox::add_rewrite_and_republish_post_button
	 */
	public function test_add_rewrite_and_republish_post_button_not_publish() {
		$post              = Mockery::mock( \WP_Post::class );
		$post->post_type   = 'post';
		$post->post_status = 'draft';

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_submitbox' )
			->andReturn( '1' );

		Monkey\Functions\expect( '\get_post' )
			->never();

		$this->permissions_helper
			->expects( 'should_link_be_displayed' )
			->never();

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->never();

		$this->setOutputCallback( function() {} );
		$this->instance->add_rewrite_and_republish_post_button();
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) === 0 );
	}
}
