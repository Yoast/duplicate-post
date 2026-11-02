<?php

namespace Yoast\WP\Duplicate_Post\Tests\Unit\UI;

use Brain\Monkey\Functions;
use Yoast\WP\Duplicate_Post\Tests\Unit\TestCase;
use Yoast\WP\Duplicate_Post\UI\Newsletter;

/**
 * Test the Newsletter class.
 */
final class Newsletter_Test extends TestCase {

	/**
	 * Sets up the stubs and the submitted form shared by all tests.
	 *
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		$this->stubTranslationFunctions();
		Functions\stubs(
			[
				'wp_nonce_field'   => '',
				'wp_verify_nonce'  => true,
				'sanitize_email'   => null,
				'is_email'         => true,
				'wp_json_encode'   => static function ( $data ) {
					return \json_encode( $data );
				},
			],
		);

		$_POST['newsletter_nonce'] = 'nonce';
		$_POST['EMAIL']            = 'user@example.com';
	}

	/**
	 * Cleans up the submitted form.
	 *
	 * @return void
	 */
	protected function tear_down() {
		unset( $_POST['newsletter_nonce'], $_POST['EMAIL'] );

		parent::tear_down();
	}

	/**
	 * Tests that newsletter_signup_form sends the subscription as JSON.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\UI\Newsletter::newsletter_signup_form
	 * @covers \Yoast\WP\Duplicate_Post\UI\Newsletter::newsletter_handle_form
	 * @covers \Yoast\WP\Duplicate_Post\UI\Newsletter::newsletter_subscribe_to_mailblue
	 *
	 * @return void
	 */
	public function test_newsletter_signup_form_sends_json() {
		$response = [ 'response' => [ 'code' => 201 ] ];

		Functions\expect( 'wp_remote_post' )
			->once()
			->with(
				'https://my.yoast.com/api/Mailing-list/subscribe',
				[
					'method'  => 'POST',
					'headers' => [
						'Content-Type' => 'application/json',
					],
					'body'    => '{"customerDetails":{"email":"user@example.com","firstName":""},"list":"Yoast newsletter"}',
				],
			)
			->andReturn( $response );

		Functions\expect( 'wp_remote_retrieve_response_code' )
			->once()
			->with( $response )
			->andReturn( 201 );

		Newsletter::newsletter_signup_form();
	}

	/**
	 * Tests the feedback shown by newsletter_signup_form for a given response code.
	 *
	 * @covers       \Yoast\WP\Duplicate_Post\UI\Newsletter::newsletter_signup_form
	 * @covers       \Yoast\WP\Duplicate_Post\UI\Newsletter::newsletter_handle_form
	 * @covers       \Yoast\WP\Duplicate_Post\UI\Newsletter::newsletter_subscribe_to_mailblue
	 * @dataProvider data_newsletter_signup_form_response
	 *
	 * @param int|string $response_code   The response code returned by the remote request.
	 * @param string     $expected_status The expected status of the feedback response.
	 *
	 * @return void
	 */
	public function test_newsletter_signup_form_response( $response_code, $expected_status ) {
		Functions\when( 'wp_remote_post' )->justReturn( [] );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( $response_code );

		$this->assertStringContainsString(
			'newsletter-response-' . $expected_status,
			Newsletter::newsletter_signup_form(),
		);
	}

	/**
	 * Data provider for test_newsletter_signup_form_response.
	 *
	 * @return array<string, array<int|string>>
	 */
	public static function data_newsletter_signup_form_response() {
		return [
			'200 OK'                 => [ 200, 'success' ],
			'201 Created'            => [ 201, 'success' ],
			'299 upper bound'        => [ 299, 'success' ],
			'199 below success'      => [ 199, 'error' ],
			'300 redirect'           => [ 300, 'error' ],
			'400 bad request'        => [ 400, 'error' ],
			'500 server error'       => [ 500, 'error' ],
			'Empty code on WP_Error' => [ '', 'error' ],
		];
	}
}
