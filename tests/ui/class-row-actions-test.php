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
use Yoast\WP\Duplicate_Post\UI\Row_Actions;
use Yoast\WP\Duplicate_Post\UI\Link_Builder;

/**
 * Test the Row_Actions class.
 */
class Row_Actions_Test extends TestCase {

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
	 * @var Row_Actions
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$this->link_builder       = Mockery::mock( Link_Builder::class );
		$this->permissions_helper = Mockery::mock( Permissions_Helper::class );

		$this->instance = Mockery::mock( Row_Actions::class, [ $this->link_builder, $this->permissions_helper ] )
								 ->makePartial();
	}

	/**
	 * Tests if the needed attributes are set correctly.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Row_Actions::__construct
	 */
	public function test_constructor() {
		$this->assertAttributeInstanceOf( Link_Builder::class, 'link_builder', $this->instance );
		$this->assertAttributeInstanceOf( Permissions_Helper::class, 'permissions_helper', $this->instance );
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Row_Actions::register_hooks
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_register_hooks() {
		$utils = \Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );

		$utils->expects( 'get_option' )
			  ->with( 'duplicate_post_show_link_in', 'row' )
			  ->once()
			  ->andReturn( '1' );

		$utils->expects( 'get_option' )
			  ->with( 'duplicate_post_show_link', 'clone' )
			  ->once()
			  ->andReturn( '1' );

		$utils->expects( 'get_option' )
			  ->with( 'duplicate_post_show_link', 'new_draft' )
			  ->once()
			  ->andReturn( '1' );

		$utils->expects( 'get_option' )
			  ->with( 'duplicate_post_show_link', 'rewrite_republish' )
			  ->once()
			  ->andReturn( '1' );

		$this->instance->register_hooks();

		$this->assertNotFalse( \has_filter( 'post_row_actions', [ $this->instance, 'add_clone_action_link' ] ), 'Does not have expected post_row_actions filter' );
		$this->assertNotFalse( \has_filter( 'page_row_actions', [ $this->instance, 'add_clone_action_link' ] ), 'Does not have expected page_row_actions filter' );
		$this->assertNotFalse( \has_filter( 'post_row_actions', [ $this->instance, 'add_new_draft_action_link' ] ), 'Does not have expected post_row_actions filter' );
		$this->assertNotFalse( \has_filter( 'page_row_actions', [ $this->instance, 'add_new_draft_action_link' ] ), 'Does not have expected page_row_actions filter' );
		$this->assertNotFalse( \has_filter( 'post_row_actions', [ $this->instance, 'add_rewrite_and_republish_action_link' ] ), 'Does not have expected post_row_actions filter' );
		$this->assertNotFalse( \has_filter( 'page_row_actions', [ $this->instance, 'add_rewrite_and_republish_action_link' ] ), 'Does not have expected page_row_actions filter' );
	}

	/**
	 * Tests the add_clone_action_link function when the links is displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Row_Actions::add_clone_action_link
	 */
	public function test_add_clone_action_link_successful() {
		$actions          = [
			'edit'                 => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=edit" aria-label="Edit &#8220;Title&#8221;">Edit</a>',
			'inline hide-if-no-js' => '<button type="button" class="button-link editinline" aria-label="Quick edit &#8220;Title&#8221; inline" aria-expanded="false">Quick&nbsp;Edit</button>',
			'trash'                => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=trash&amp;_wpnonce=e52d0bff9b" class="submitdelete" aria-label="Move &#8220;Title&#8221; to the Trash">Trash</a>',
			'view'                 => '<a href="http://basic.wordpress.test/?p=464&#038;preview=true" rel="bookmark" aria-label="Preview &#8220;Title&#8221;">Preview</a>',
		];
		$post             = Mockery::mock( \WP_Post::class );
		$post->post_title = 'Title';
		$post->post_type  = 'post';
		$post->ID         = '464';
		$url              = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_clone&amp;post=464';

		$this->permissions_helper
			->expects( 'should_link_be_displayed' )
			->with( $post )
			->andReturnTrue();

		Monkey\Functions\expect( '\_draft_or_post_title' )
			->with( $post )
			->andReturnUsing(
				function ( $post ) {
					return $post->post_title;
				}
			);

		$this->link_builder
			->expects( 'build_clone_link' )
			->with( $post->ID )
			->andReturn( $url );

		$this->assertSame(
			[
				'edit'                 => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=edit" aria-label="Edit &#8220;Title&#8221;">Edit</a>',
				'inline hide-if-no-js' => '<button type="button" class="button-link editinline" aria-label="Quick edit &#8220;Title&#8221; inline" aria-expanded="false">Quick&nbsp;Edit</button>',
				'trash'                => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=trash&amp;_wpnonce=e52d0bff9b" class="submitdelete" aria-label="Move &#8220;Title&#8221; to the Trash">Trash</a>',
				'view'                 => '<a href="http://basic.wordpress.test/?p=464&#038;preview=true" rel="bookmark" aria-label="Preview &#8220;Title&#8221;">Preview</a>',
				'clone'                => '<a href="http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_clone&amp;post=464" aria-label="Clone &#8220;Title&#8221;">Clone</a>',
			],
			$this->instance->add_clone_action_link( $actions, $post )
		);
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) > 0 );
	}

	/**
	 * Tests the add_clone_action_link function when the link should not be displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Row_Actions::add_clone_action_link
	 */
	public function test_add_clone_action_link_unsuccessful() {
		$actions = [
			'edit'                 => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=edit" aria-label="Edit &#8220;Title&#8221;">Edit</a>',
			'inline hide-if-no-js' => '<button type="button" class="button-link editinline" aria-label="Quick edit &#8220;Title&#8221; inline" aria-expanded="false">Quick&nbsp;Edit</button>',
			'trash'                => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=trash&amp;_wpnonce=e52d0bff9b" class="submitdelete" aria-label="Move &#8220;Title&#8221; to the Trash">Trash</a>',
			'view'                 => '<a href="http://basic.wordpress.test/?p=464&#038;preview=true" rel="bookmark" aria-label="Preview &#8220;Title&#8221;">Preview</a>',
		];
		$post    = Mockery::mock( \WP_Post::class );

		$this->permissions_helper
			->expects( 'should_link_be_displayed' )
			->with( $post )
			->andReturnFalse();

		Monkey\Functions\expect( '\_draft_or_post_title' )
			->never();

		$this->link_builder
			->expects( 'build_clone_link' )
			->never();

		$this->assertSame(
			[
				'edit'                 => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=edit" aria-label="Edit &#8220;Title&#8221;">Edit</a>',
				'inline hide-if-no-js' => '<button type="button" class="button-link editinline" aria-label="Quick edit &#8220;Title&#8221; inline" aria-expanded="false">Quick&nbsp;Edit</button>',
				'trash'                => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=trash&amp;_wpnonce=e52d0bff9b" class="submitdelete" aria-label="Move &#8220;Title&#8221; to the Trash">Trash</a>',
				'view'                 => '<a href="http://basic.wordpress.test/?p=464&#038;preview=true" rel="bookmark" aria-label="Preview &#8220;Title&#8221;">Preview</a>',
			],
			$this->instance->add_clone_action_link( $actions, $post )
		);
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) > 0 );
	}

	/**
	 * Tests the add_new_draft_action_link function when the links is displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Row_Actions::add_new_draft_action_link
	 */
	public function test_add_new_draft_action_link_successful() {
		$actions          = [
			'edit'                 => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=edit" aria-label="Edit &#8220;Title&#8221;">Edit</a>',
			'inline hide-if-no-js' => '<button type="button" class="button-link editinline" aria-label="Quick edit &#8220;Title&#8221; inline" aria-expanded="false">Quick&nbsp;Edit</button>',
			'trash'                => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=trash&amp;_wpnonce=e52d0bff9b" class="submitdelete" aria-label="Move &#8220;Title&#8221; to the Trash">Trash</a>',
			'view'                 => '<a href="http://basic.wordpress.test/?p=464&#038;preview=true" rel="bookmark" aria-label="Preview &#8220;Title&#8221;">Preview</a>',
		];
		$post             = Mockery::mock( \WP_Post::class );
		$post->post_title = 'Title';
		$post->post_type  = 'post';
		$post->ID         = '464';
		$url              = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_new_draft&amp;post=464';

		$this->permissions_helper
			->expects( 'should_link_be_displayed' )
			->with( $post )
			->andReturnTrue();

		Monkey\Functions\expect( '\_draft_or_post_title' )
			->with( $post )
			->andReturnUsing(
				function ( $post ) {
					return $post->post_title;
				}
			);

		$this->link_builder
			->expects( 'build_new_draft_link' )
			->with( $post->ID )
			->andReturn( $url );

		$this->assertSame(
			[
				'edit'                 => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=edit" aria-label="Edit &#8220;Title&#8221;">Edit</a>',
				'inline hide-if-no-js' => '<button type="button" class="button-link editinline" aria-label="Quick edit &#8220;Title&#8221; inline" aria-expanded="false">Quick&nbsp;Edit</button>',
				'trash'                => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=trash&amp;_wpnonce=e52d0bff9b" class="submitdelete" aria-label="Move &#8220;Title&#8221; to the Trash">Trash</a>',
				'view'                 => '<a href="http://basic.wordpress.test/?p=464&#038;preview=true" rel="bookmark" aria-label="Preview &#8220;Title&#8221;">Preview</a>',
				'edit_as_new_draft'    => '<a href="http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_new_draft&amp;post=464" aria-label="New draft of &#8220;Title&#8221;">New Draft</a>',
			],
			$this->instance->add_new_draft_action_link( $actions, $post )
		);
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) > 0 );
	}

	/**
	 * Tests the add_new_draft_action_link function when the link should not be displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Row_Actions::add_new_draft_action_link
	 */
	public function test_add_new_draft_action_link_unsuccessful() {
		$actions = [
			'edit'                 => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=edit" aria-label="Edit &#8220;Title&#8221;">Edit</a>',
			'inline hide-if-no-js' => '<button type="button" class="button-link editinline" aria-label="Quick edit &#8220;Title&#8221; inline" aria-expanded="false">Quick&nbsp;Edit</button>',
			'trash'                => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=trash&amp;_wpnonce=e52d0bff9b" class="submitdelete" aria-label="Move &#8220;Title&#8221; to the Trash">Trash</a>',
			'view'                 => '<a href="http://basic.wordpress.test/?p=464&#038;preview=true" rel="bookmark" aria-label="Preview &#8220;Title&#8221;">Preview</a>',
		];
		$post    = Mockery::mock( \WP_Post::class );

		$this->permissions_helper
			->expects( 'should_link_be_displayed' )
			->with( $post )
			->andReturnFalse();

		Monkey\Functions\expect( '\_draft_or_post_title' )
			->never();

		$this->link_builder
			->expects( 'build_new_draft_link' )
			->never();

		$this->assertSame(
			[
				'edit'                 => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=edit" aria-label="Edit &#8220;Title&#8221;">Edit</a>',
				'inline hide-if-no-js' => '<button type="button" class="button-link editinline" aria-label="Quick edit &#8220;Title&#8221; inline" aria-expanded="false">Quick&nbsp;Edit</button>',
				'trash'                => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=trash&amp;_wpnonce=e52d0bff9b" class="submitdelete" aria-label="Move &#8220;Title&#8221; to the Trash">Trash</a>',
				'view'                 => '<a href="http://basic.wordpress.test/?p=464&#038;preview=true" rel="bookmark" aria-label="Preview &#8220;Title&#8221;">Preview</a>',
			],
			$this->instance->add_new_draft_action_link( $actions, $post )
		);
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) > 0 );
	}

	/**
	 * Tests the add_rewrite_and_republish_action_link function when the links is displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Row_Actions::add_rewrite_and_republish_action_link
	 */
	public function test_add_rewrite_and_republish_action_link_successful() {
		$actions           = [
			'edit'                 => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=edit" aria-label="Edit &#8220;Title&#8221;">Edit</a>',
			'inline hide-if-no-js' => '<button type="button" class="button-link editinline" aria-label="Quick edit &#8220;Title&#8221; inline" aria-expanded="false">Quick&nbsp;Edit</button>',
			'trash'                => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=trash&amp;_wpnonce=e52d0bff9b" class="submitdelete" aria-label="Move &#8220;Title&#8221; to the Trash">Trash</a>',
			'view'                 => '<a href="http://basic.wordpress.test/?p=464&#038;preview=true" rel="bookmark" aria-label="Preview &#8220;Title&#8221;">Preview</a>',
		];
		$post              = Mockery::mock( \WP_Post::class );
		$post->post_title  = 'Title';
		$post->post_type   = 'post';
		$post->post_status = 'publish';
		$post->ID          = '464';
		$url               = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_rewrite&amp;post=464';

		$this->permissions_helper
			->expects( 'should_link_be_displayed' )
			->with( $post )
			->andReturnTrue();

		Monkey\Functions\expect( '\_draft_or_post_title' )
			->with( $post )
			->andReturnUsing(
				function ( $post ) {
					return $post->post_title;
				}
			);

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->with( $post->ID )
			->andReturn( $url );

		$this->assertSame(
			[
				'edit'                 => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=edit" aria-label="Edit &#8220;Title&#8221;">Edit</a>',
				'inline hide-if-no-js' => '<button type="button" class="button-link editinline" aria-label="Quick edit &#8220;Title&#8221; inline" aria-expanded="false">Quick&nbsp;Edit</button>',
				'trash'                => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=trash&amp;_wpnonce=e52d0bff9b" class="submitdelete" aria-label="Move &#8220;Title&#8221; to the Trash">Trash</a>',
				'view'                 => '<a href="http://basic.wordpress.test/?p=464&#038;preview=true" rel="bookmark" aria-label="Preview &#8220;Title&#8221;">Preview</a>',
				'rewrite'              => '<a href="http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_rewrite&amp;post=464" aria-label="Rewrite & Republish &#8220;Title&#8221;">Rewrite & Republish</a>',
			],
			$this->instance->add_rewrite_and_republish_action_link( $actions, $post )
		);
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) > 0 );
	}

	/**
	 * Tests the add_rewrite_and_republish_action_link function when the link should not be displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Row_Actions::add_rewrite_and_republish_action_link
	 */
	public function test_add_rewrite_and_republish_action_link_unsuccessful_should_not_be_displayed() {
		$actions           = [
			'edit'                 => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=edit" aria-label="Edit &#8220;Title&#8221;">Edit</a>',
			'inline hide-if-no-js' => '<button type="button" class="button-link editinline" aria-label="Quick edit &#8220;Title&#8221; inline" aria-expanded="false">Quick&nbsp;Edit</button>',
			'trash'                => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=trash&amp;_wpnonce=e52d0bff9b" class="submitdelete" aria-label="Move &#8220;Title&#8221; to the Trash">Trash</a>',
			'view'                 => '<a href="http://basic.wordpress.test/?p=464&#038;preview=true" rel="bookmark" aria-label="Preview &#8220;Title&#8221;">Preview</a>',
		];
		$post              = Mockery::mock( \WP_Post::class );
		$post->post_status = 'publish';

		$this->permissions_helper
			->expects( 'should_link_be_displayed' )
			->with( $post )
			->andReturnFalse();

		Monkey\Functions\expect( '\_draft_or_post_title' )
			->never();

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->never();

		$this->assertSame(
			[
				'edit'                 => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=edit" aria-label="Edit &#8220;Title&#8221;">Edit</a>',
				'inline hide-if-no-js' => '<button type="button" class="button-link editinline" aria-label="Quick edit &#8220;Title&#8221; inline" aria-expanded="false">Quick&nbsp;Edit</button>',
				'trash'                => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=trash&amp;_wpnonce=e52d0bff9b" class="submitdelete" aria-label="Move &#8220;Title&#8221; to the Trash">Trash</a>',
				'view'                 => '<a href="http://basic.wordpress.test/?p=464&#038;preview=true" rel="bookmark" aria-label="Preview &#8220;Title&#8221;">Preview</a>',
			],
			$this->instance->add_rewrite_and_republish_action_link( $actions, $post )
		);
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) > 0 );
	}

	/**
	 * Tests the add_rewrite_and_republish_action_link function when the post is not published.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Row_Actions::add_rewrite_and_republish_action_link
	 */
	public function test_add_rewrite_and_republish_action_link_unsuccessful_not_published() {
		$actions           = [
			'edit'                 => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=edit" aria-label="Edit &#8220;Title&#8221;">Edit</a>',
			'inline hide-if-no-js' => '<button type="button" class="button-link editinline" aria-label="Quick edit &#8220;Title&#8221; inline" aria-expanded="false">Quick&nbsp;Edit</button>',
			'trash'                => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=trash&amp;_wpnonce=e52d0bff9b" class="submitdelete" aria-label="Move &#8220;Title&#8221; to the Trash">Trash</a>',
			'view'                 => '<a href="http://basic.wordpress.test/?p=464&#038;preview=true" rel="bookmark" aria-label="Preview &#8220;Title&#8221;">Preview</a>',
		];
		$post              = Mockery::mock( \WP_Post::class );
		$post->post_status = 'draft';

		$this->permissions_helper
			->expects( 'should_link_be_displayed' )
			->never();

		Monkey\Functions\expect( '\_draft_or_post_title' )
			->never();

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->never();

		$this->assertSame(
			[
				'edit'                 => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=edit" aria-label="Edit &#8220;Title&#8221;">Edit</a>',
				'inline hide-if-no-js' => '<button type="button" class="button-link editinline" aria-label="Quick edit &#8220;Title&#8221; inline" aria-expanded="false">Quick&nbsp;Edit</button>',
				'trash'                => '<a href="http://basic.wordpress.test/wp-admin/post.php?post=464&amp;action=trash&amp;_wpnonce=e52d0bff9b" class="submitdelete" aria-label="Move &#8220;Title&#8221; to the Trash">Trash</a>',
				'view'                 => '<a href="http://basic.wordpress.test/?p=464&#038;preview=true" rel="bookmark" aria-label="Preview &#8220;Title&#8221;">Preview</a>',
			],
			$this->instance->add_rewrite_and_republish_action_link( $actions, $post )
		);
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) === 0 );
	}
}
