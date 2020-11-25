<?php
/**
 * Duplicate Post test file.
 *
 * @package Duplicate_Post\Tests
 */

namespace Yoast\WP\Duplicate_Post\Tests\UI;

use Brain\Monkey;
use Mockery;
use Yoast\WP\Duplicate_Post\Tests\TestCase;
use \Yoast\WP\Duplicate_Post\UI\Link_Builder;

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
	public function setUp() {
		parent::setUp();

		$this->instance = new Link_Builder();
	}

	/**
	 * Tests the build_link function.
	 *
	 * @covers       \Yoast\WP\Duplicate_Post\UI\Link_Builder::build_link
	 */
	public function test_build_link() {
		$post        = Mockery::mock( \WP_Post::class );
		$post->ID    = 123;
		$context     = 'display';
		$action_name = 'duplicate_post_clone';

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post );

		Monkey\Functions\expect( '\admin_url' )
			->andReturnUsing(
				function ( $string ) {
					return 'http://basic.wordpress.test/wp-admin/' . $string;
				}
			);

		Monkey\Functions\expect( '\wp_nonce_url' )
			->andReturnFirstArg();

		$this->assertEquals(
			'http://basic.wordpress.test/wp-admin/admin.php?action=duplicate_post_clone&amp;post=123',
			$this->instance->build_link( $post, $context, $action_name )
		);
		$this->assertTrue( Monkey\Filters\applied( 'duplicate_post_get_clone_post_link' ) > 0 );
	}
}
