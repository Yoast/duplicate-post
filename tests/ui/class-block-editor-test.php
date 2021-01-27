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
use Yoast\WP\Duplicate_Post\UI\Asset_Manager;
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
	 * Holds the asset manager.
	 *
	 * @var Asset_Manager
	 */
	protected $asset_manager;

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
		$this->asset_manager      = Mockery::mock( Asset_Manager::class );

		$this->instance = Mockery::mock(
			Block_Editor::class,
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

		$this->assertNotFalse(
			\has_action(
				'elementor/editor/after_enqueue_styles',
				[
					$this->instance,
					'hide_elementor_post_status',
				]
			),
			'Does not have expected elementor/editor/after_enqueue_styles action'
		);

		$this->assertNotFalse(
			\has_action(
				'elementor/editor/before_enqueue_scripts',
				[
					$this->instance,
					'enqueue_elementor_script',
				]
			),
			'Does not have expected elementor/editor/before_enqueue_scripts action'
		);

		$this->assertNotFalse(
			\has_action(
				'admin_enqueue_scripts',
				[
					$this->instance,
					'should_previously_used_keyword_assessment_run',
				]
			),
			'Does not have expected admin_enqueue_scripts action'
		);

		$this->assertNotFalse(
			\has_action(
				'enqueue_block_editor_assets',
				[
					$this->instance,
					'enqueue_block_editor_scripts',
				]
			),
			'Does not have expected enqueue_block_editor_assets action'
		);

		$this->assertNotFalse(
			\has_filter(
				'wpseo_link_suggestions_indexables',
				[
					$this->instance,
					'remove_original_from_wpseo_link_suggestions',
				]
			),
			'Does not have expected wpseo_link_suggestions_indexables filter'
		);
	}

	/**
	 * Tests the should_previously_used_keyword_assessment_run function.
	 *
	 * @covers       \Yoast\WP\Duplicate_Post\UI\Block_Editor::should_previously_used_keyword_assessment_run
	 * @dataProvider should_previously_used_keyword_assessment_run_provider
	 *
	 * @param mixed $original Input value.
	 * @param mixed $expected Expected output.
	 */
	public function test_should_previously_used_keyword_assessment_run( $original, $expected ) {
		$post = Mockery::mock( \WP_Post::class );

		$this->permissions_helper
			->expects( 'is_edit_post_screen' )
			->andReturn( $original['is_edit_post_screen'] );

		$this->permissions_helper
			->allows( 'is_new_post_screen' )
			->andReturn( $original['is_new_post_screen'] );

		Monkey\Functions\when( '\get_post' )
			->justReturn( $post );

		$this->permissions_helper
			->allows( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturn( $original['is_rewrite_and_republish_copy'] );

		$this->permissions_helper
			->allows( 'has_rewrite_and_republish_copy' )
			->with( $post )
			->andReturn( $original['has_rewrite_and_republish_copy'] );

		$this->instance->should_previously_used_keyword_assessment_run();
		$this->assertSame( $expected, \has_filter( 'wpseo_previously_used_keyword_active' ) );
	}

	/**
	 * Data provider for test_is_edit_post_screen.
	 *
	 * @return array The test parameters.
	 */
	public function should_previously_used_keyword_assessment_run_provider() {
		return [
			[
				'original' => [
					'is_edit_post_screen'            => true,
					'is_new_post_screen'             => false,
					'is_rewrite_and_republish_copy'  => true,
					'has_rewrite_and_republish_copy' => false,
				],
				'expected' => true,
			],
			[
				'original' => [
					'is_edit_post_screen'            => false,
					'is_new_post_screen'             => true,
					'is_rewrite_and_republish_copy'  => true,
					'has_rewrite_and_republish_copy' => false,
				],
				'expected' => true,
			],
			[
				'original' => [
					'is_edit_post_screen'            => true,
					'is_new_post_screen'             => false,
					'is_rewrite_and_republish_copy'  => false,
					'has_rewrite_and_republish_copy' => true,
				],
				'expected' => true,
			],
			[
				'original' => [
					'is_edit_post_screen'            => false,
					'is_new_post_screen'             => false,
					'is_rewrite_and_republish_copy'  => false,
					'has_rewrite_and_republish_copy' => false,
				],
				'expected' => false,
			],
			[
				'original' => [
					'is_edit_post_screen'            => true,
					'is_new_post_screen'             => false,
					'is_rewrite_and_republish_copy'  => false,
					'has_rewrite_and_republish_copy' => false,
				],
				'expected' => false,
			],
			[
				'original' => [
					'is_edit_post_screen'            => false,
					'is_new_post_screen'             => true,
					'is_rewrite_and_republish_copy'  => false,
					'has_rewrite_and_republish_copy' => false,
				],
				'expected' => false,
			],
		];
	}

	/**
	 * Tests the enqueueing of the scripts on a post that is not a Rewrite &
	 * Republish copy.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::enqueue_block_editor_scripts
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_enqueue_block_editor_scripts() {
		$utils                      = Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$post                       = Mockery::mock( \WP_Post::class );
		$new_draft_link             = 'http://fakeu.rl/new_draft';
		$rewrite_and_republish_link = 'http://fakeu.rl/rewrite_and_republish';
		$rewriting                  = 0;
		$original_edit_url          = 'http://fakeu.rl/original';

		$show_links = [
			'new_draft'         => '1',
			'clone'             => '1',
			'rewrite_republish' => '1',
		];

		$show_links_in = [
			'row'         => '1',
			'adminbar'    => '1',
			'submitbox'   => '1',
			'bulkactions' => '1',
		];

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->twice()
			->andReturnFalse();

		$this->instance
			->expects( 'get_new_draft_permalink' )
			->andReturn( $new_draft_link );

		$this->instance
			->expects( 'get_rewrite_republish_permalink' )
			->andReturn( $rewrite_and_republish_link );

		$utils
			->expects( 'get_option' )
			->andReturn( $show_links );

		$utils
			->expects( 'get_option' )
			->andReturn( $show_links_in );

		$this->instance
			->expects( 'get_original_post_edit_url' )
			->andReturn( $original_edit_url );

		$edit_js_object = [
			'newDraftLink'            => $new_draft_link,
			'rewriteAndRepublishLink' => $rewrite_and_republish_link,
			'showLinks'               => $show_links,
			'showLinksIn'             => $show_links_in,
			'rewriting'               => $rewriting,
			'originalEditURL'         => $original_edit_url,
		];

		$this->asset_manager
			->expects( 'enqueue_edit_script' )
			->with( $edit_js_object );

		$this->instance
			->expects( 'get_check_permalink' )
			->never();

		$this->instance->enqueue_block_editor_scripts();
	}

	/**
	 * Tests the enqueueing of the scripts on a post that is a Rewrite &
	 * Republish copy.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::enqueue_block_editor_scripts
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_new_draft_permalink_rewrite_and_republish() {
		$utils                      = Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$post                       = Mockery::mock( \WP_Post::class );
		$new_draft_link             = 'http://fakeu.rl/new_draft';
		$rewrite_and_republish_link = 'http://fakeu.rl/rewrite_and_republish';
		$rewriting                  = 1;
		$original_edit_url          = 'http://fakeu.rl/original';
		$check_link                 = 'http://fakeu.rl/check';

		$show_links = [
			'new_draft'         => '1',
			'clone'             => '1',
			'rewrite_republish' => '1',
		];

		$show_links_in = [
			'row'         => '1',
			'adminbar'    => '1',
			'submitbox'   => '1',
			'bulkactions' => '1',
		];

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->twice()
			->andReturnTrue();

		$this->instance
			->expects( 'get_new_draft_permalink' )
			->andReturn( $new_draft_link );

		$this->instance
			->expects( 'get_rewrite_republish_permalink' )
			->andReturn( $rewrite_and_republish_link );

		$utils
			->expects( 'get_option' )
			->andReturn( $show_links );

		$utils
			->expects( 'get_option' )
			->andReturn( $show_links_in );

		$this->instance
			->expects( 'get_original_post_edit_url' )
			->andReturn( $original_edit_url );

		$edit_js_object = [
			'newDraftLink'            => $new_draft_link,
			'rewriteAndRepublishLink' => $rewrite_and_republish_link,
			'showLinks'               => $show_links,
			'showLinksIn'             => $show_links_in,
			'rewriting'               => $rewriting,
			'originalEditURL'         => $original_edit_url,
		];

		$this->asset_manager
			->expects( 'enqueue_edit_script' )
			->with( $edit_js_object );

		$this->instance
			->expects( 'get_check_permalink' )
			->andReturn( $check_link );

		$string_js_object = [
			'checkLink' => $check_link,
		];

		$this->asset_manager
			->expects( 'enqueue_strings_script' )
			->with( $string_js_object );

		$this->instance->enqueue_block_editor_scripts();
	}

	/**
	 * Tests the enqueueing of the scripts when no post is displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::enqueue_block_editor_scripts
	 */
	public function test_get_new_draft_permalink_no_post() {
		Monkey\Functions\expect( '\get_post' )
			->andReturnNull();

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->never();

		$this->instance
			->expects( 'get_new_draft_permalink' )
			->never();

		$this->instance
			->expects( 'get_rewrite_republish_permalink' )
			->never();

		$this->instance
			->expects( 'get_original_post_edit_url' )
			->never();

		$this->asset_manager
			->expects( 'enqueue_edit_script' )
			->never();

		$this->asset_manager
			->expects( 'enqueue_strings_script' )
			->never();

		$this->instance
			->expects( 'get_check_permalink' )
			->never();

		$this->instance->enqueue_block_editor_scripts();
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

		$this->permissions_helper
			->expects( 'should_links_be_displayed' )
			->with( $post )
			->andReturnTrue();

		$this->link_builder
			->expects( 'build_new_draft_link' )
			->with( $post )
			->andReturn( $url );

		$this->assertSame( $url, $this->instance->get_new_draft_permalink() );
	}

	/**
	 * Tests the get_new_draft_permalink function when a link should not be
	 * displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::get_new_draft_permalink
	 */
	public function test_get_new_draft_permalink_unsuccessful() {
		$post = Mockery::mock( \WP_Post::class );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'should_links_be_displayed' )
			->with( $post )
			->andReturnFalse();

		$this->link_builder
			->expects( 'build_new_draft_link' )
			->with( $post )
			->never();

		$this->assertSame( '', $this->instance->get_new_draft_permalink() );
	}

	/**
	 * Tests the get_rewrite_republish_permalink function when a link is
	 * returned.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::get_rewrite_republish_permalink
	 */
	public function test_get_rewrite_republish_permalink_successful() {
		$post = Mockery::mock( \WP_Post::class );
		$url  = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_rewrite&post=201&_wpnonce=5e7abf68c9';

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturnFalse();

		$this->permissions_helper
			->expects( 'has_rewrite_and_republish_copy' )
			->with( $post )
			->andReturnFalse();

		$this->permissions_helper
			->expects( 'should_links_be_displayed' )
			->with( $post )
			->andReturnTrue();

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->with( $post )
			->andReturn( $url );

		$this->assertSame( $url, $this->instance->get_rewrite_republish_permalink() );
	}

	/**
	 * Tests the get_rewrite_republish_permalink function when the post is a Rewrite & Republish copy.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::get_rewrite_republish_permalink
	 */
	public function test_get_rewrite_republish_permalink_unsuccessful_is_rewrite_and_republish() {
		$post = Mockery::mock( \WP_Post::class );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturnTrue();

		$this->permissions_helper
			->expects( 'has_rewrite_and_republish_copy' )
			->with( $post )
			->never();

		$this->permissions_helper
			->expects( 'should_links_be_displayed' )
			->with( $post )
			->never();

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->with( $post )
			->never();

		$this->assertSame( '', $this->instance->get_rewrite_republish_permalink() );
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_show_link' ) === 0 );
	}

	/**
	 * Tests the get_rewrite_republish_permalink function when the post has a Rewrite & Republish copy.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::get_rewrite_republish_permalink
	 */
	public function test_get_rewrite_republish_permalink_unsuccessful_has_a_rewrite_and_republish() {
		$post = Mockery::mock( \WP_Post::class );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturnFalse();

		$this->permissions_helper
			->expects( 'has_rewrite_and_republish_copy' )
			->with( $post )
			->andReturnTrue();

		$this->permissions_helper
			->expects( 'should_links_be_displayed' )
			->with( $post )
			->never();

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->with( $post )
			->never();

		$this->assertSame( '', $this->instance->get_rewrite_republish_permalink() );
	}

	/**
	 * Tests the get_rewrite_republish_permalink function when the links should not be displayed.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::get_rewrite_republish_permalink
	 */
	public function test_get_rewrite_republish_permalink_unsuccessful_links_should_not_be_displayed() {
		$post              = Mockery::mock( \WP_Post::class );
		$post->post_status = 'publish';

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturnFalse();

		$this->permissions_helper
			->expects( 'has_rewrite_and_republish_copy' )
			->with( $post )
			->andReturnFalse();

		$this->permissions_helper
			->expects( 'should_links_be_displayed' )
			->with( $post )
			->andReturnFalse();

		$this->link_builder
			->expects( 'build_rewrite_and_republish_link' )
			->with( $post )
			->never();

		$this->assertSame( '', $this->instance->get_rewrite_republish_permalink() );
	}

	/**
	 * Tests the get_check_permalink function when a link is returned.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::get_check_permalink
	 */
	public function test_get_check_permalink_successful() {
		$post = Mockery::mock( \WP_Post::class );
		$url  = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_check_changes&post=201&_wpnonce=5e7abf68c9';

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturnTrue();

		$this->link_builder
			->expects( 'build_check_link' )
			->with( $post )
			->andReturn( $url );

		$this->assertSame( $url, $this->instance->get_check_permalink() );
	}

	/**
	 * Tests the get_check_permalink function when the post is not intended for
	 * Rewrite & Republish.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::get_check_permalink
	 */
	public function test_get_check_permalink_not_rewrite_and_republish() {
		$post = Mockery::mock( \WP_Post::class );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->andReturnFalse();

		$this->link_builder
			->expects( 'build_check_link' )
			->with( $post )
			->never();

		$this->assertSame( '', $this->instance->get_check_permalink() );
	}

	/**
	 * Tests the successful get_original_post_edit_url.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::get_original_post_edit_url
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_original_post_edit_url_successful() {
		$utils       = Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$post        = Mockery::mock( \WP_Post::class );
		$post->ID    = 128;
		$original_id = 64;
		$nonce       = '12345678';

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$utils
			->expects( 'get_original_post_id' )
			->with( $post->ID )
			->andReturn( $original_id );

		Monkey\Functions\expect( '\admin_url' )
			->andReturnUsing(
				function ( $string ) {
					return 'http://basic.wordpress.test/wp-admin/' . $string;
				}
			);

		Monkey\Functions\expect( '\wp_create_nonce' )
			->with( 'dp-republish' )
			->andReturn( $nonce );

		Monkey\Functions\expect( '\add_query_arg' )
			->andReturnUsing(
				function ( $array, $string ) {
					foreach ( $array as $key => $value ) {
						$string .= '&' . $key . '=' . $value;
					}

					return $string;
				}
			);

		$this->assertSame(
			'http://basic.wordpress.test/wp-admin/post.php?action=edit&post=64&dprepublished=1&dpcopy=128&dpnonce=12345678',
			$this->instance->get_original_post_edit_url()
		);
	}

	/**
	 * Tests the get_original_post_edit_url when there is no post.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::get_original_post_edit_url
	 */
	public function test_get_original_post_edit_url_no_post() {
		Monkey\Functions\expect( '\get_post' )
			->andReturnNull();

		$this->assertSame(
			'',
			$this->instance->get_original_post_edit_url()
		);
	}

	/**
	 * Tests the get_original_post_edit_url function when there is no original.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::get_original_post_edit_url
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_original_post_edit_url_no_original() {
		$utils       = Mockery::mock( 'alias:\Yoast\WP\Duplicate_Post\Utils' );
		$post        = Mockery::mock( \WP_Post::class );
		$post->ID    = 128;
		$original_id = '';

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$utils
			->expects( 'get_original_post_id' )
			->with( $post->ID )
			->andReturn( $original_id );

		$this->assertSame(
			'',
			$this->instance->get_original_post_edit_url()
		);
	}

	/**
	 * Tests the hiding of the Elementor post status field.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::hide_elementor_post_status
	 */
	public function test_hide_elementor_post_status() {
		$post = Mockery::mock( \WP_Post::class );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->once()
			->andReturnTrue();

		Monkey\Functions\expect( '\wp_add_inline_style' )
			->with(
				'elementor-editor',
				'.elementor-control-post_status { display: none !important; }'
			);

		$this->instance->hide_elementor_post_status();
	}

	/**
	 * Tests the hiding of the Elementor post status field doesn't trigger on normal posts.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::hide_elementor_post_status
	 */
	public function test_dont_remove_elementor_post_status() {
		$post = Mockery::mock( \WP_Post::class );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		$this->permissions_helper
			->expects( 'is_rewrite_and_republish_copy' )
			->with( $post )
			->once()
			->andReturnFalse();

		Monkey\Functions\expect( '\wp_add_inline_style' )
			->never();

		$this->instance->hide_elementor_post_status();
	}
}
