<?php

namespace Yoast\WP\Duplicate_Post\Tests\UI;

use Brain\Monkey;
use Mockery;
use WP_Post;
use Yoast\WP\Duplicate_Post\Tests\TestCase;
use Yoast\WP\Duplicate_Post\UI\Link_Builder;

/**
 * Test the Link_Builder class.
 */
class Link_Builder_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Link_Builder
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	protected function set_up() {
		parent::set_up();

		$this->instance = Mockery::mock( Link_Builder::class )->makePartial();
	}

	/**
	 * Tests the build_rewrite_and_republish_link function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Link_Builder::build_rewrite_and_republish_link
	 */
	public function test_build_rewrite_and_republish_link() {
		$post    = Mockery::mock( WP_Post::class );
		$context = 'display';
		$url     = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_rewrite&amp;post=123';

		$this->instance
			->expects( 'build_link' )
			->with( $post, $context, 'duplicate_post_rewrite' )
			->andReturn( $url );

		$this->assertSame(
			$url,
			$this->instance->build_rewrite_and_republish_link( $post, $context )
		);
	}

	/**
	 * Tests the build_clone_link function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Link_Builder::build_clone_link
	 */
	public function test_build_clone_link() {
		$post    = Mockery::mock( WP_Post::class );
		$context = 'display';
		$url     = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_clone&amp;post=123';

		$this->instance
			->expects( 'build_link' )
			->with( $post, $context, 'duplicate_post_clone' )
			->andReturn( $url );

		$this->assertSame(
			$url,
			$this->instance->build_clone_link( $post, $context )
		);
	}

	/**
	 * Tests the build_new_draft_link function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Link_Builder::build_new_draft_link
	 */
	public function test_build_new_draft_link() {
		$post    = Mockery::mock( WP_Post::class );
		$context = 'display';
		$url     = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_new_draft&amp;post=123';

		$this->instance
			->expects( 'build_link' )
			->with( $post, $context, 'duplicate_post_new_draft' )
			->andReturn( $url );

		$this->assertSame(
			$url,
			$this->instance->build_new_draft_link( $post, $context )
		);
	}

	/**
	 * Tests the build_check_link function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Link_Builder::build_check_link
	 */
	public function test_build_check_link() {
		$post    = Mockery::mock( WP_Post::class );
		$context = 'display';
		$url     = 'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_check_changes&amp;post=123';

		$this->instance
			->expects( 'build_link' )
			->with( $post, $context, 'duplicate_post_check_changes' )
			->andReturn( $url );

		$this->assertSame(
			$url,
			$this->instance->build_check_link( $post, $context )
		);
	}

	/**
	 * Tests the build_link function.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Link_Builder::build_link
	 */
	public function test_build_link() {
		$post        = Mockery::mock( WP_Post::class );
		$post->ID    = 123;
		$context     = 'display';
		$action_name = 'duplicate_post_clone';

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		Monkey\Functions\expect( '\admin_url' )
			->andReturnUsing(
				static function ( $query_string ) {
					return 'http://basic.wordpress.test/wp-admin/' . $query_string;
				}
			);

		Monkey\Functions\expect( '\wp_nonce_url' )
			->andReturnFirstArg();

		$this->assertSame(
			'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_clone&amp;post=123',
			$this->instance->build_link( $post, $context, $action_name )
		);
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_get_clone_post_link' ) > 0 );
	}

	/**
	 * Tests the build_link function when context is not 'display'.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Link_Builder::build_link
	 */
	public function test_build_link_not_display() {
		$post        = Mockery::mock( WP_Post::class );
		$post->ID    = 123;
		$context     = '';
		$action_name = 'duplicate_post_clone';

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		Monkey\Functions\expect( '\admin_url' )
			->andReturnUsing(
				static function ( $query_string ) {
					return 'http://basic.wordpress.test/wp-admin/' . $query_string;
				}
			);

		Monkey\Functions\expect( '\wp_nonce_url' )
			->andReturnFirstArg();

		$this->assertSame(
			'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_clone&post=123',
			$this->instance->build_link( $post, $context, $action_name )
		);
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_get_clone_post_link' ) > 0 );
	}

	/**
	 * Tests the build_link function with no valid post.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Link_Builder::build_link
	 */
	public function test_build_link_no_post() {
		$post        = null;
		$context     = 'display';
		$action_name = 'duplicate_post_clone';

		Monkey\Functions\expect( '\get_post' )
			->andReturnNull();

		Monkey\Functions\expect( '\admin_url' )
			->never();

		Monkey\Functions\expect( '\wp_nonce_url' )
			->never();

		$this->assertSame(
			'',
			$this->instance->build_link( $post, $context, $action_name )
		);
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_get_clone_post_link' ) === 0 );
	}
}
