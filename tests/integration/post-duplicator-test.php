<?php

namespace Yoast\WP\Duplicate_Post\Tests\Integration;

/**
 * Yoast Duplicate Post plugin test file.
 *
 * @package Yoast\WP\Duplicate_Post\Tests
 */

use Yoast\WPTestUtils\WPIntegration\TestCase;
use Yoast\WP\Duplicate_Post\Post_Duplicator;

/**
 * Class Post_Duplicator.
 *
 * @coversDefaultClass \Yoast\WP\Duplicate_Post\Post_Duplicator
 */
class Post_Duplicator_Test extends TestCase {

	/**
	 * Instance of the Post_Duplicator class.
	 *
	 * @var Post_Duplicator
	 */
	private $instance;

	/**
	 * Setting up the instance of Post_Duplicator.
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
	 */
	public function test_create_duplicate() {

		$post = $this->factory->post->create_and_get();

		$id = $this->instance->create_duplicate( $post );

		$this->assertTrue( \is_int( $id ) );
	}
}
