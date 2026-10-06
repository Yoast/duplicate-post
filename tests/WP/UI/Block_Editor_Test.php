<?php

namespace Yoast\WP\Duplicate_Post\Tests\WP\UI;

use WP_REST_Request;
use WP_REST_Response;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\UI\Asset_Manager;
use Yoast\WP\Duplicate_Post\UI\Block_Editor;
use Yoast\WP\Duplicate_Post\UI\Link_Builder;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Integration tests for the Block_Editor class.
 */
final class Block_Editor_Test extends TestCase {

	/**
	 * Tests removing the permalink fields from a Rewrite & Republish response.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::remove_rewrite_republish_permalink
	 *
	 * @return void
	 */
	public function test_remove_rewrite_republish_permalink() {
		$copy = self::factory()->post->create_and_get();
		\update_post_meta( $copy->ID, '_dp_is_rewrite_republish_copy', 1 );

		$block_editor = new Block_Editor( new Link_Builder(), new Permissions_Helper(), new Asset_Manager() );
		$request      = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $copy->ID );
		$request->set_param( 'context', 'edit' );
		$response = new WP_REST_Response(
			[
				'permalink_template' => 'https://example.com/%postname%/',
				'generated_slug'     => $copy->post_name,
				'title'              => [ 'raw' => $copy->post_title ],
			],
		);

		$filtered_response = $block_editor->remove_rewrite_republish_permalink( $response, $copy, $request );
		$data              = $filtered_response->get_data();

		$this->assertArrayNotHasKey( 'permalink_template', $data );
		$this->assertArrayNotHasKey( 'generated_slug', $data );
		$this->assertSame( [ 'raw' => $copy->post_title ], $data['title'] );
	}

	/**
	 * Tests that normal REST responses retain their permalink fields.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Block_Editor::remove_rewrite_republish_permalink
	 *
	 * @return void
	 */
	public function test_remove_rewrite_republish_permalink_does_not_change_normal_post() {
		$post         = self::factory()->post->create_and_get();
		$block_editor = new Block_Editor( new Link_Builder(), new Permissions_Helper(), new Asset_Manager() );
		$request      = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post->ID );
		$request->set_param( 'context', 'edit' );
		$response = new WP_REST_Response(
			[
				'permalink_template' => 'https://example.com/%postname%/',
				'generated_slug'     => $post->post_name,
			],
		);

		$data = $block_editor->remove_rewrite_republish_permalink( $response, $post, $request )->get_data();

		$this->assertArrayHasKey( 'permalink_template', $data );
		$this->assertArrayHasKey( 'generated_slug', $data );
	}
}
