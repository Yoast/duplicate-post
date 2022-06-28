<?php

namespace Yoast\WP\Duplicate_Post\Tests\UI;

use Brain\Monkey;
use Mockery;
use WP_Post;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Tests\TestCase;
use Yoast\WP\Duplicate_Post\UI\Asset_Manager;
use Yoast\WP\Duplicate_Post\UI\Classic_Editor;
use Yoast\WP\Duplicate_Post\UI\Link_Builder;

/**
 * Test the Classic_Editor class.
 */
class Classic_Editor_Test extends TestCase {

	/**
	 * Holds the object to create the action link to duplicate.
	 *
	 * @var Link_Builder|Mockery\MockInterface
	 */
	protected $link_builder;

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper|Mockery\MockInterface
	 */
	protected $permissions_helper;

	/**
	 * Holds the asset manager.
	 *
	 * @var Asset_Manager|Mockery\MockInterface
	 */
	protected $asset_manager;

	/**
	 * The instance.
	 *
	 * @var Classic_Editor&Mockery\MockInterface
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	protected function set_up() {
		parent::set_up();

		$this->link_builder       = Mockery::mock( Link_Builder::class );
		$this->permissions_helper = Mockery::mock( Permissions_Helper::class );
		$this->asset_manager      = Mockery::mock( Asset_Manager::class );

		$this->instance = Mockery::mock(
			Classic_Editor::class,
			[
				$this->link_builder,
				$this->permissions_helper,
				$this->asset_manager,
			]
		)->makePartial();
	}

	/**
	 * Tests if the needed attributes are set correctly.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::__construct
	 */
	public function test_constructor() {
		$this->assertInstanceOf(
			Link_Builder::class,
			$this->getPropertyValue( $this->instance, 'link_builder' )
		);

		$this->assertInstanceOf(
			Permissions_Helper::class,
			$this->getPropertyValue( $this->instance, 'permissions_helper' )
		);
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::register_hooks
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_register_hooks() {
		$utils = Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );

		$utils->expects( 'get_option' )
			->with( 'duplicate_post_show_link_in', 'submitbox' )
			->twice()
			->andReturn( '1' );

		$utils->expects( 'get_option' )
			->with( 'duplicate_post_show_link', 'new_draft' )
			->twice()
			->andReturn( '1' );

		$utils->expects( 'get_option' )
			->with( 'duplicate_post_show_link', 'rewrite_republish' )
			->once()
			->andReturn( '1' );

		$this->instance->register_hooks();

		$this->assertNotFalse( \has_action( 'post_submitbox_start', [ $this->instance, 'add_new_draft_post_button' ] ), 'Does not have expected post_submitbox_start action' );
		$this->assertNotFalse( \has_action( 'post_submitbox_start', [ $this->instance, 'add_rewrite_and_republish_post_button' ] ), 'Does not have expected post_submitbox_start action' );
		$this->assertNotFalse( \has_action( 'post_submitbox_misc_actions', [ $this->instance, 'add_check_changes_link' ] ), 'Does not have expected post_submitbox_misc_actions action' );

		$this->assertNotFalse( \has_action( 'load-post.php', [ $this->instance, 'hook_translations' ] ), 'Does not have expected load-post.php action' );
		$this->assertNotFalse( \has_filter( 'post_updated_messages', [ $this->instance, 'change_scheduled_notice_classic_editor' ] ), 'Does not have expected post_updated_messages filter' );

		$this->assertNotFalse( \has_action( 'admin_enqueue_scripts', [ $this->instance, 'enqueue_classic_editor_scripts' ] ), 'Does not have expected admin_enqueue_scripts action (scripts)' );
		$this->assertNotFalse( \has_action( 'admin_enqueue_scripts', [ $this->instance, 'enqueue_classic_editor_styles' ] ), 'Does not have expected admin_enqueue_scripts action (styles)' );

		$this->assertNotFalse( \has_action( 'add_meta_boxes', [ $this->instance, 'remove_slug_meta_box' ] ), 'Does not have expected add_meta_boxes action' );
		$this->assertNotFalse( \has_filter( 'get_sample_permalink_html', [ $this->instance, 'remove_sample_permalink_slug_editor' ] ), 'Does not have expected get_sample_permalink_html filter' );
	}

