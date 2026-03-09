<?php

namespace Yoast\WP\Duplicate_Post\Tests\WP;

use Yoast\WP\Duplicate_Post\Post_Duplicator;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Class Post_Duplicator.
 *
 * @coversDefaultClass \Yoast\WP\Duplicate_Post\Post_Duplicator
 */
final class Post_Duplicator_Test extends TestCase {

	/**
	 * Instance of the Post_Duplicator class.
	 *
	 * @var Post_Duplicator
	 */
	private $instance;

	/**
	 * Setting up the instance of Post_Duplicator.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->instance = new Post_Duplicator();
	}

	/**
	 * Tests whether the admin page is generated correctly.
	 *
	 * @covers ::create_duplicate
	 * @covers ::get_default_options
	 * @covers ::generate_copy_title
	 * @covers ::generate_copy_status
	 * @covers ::generate_copy_author
	 * @covers ::set_modified
	 *
	 * @return void
	 */
	public function test_create_duplicate() {

		$post = $this->factory->post->create_and_get();

		$id = $this->instance->create_duplicate( $post, [ 'copy_date' => true ] );

		$this->assertIsInt( $id );
	}
}