	/**
	 * Tests the registration of the translation hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::hook_translations
	 */
	public function test_hook_translations() {
		$this->instance->hook_translations();

		$this->assertNotFalse( \has_filter( 'gettext', [ $this->instance, 'change_republish_strings_classic_editor' ] ), 'Does not have expected gettext filter' );
		$this->assertNotFalse( \has_filter( 'gettext_with_context', [ $this->instance, 'change_schedule_strings_classic_editor' ] ), 'Does not have expected gettext_with_context filter' );
	}

	/**
	 * Tests the successful enqueue_classic_editor_scripts function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::enqueue_classic_editor_scripts
	 */
	public function test_enqueue_classic_editor_scripts() {
		$_GET['post'] = '123';
		$post         = Mockery::mock( WP_Post::class );

		$this->permissions_helper->expects( 'is_classic_editor' )
			->andReturnTrue();

		Monkey\Functions\expect( '\get_post' )
			->with( 123 )
			->andReturn( $post );

		$this->permissions_helper->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->once()
			->andReturnTrue();

		$this->asset_manager
			->expects( 'enqueue_strings_script' );

		$this->instance->enqueue_classic_editor_scripts();

		// Clean up after the test.
		unset( $_GET['post'] );
	}

	/**
	 * Tests the successful enqueue_classic_editor_styles function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::enqueue_classic_editor_styles
	 */
	public function test_enqueue_classic_editor_styles() {
		$_GET['post'] = '123';
		$post         = Mockery::mock( WP_Post::class );

		$this->permissions_helper->expects( 'is_classic_editor' )
			->andReturnTrue();

		Monkey\Functions\expect( '\get_post' )
			->with( 123 )
			->andReturn( $post );

		$this->permissions_helper->expects( 'should_links_be_displayed' )
			->with( $post )
			->andReturnTrue();

		$this->asset_manager
			->expects( 'enqueue_styles' );

		$this->instance->enqueue_classic_editor_styles();

		// Clean up after the test.
		unset( $_GET['post'] );
	}

	/**
	 * Tests the add_new_draft_post_button function when a button is displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::add_new_draft_post_button
	 */
	public function test_add_new_draft_post_button_successful() {
		$this->stubEscapeFunctions();
		$this->stubTranslationFunctions();

		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';
		$url             = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_new_draft&post=201&_wpnonce=94038b7dee';

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_submitbox' )
			->andReturn( '1' );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post )
			->never();

		$this->permissions_helper
			->expects( 'should_links_be_displayed' )
			->with( $post )
			->andReturnTrue();

		$this->link_builder
			->expects( 'build_new_draft_link' )
			->with( $post )
			->andReturn( $url );

		$this->expectOutputRegex(
			'`\s*<div id="duplicate-action">\s+<a class="submitduplicate duplication"\s+href="[^"]+">Copy to a new draft\s+</a>\s+</div>`'
		);

		$this->instance->add_new_draft_post_button( $post );
	}

	/**
	 * Tests the add_new_draft_post_button function when a button is displayed and the post ID comes from $_GET.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::add_new_draft_post_button
	 */
	public function test_add_new_draft_post_button_successful_post_from_GET() {
		$this->stubEscapeFunctions();
		$this->stubTranslationFunctions();

		$_GET['post']    = '123';
		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';
		$url             = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_new_draft&post=123&_wpnonce=94038b7dee';

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_submitbox' )
			->andReturn( '1' );

		Monkey\Functions\expect( '\get_post' )
			->with( 123 )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'should_links_be_displayed' )
			->with( $post )
			->andReturnTrue();

		$this->link_builder
			->expects( 'build_new_draft_link' )
			->with( $post )
			->andReturn( $url );

		$this->expectOutputRegex(
			'`\s*<div id="duplicate-action">\s+<a class="submitduplicate duplication"\s+href="[^"]+">Copy to a new draft\s+</a>\s+</div>`'
		);

		$this->instance->add_new_draft_post_button();

		// Clean up after the test.
		unset( $_GET['post'] );
	}

	/**
	 * Tests the add_new_draft_post_button function when no post could be retrieved.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::add_new_draft_post_button
	 */
	public function test_add_new_draft_post_button_unsuccessful_no_post() {
		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_submitbox' )
			->andReturn( '1' );

		Monkey\Functions\expect( '\get_post' )
			->never();

		$this->permissions_helper
			->expects( 'should_links_be_displayed' )
			->never();

		$this->link_builder
			->expects( 'build_new_draft_link' )
			->never();

		$this->instance->add_new_draft_post_button();
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) === 0 );
	}

	/**
	 * Tests the add_new_draft_post_button function when the link cannot be displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::add_new_draft_post_button
	 */
	public function test_add_new_draft_post_button_unsuccessful_no_link_allowed() {
		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_submitbox' )
			->andReturn( '1' );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post )
			->never();

		$this->permissions_helper
			->expects( 'should_links_be_displayed' )
			->with( $post )
			->andReturnFalse();

		$this->link_builder
			->expects( 'build_new_draft_link' )
			->never();

		$this->instance->add_new_draft_post_button( $post );
	}

	/**
	 * Tests the add_rewrite_and_republish_post_button function when a button is displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::add_rewrite_and_republish_post_button
	 */
	public function test_add_rewrite_and_republish_post_button_successful() {
		$this->stubEscapeFunctions();
		$this->stubTranslationFunctions();

		$post              = Mockery::mock( WP_Post::class );
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
			->expects( 'should_links_be_displayed' )
			->with( $post )
			->andReturnTrue();

		$this->permissions_helper
			->expects( 'should_rewrite_and_republish_be_allowed' )
			->with( $post )
			->andReturnTrue();

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->with( $post )
			->andReturn( $url );

		$this->expectOutputRegex(
			'`\s*<div id="rewrite-republish-action">\s+<a class="submitduplicate duplication" href="[^"]+">Rewrite & Republish\s+</a>\s+</div>`'
		);

		$this->instance->add_rewrite_and_republish_post_button( $post );
	}

	/**
	 * Tests the add_rewrite_and_republish_post_button function when a button is displayed and the post ID comes from $_GET.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::add_rewrite_and_republish_post_button
	 */
	public function test_add_rewrite_and_republish_post_button_post_from_GET() {
		$this->stubEscapeFunctions();
		$this->stubTranslationFunctions();

		$_GET['post']      = '123';
		$post              = Mockery::mock( WP_Post::class );
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
			->expects( 'should_links_be_displayed' )
			->with( $post )
			->andReturnTrue();

		$this->permissions_helper
			->expects( 'should_rewrite_and_republish_be_allowed' )
			->with( $post )
			->andReturnTrue();

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->with( $post )
			->andReturn( $url );

		$this->expectOutputRegex(
			'`\s*<div id="rewrite-republish-action">\s+<a class="submitduplicate duplication" href="[^"]+">Rewrite & Republish\s+</a>\s+</div>`'
		);

		$this->instance->add_rewrite_and_republish_post_button();

		// Clean up after the test.
		unset( $_GET['post'] );
	}

	/**
	 * Tests the add_rewrite_and_republish_post_button function when no post could be retrieved.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::add_rewrite_and_republish_post_button
	 */
	public function test_add_rewrite_and_republish_post_button_no_post() {
		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_submitbox' )
			->andReturn( '1' );

		Monkey\Functions\expect( '\get_post' )
			->never();

		$this->permissions_helper
			->expects( 'should_links_be_displayed' )
			->never();

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->never();

		$this->instance->add_rewrite_and_republish_post_button();
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) === 0 );
	}

	/**
	 * Tests the add_rewrite_and_republish_post_button function when the link cannot be displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::add_rewrite_and_republish_post_button
	 */
	public function test_add_rewrite_and_republish_post_button_unsuccessful_is_for_rewrite_and_republish() {
		$post              = Mockery::mock( WP_Post::class );
		$post->post_type   = 'post';
		$post->post_status = 'publish';

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_submitbox' )
			->andReturn( '1' );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post )
			->never();

		$this->permissions_helper
			->expects( 'should_links_be_displayed' )
			->with( $post )
			->never();

		$this->permissions_helper
			->expects( 'should_rewrite_and_republish_be_allowed' )
			->with( $post )
			->andReturnFalse();

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->never();

		$this->instance->add_rewrite_and_republish_post_button( $post );
	}

	/**
	 * Tests the add_rewrite_and_republish_post_button function when the post is not published.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::add_rewrite_and_republish_post_button
	 */
	public function test_add_rewrite_and_republish_post_button_not_publish() {
		$post              = Mockery::mock( WP_Post::class );
		$post->post_type   = 'post';
		$post->post_status = 'draft';

		Monkey\Functions\expect( '\get_option' )
			->with( 'duplicate_post_show_submitbox' )
			->andReturn( '1' );

		Monkey\Functions\expect( '\get_post' )
			->never();

		$this->permissions_helper
			->expects( 'should_links_be_displayed' )
			->never();

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->never();

		$this->instance->add_rewrite_and_republish_post_button();
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) === 0 );
	}

	/**
	 * Tests the change_republish_strings_classic_editor function when the copy should be changed in the case of the date label.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::change_republish_strings_classic_editor
	 */
	public function test_should_change_republish_strings_date_label() {
		$this->stubTranslationFunctions();

		$text   = 'Publish on: %s';
		$domain = 'default';

		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		Monkey\Functions\expect( '\get_post' )
			->once()
			->andReturn( $post );

		$this->instance->expects( 'should_change_rewrite_republish_copy' )
			->with( $post )
			->once()
			->andReturnTrue();

		$this->assertSame( 'Republish on: %s', $this->instance->change_republish_strings_classic_editor( '', $text, $domain ) );
	}

	/**
	 * Tests the change_republish_strings_classic_editor function when the copy should be changed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::change_republish_strings_classic_editor
	 */
	public function test_should_change_republish_strings() {
		$this->stubTranslationFunctions();

		$text   = 'Publish';
		$domain = 'default';

		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		Monkey\Functions\expect( '\get_post' )
			->once()
			->andReturn( $post );

		$this->instance->expects( 'should_change_rewrite_republish_copy' )
			->with( $post )
			->once()
			->andReturnTrue();

		$this->assertSame( 'Republish', $this->instance->change_republish_strings_classic_editor( '', $text, $domain ) );
	}

	/**
	 * Tests the change_republish_strings_classic_editor function when the copy should not be changed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::change_republish_strings_classic_editor
	 */
	public function test_should_not_change_republish_strings() {
		$text        = 'Publish';
		$translation = 'Publish';
		$domain      = 'default';

		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		Monkey\Functions\expect( '\get_post' )
			->once()
			->andReturn( $post );

		$this->instance->expects( 'should_change_rewrite_republish_copy' )
			->with( $post )
			->once()
			->andReturnFalse();

		$this->assertSame( 'Publish', $this->instance->change_republish_strings_classic_editor( $translation, $text, $domain ) );
	}

	/**
	 * Tests the change_republish_strings_classic_editor function when the copy should not be changed,
	 * because the copy is not 'Publish'.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::change_republish_strings_classic_editor
	 */
	public function test_should_not_change_republish_strings_other_text() {
		$text        = 'Test';
		$translation = 'Test';
		$domain      = 'default';

		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		Monkey\Functions\expect( '\get_post' )
			->never();

		$this->instance->expects( 'should_change_rewrite_republish_copy' )
			->with( $post )
			->never();

		$this->assertSame( 'Test', $this->instance->change_republish_strings_classic_editor( $translation, $text, $domain ) );
	}

	/**
	 * Tests the change_republish_strings_classic_editor function when the copy should not be changed,
	 * because the domain is not 'default'.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::change_republish_strings_classic_editor
	 */
	public function test_should_not_change_republish_strings_other_domain() {
		$text        = 'Publish';
		$translation = 'Publish';
		$domain      = 'other';

		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		Monkey\Functions\expect( '\get_post' )
			->never();

		$this->instance->expects( 'should_change_rewrite_republish_copy' )
			->with( $post )
			->never();

		$this->assertSame( 'Publish', $this->instance->change_republish_strings_classic_editor( $translation, $text, $domain ) );
	}

	/**
	 * Tests the change_schedule_strings_classic_editor function when the copy should be changed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::change_schedule_strings_classic_editor
	 */
	public function test_should_change_schedule_strings() {
		$this->stubTranslationFunctions();

		$text    = 'Schedule';
		$context = 'post action/button label';
		$domain  = 'default';

		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		Monkey\Functions\expect( '\get_post' )
			->once()
			->andReturn( $post );

		$this->instance->expects( 'should_change_rewrite_republish_copy' )
			->with( $post )
			->once()
			->andReturnTrue();

		$this->assertSame( 'Schedule republish', $this->instance->change_schedule_strings_classic_editor( '', $text, $context, $domain ) );
	}

	/**
	 * Tests the change_schedule_strings_classic_editor function when the copy should not be changed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::change_schedule_strings_classic_editor
	 */
	public function test_should_not_change_schedule_strings() {
		$text        = 'Schedule';
		$translation = 'Schedule';
		$context     = 'post action/button label';
		$domain      = 'default';

		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		Monkey\Functions\expect( '\get_post' )
			->once()
			->andReturn( $post );

		$this->instance->expects( 'should_change_rewrite_republish_copy' )
			->with( $post )
			->once()
			->andReturnFalse();

		$this->assertSame( 'Schedule', $this->instance->change_schedule_strings_classic_editor( $translation, $text, $context, $domain ) );
	}

	/**
	 * Tests the change_republish_strings_classic_editor function when the copy should not be changed,
	 * because the copy is not 'Schedule'.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::change_schedule_strings_classic_editor
	 */
	public function test_should_not_change_schedule_strings_other_text() {
		$text        = 'Test';
		$translation = 'Test';
		$context     = 'post action/button label';
		$domain      = 'default';

		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		Monkey\Functions\expect( '\get_post' )
			->never();

		$this->instance->expects( 'should_change_rewrite_republish_copy' )
			->with( $post )
			->never();

		$this->assertSame( 'Test', $this->instance->change_schedule_strings_classic_editor( $translation, $text, $context, $domain ) );
	}

	/**
	 * Tests the change_republish_strings_classic_editor function when the copy should not be changed,
	 * because the domain is not 'default'.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::change_schedule_strings_classic_editor
	 */
	public function test_should_not_change_schedule_strings_other_domain() {
		$text        = 'Schedule';
		$translation = 'Schedule';
		$context     = 'post action/button label';
		$domain      = 'other';

		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		Monkey\Functions\expect( '\get_post' )
			->never();

		$this->instance->expects( 'should_change_rewrite_republish_copy' )
			->with( $post )
			->never();

		$this->assertSame( 'Schedule', $this->instance->change_schedule_strings_classic_editor( $translation, $text, $context, $domain ) );
	}

	/**
	 * Tests the change_republish_strings_classic_editor function when the copy should not be changed,
	 * because the context is not 'default'.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::change_schedule_strings_classic_editor
	 */
	public function test_should_not_change_schedule_strings_other_context() {
		$text        = 'Schedule';
		$translation = 'Schedule';
		$context     = 'other context';
		$domain      = 'default';

		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		Monkey\Functions\expect( '\get_post' )
			->never();

		$this->instance->expects( 'should_change_rewrite_republish_copy' )
			->with( $post )
			->never();

		$this->assertSame( 'Schedule', $this->instance->change_schedule_strings_classic_editor( $translation, $text, $context, $domain ) );
	}

	/**
	 * Tests the change_scheduled_notice_classic_editor function when the copy should be changed for a post.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::change_scheduled_notice_classic_editor
	 */
	public function test_should_change_scheduled_notice_post() {
		$this->stubTranslationFunctions();

		$post             = Mockery::mock( WP_Post::class );
		$post->post_type  = 'post';
		$post->post_title = 'example_post';
		$post->ID         = 1;

		$permalink      = 'http://basic.wordpress.test/example_post';
		$date_format    = 'F j, Y';
		$scheduled_date = 'December 18, 2020';
		$time_format    = 'g:i a';
		$scheduled_time = '2:30 pm';

		$messages         = [];
		$messages['post'] = [
			0  => '', // Unused. Messages start at index 1.
			1  => 'Post updated.',
			2  => 'Custom field updated.',
			3  => 'Custom field deleted.',
			4  => 'Post updated.',
			5  => 'Post restored to revision.',
			6  => 'Post published.',
			7  => 'Post saved.',
			8  => 'Post submitted.',
			9  => 'Post scheduled for: <strong>' . $scheduled_date . ' ' . $scheduled_time . '</strong>',
			10 => 'Post draft updated.',
		];
		$messages['page'] = [
			0  => '', // Unused. Messages start at index 1.
			1  => 'Page updated.',
			2  => 'Custom field updated.',
			3  => 'Custom field deleted.',
			4  => 'Page updated.',
			5  => 'Page restored to revision.',
			6  => 'Page published.',
			7  => 'Page saved.',
			8  => 'Page submitted.',
			9  => 'Page scheduled for: <strong>' . $scheduled_date . ' ' . $scheduled_time . '</strong>',
			10 => 'Page draft updated.',
		];

		$new_copy = 'This rewritten post <a href="' . $permalink . '">' . $post->post_title . '</a> is now scheduled to replace the original post. It will be published on <strong>' . $scheduled_date . ' ' . $scheduled_time . '</strong>.';

		$result         = [];
		$result['post'] = [
			0  => '', // Unused. Messages start at index 1.
			1  => 'Post updated.',
			2  => 'Custom field updated.',
			3  => 'Custom field deleted.',
			4  => 'Post updated.',
			5  => 'Post restored to revision.',
			6  => 'Post published.',
			7  => 'Post saved.',
			8  => 'Post submitted.',
			9  => $new_copy,
			10 => 'Post draft updated.',
		];
		$result['page'] = [
			0  => '', // Unused. Messages start at index 1.
			1  => 'Page updated.',
			2  => 'Custom field updated.',
			3  => 'Custom field deleted.',
			4  => 'Page updated.',
			5  => 'Page restored to revision.',
			6  => 'Page published.',
			7  => 'Page saved.',
			8  => 'Page submitted.',
			9  => 'Page scheduled for: <strong>' . $scheduled_date . ' ' . $scheduled_time . '</strong>',
			10 => 'Page draft updated.',
		];

		Monkey\Functions\expect( '\get_post' )
			->once()
			->andReturn( $post );

		$this->instance->expects( 'should_change_rewrite_republish_copy' )
			->with( $post )
			->once()
			->andReturnTrue();

		Monkey\Functions\expect( '\get_permalink' )
			->once()
			->with( $post->ID )
			->andReturn( $permalink );

		Monkey\Functions\expect( '\get_option' )
			->once()
			->with( 'date_format' )
			->andReturn( $date_format );

		Monkey\Functions\expect( '\get_option' )
			->once()
			->with( 'time_format' )
			->andReturn( $time_format );

		Monkey\Functions\expect( '\get_the_time' )
			->once()
			->with( $date_format, $post )
			->andReturn( $scheduled_date );

		Monkey\Functions\expect( '\get_the_time' )
			->once()
			->with( $time_format, $post )
			->andReturn( $scheduled_time );

		$this->assertSame( $result, $this->instance->change_scheduled_notice_classic_editor( $messages ) );
	}

	/**
	 * Tests the change_scheduled_notice_classic_editor function when the copy should be changed for a page.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::change_scheduled_notice_classic_editor
	 */
	public function test_should_change_scheduled_notice_page() {
		$this->stubTranslationFunctions();

		$post             = Mockery::mock( WP_Post::class );
		$post->post_type  = 'page';
		$post->post_title = 'example_page';
		$post->ID         = 1;

		$permalink      = 'http://basic.wordpress.test/example_page';
		$date_format    = 'F j, Y';
		$scheduled_date = 'December 18, 2020';
		$time_format    = 'g:i a';
		$scheduled_time = '2:30 pm';

		$messages         = [];
		$messages['post'] = [
			0  => '', // Unused. Messages start at index 1.
			1  => 'Post updated.',
			2  => 'Custom field updated.',
			3  => 'Custom field deleted.',
			4  => 'Post updated.',
			5  => 'Post restored to revision.',
			6  => 'Post published.',
			7  => 'Post saved.',
			8  => 'Post submitted.',
			9  => 'Post scheduled for: <strong>' . $scheduled_date . ' ' . $scheduled_time . '</strong>',
			10 => 'Post draft updated.',
		];
		$messages['page'] = [
			0  => '', // Unused. Messages start at index 1.
			1  => 'Page updated.',
			2  => 'Custom field updated.',
			3  => 'Custom field deleted.',
			4  => 'Page updated.',
			5  => 'Page restored to revision.',
			6  => 'Page published.',
			7  => 'Page saved.',
			8  => 'Page submitted.',
			9  => 'Page scheduled for: <strong>' . $scheduled_date . ' ' . $scheduled_time . '</strong>',
			10 => 'Page draft updated.',
		];

		$new_copy = 'This rewritten page <a href="' . $permalink . '">' . $post->post_title . '</a> is now scheduled to replace the original page. It will be published on <strong>' . $scheduled_date . ' ' . $scheduled_time . '</strong>.';

		$result         = [];
		$result['post'] = [
			0  => '', // Unused. Messages start at index 1.
			1  => 'Post updated.',
			2  => 'Custom field updated.',
			3  => 'Custom field deleted.',
			4  => 'Post updated.',
			5  => 'Post restored to revision.',
			6  => 'Post published.',
			7  => 'Post saved.',
			8  => 'Post submitted.',
			9  => 'Post scheduled for: <strong>' . $scheduled_date . ' ' . $scheduled_time . '</strong>',
			10 => 'Post draft updated.',
		];
		$result['page'] = [
			0  => '', // Unused. Messages start at index 1.
			1  => 'Page updated.',
			2  => 'Custom field updated.',
			3  => 'Custom field deleted.',
			4  => 'Page updated.',
			5  => 'Page restored to revision.',
			6  => 'Page published.',
			7  => 'Page saved.',
			8  => 'Page submitted.',
			9  => $new_copy,
			10 => 'Page draft updated.',
		];

		Monkey\Functions\expect( '\get_post' )
			->once()
			->andReturn( $post );

		$this->instance->expects( 'should_change_rewrite_republish_copy' )
			->with( $post )
			->once()
			->andReturnTrue();

		Monkey\Functions\expect( '\get_permalink' )
			->once()
			->with( $post->ID )
			->andReturn( $permalink );

		Monkey\Functions\expect( '\get_option' )
			->once()
			->with( 'date_format' )
			->andReturn( $date_format );

		Monkey\Functions\expect( '\get_option' )
			->once()
			->with( 'time_format' )
			->andReturn( $time_format );

		Monkey\Functions\expect( '\get_the_time' )
			->once()
			->with( $date_format, $post )
			->andReturn( $scheduled_date );

		Monkey\Functions\expect( '\get_the_time' )
			->once()
			->with( $time_format, $post )
			->andReturn( $scheduled_time );

		$this->assertSame( $result, $this->instance->change_scheduled_notice_classic_editor( $messages ) );
	}

	/**
	 * Tests the should_change_rewrite_republish_copy function when it should return true for a post.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::should_change_rewrite_republish_copy
	 */
	public function test_should_change_rewrite_republish_copy_post() {
		global $pagenow;
		$pagenow = 'post.php';

		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		$this->permissions_helper->expects( 'is_rewrite_and_republish_copy' )
			->once()
			->with( $post )
			->andReturnTrue();

		$this->assertTrue( $this->instance->should_change_rewrite_republish_copy( $post ) );

		// Clean up after the test.
		unset( $GLOBALS['pagenow'] );
	}

	/**
	 * Tests the should_change_rewrite_republish_copy function when it should return true for a new post.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::should_change_rewrite_republish_copy
	 */
	public function test_should_change_rewrite_republish_copy_new_post() {
		global $pagenow;
		$pagenow = 'post-new.php';

		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		$this->permissions_helper->expects( 'is_rewrite_and_republish_copy' )
			->once()
			->with( $post )
			->andReturnTrue();

		$this->assertTrue( $this->instance->should_change_rewrite_republish_copy( $post ) );

		// Clean up after the test.
		unset( $GLOBALS['pagenow'] );
	}

	/**
	 * Tests the should_change_rewrite_republish_copy function when it should return false,
	 * because the current page is not a post edit screen.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::should_change_rewrite_republish_copy
	 */
	public function test_should_not_change_rewrite_republish_copy_not_post_edit_screen() {
		global $pagenow;
		$pagenow = 'xx.php';

		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		$this->assertFalse( $this->instance->should_change_rewrite_republish_copy( $post ) );

		// Clean up after the test.
		unset( $GLOBALS['pagenow'] );
	}

	/**
	 * Tests the should_change_rewrite_republish_copy function when it should return false,
	 * because the current post is null.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::should_change_rewrite_republish_copy
	 */
	public function test_should_not_change_rewrite_republish_copy_post_is_null() {
		global $pagenow;
		$pagenow = 'post.php';

		$this->assertFalse( $this->instance->should_change_rewrite_republish_copy( null ) );

		// Clean up after the test.
		unset( $GLOBALS['pagenow'] );
	}

	/**
	 * Tests the should_change_rewrite_republish_copy function when it should return false,
	 * because the current post is not a Rewrite & Republish post.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::should_change_rewrite_republish_copy
	 */
	public function test_should_not_change_rewrite_republish_copy_not_republish_copy() {
		global $pagenow;
		$pagenow = 'post.php';

		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		$this->permissions_helper->expects( 'is_rewrite_and_republish_copy' )
			->once()
			->with( $post )
			->andReturnFalse();

		$this->assertFalse( $this->instance->should_change_rewrite_republish_copy( $post ) );

		// Clean up after the test.
		unset( $GLOBALS['pagenow'] );
	}

	/**
	 * Tests the remove_slug_meta_box function when the post is a Rewrite & Republish copy.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::remove_slug_meta_box
	 */
	public function test_remove_slug_meta_box() {
		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		$this->permissions_helper->expects( 'is_rewrite_and_republish_copy' )
			->once()
			->with( $post )
			->andReturnTrue();

		Monkey\Functions\expect( '\remove_meta_box' )
			->once()
			->with( 'slugdiv', $post->post_type, 'normal' );

		$this->instance->remove_slug_meta_box( $post->post_type, $post );
	}

	/**
	 * Tests the remove_slug_meta_box function when the post is not a Rewrite & Republish copy.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::remove_slug_meta_box
	 */
	public function test_remove_slug_meta_box_not_rewrite_and_republish_copy() {
		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		$this->permissions_helper->expects( 'is_rewrite_and_republish_copy' )
			->once()
			->with( $post )
			->andReturnFalse();

		Monkey\Functions\expect( '\remove_meta_box' )
			->never();

		$this->instance->remove_slug_meta_box( $post->post_type, $post );
	}

	/**
	 * Tests the remove_sample_permalink_slug_editor function when the post is a Rewrite & Republish copy.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::remove_sample_permalink_slug_editor
	 */
	public function test_remove_sample_permalink_slug_editor() {
		$return          = 'sample-permalink-html';
		$post_id         = '123';
		$new_title       = null;
		$new_slug        = null;
		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		$this->permissions_helper->expects( 'is_rewrite_and_republish_copy' )
			->once()
			->with( $post )
			->andReturnTrue();

		$this->assertSame( '', $this->instance->remove_sample_permalink_slug_editor( $return, $post_id, $new_title, $new_slug, $post ) );
	}

	/**
	 * Tests the remove_sample_permalink_slug_editor function when the post is not a Rewrite & Republish copy.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Classic_Editor::remove_sample_permalink_slug_editor
	 */
	public function test_remove_sample_permalink_slug_editor_not_rewrite_and_republish_copy() {
		$return          = 'sample-permalink-html';
		$post_id         = '123';
		$new_title       = null;
		$new_slug        = null;
		$post            = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		$this->permissions_helper->expects( 'is_rewrite_and_republish_copy' )
			->once()
			->with( $post )
			->andReturnFalse();

		$this->assertSame( 'sample-permalink-html', $this->instance->remove_sample_permalink_slug_editor( $return, $post_id, $new_title, $new_slug, $post ) );
	}
}
