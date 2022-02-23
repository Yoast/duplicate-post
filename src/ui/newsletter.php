<?php

namespace Yoast\WP\Duplicate_Post\UI;

/**
 * Newsletter class.
 */
class Newsletter {

	/**
	 * Renders the newsletter signup form.
	 *
	 * @return string The HTML of the newsletter signup form (escaped).
	 */
	public static function newsletter_signup_form() {

		$newsletter_form_response = self::newsletter_handle_form();


		$copy = \sprintf(
		/* translators: 1: Yoast */
			\esc_html__(
				'If you want to stay up to date about all the exciting developments around Duplicate Post, subscribe to the %1$s newsletter!',
				'duplicate-post'
			),
			'Yoast'
		);

		$email_label = \esc_html__( 'Email address', 'duplicate-post' );

		$copy_privacy_policy = \sprintf(
			/* translators: %1$s and %2$s are replaced by opening and closing anchor tags. */
			\esc_html__(
				'Yoast respects your privacy. Read %1$sour privacy policy%2$s on how we handle your personal information.',
				'duplicate-post'
			),
			'<a href="https://yoa.st/4jf" target="_blank">',
			'</a>'
		);

		$response_html = '';
		if ( \is_array( $newsletter_form_response ) ) {
			$response_status  = $newsletter_form_response['status'];
			$response_message = $newsletter_form_response['message'];

			$response_html = '<div class="newsletter-response-' . $response_status . ' clear" id="newsletter-response" style="margin-top: 6px;">' . $response_message . '</div>';
		}

		$html = '
		<!-- Begin Newsletter Signup Form -->
		<form method="post" id="newsletter-subscribe-form" name="newsletter-subscribe-form" novalidate>
		' . \wp_nonce_field( 'newsletter', 'newsletter_nonce' ) . '
		<p>' . $copy . '</p>
		<div class="newsletter-field-group" style="display: flex; flex-direction: column">
			<label for="newsletter-email" style="margin: 0 0 4px 0;"><strong>' . $email_label . '</strong></label>
			<div>
				<input type="email" value="" name="EMAIL" class="required email" id="newsletter-email" style="margin-right: 4px;">
				<input type="submit" value="' . \esc_attr__( 'Subscribe', 'duplicate-post' ) . '" name="subscribe" id="newsletter-subscribe" class="button">
			</div>
			<p style="font-size: 10px;">' . $copy_privacy_policy . '</p>
		</div>
		' . $response_html . '
		</form>
		<!--End Newsletter Signup Form-->
		';

		return $html;
	}

	/**
	 * Handles and validates Newsletter form.
	 *
	 * @return array|null
	 */
	private static function newsletter_handle_form() {

		//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Already sanitized.
		if ( isset( $_POST['newsletter_nonce'] ) && ! \wp_verify_nonce( \wp_unslash( $_POST['newsletter_nonce'] ), 'newsletter' ) ) {
			return [
				'status'    => 'error',
				'message'   => \esc_html__( 'Something went wrong. Please try again later.', 'duplicate-post' ),
			];
		}

		$email = null;
		if ( isset( $_POST['EMAIL'] ) ) {
			$email = \sanitize_email( \wp_unslash( $_POST['EMAIL'] ) );
		}

		if ( $email === null ) {
			return null;
		}

		if ( ! \is_email( $email ) ) {
			return [
				'status'    => 'error',
				'message'   => \esc_html__( 'Please enter valid e-mail address.', 'duplicate-post' ),
			];
		}

		return self::newsletter_subscribe_to_mailblue( $email );
	}

	/**
	 * Handles subscription request and provides feedback response.
	 *
	 * @param string $email Subscriber email.
	 *
	 * @return array Feedback response.
	 */
	private static function newsletter_subscribe_to_mailblue( $email ) {
		$response = \wp_remote_post(
			'https://my.yoast.com/api/Mailing-list/subscribe',
			[
				'method'      => 'POST',
				'body'        => [
					'customerDetails' => [
						'email'     => $email,
						'firstName' => '',
					],
					'list'            => 'Yoast newsletter',
				],
			]
		);

		$wp_remote_retrieve_response_code = \wp_remote_retrieve_response_code( $response );

		if ( $wp_remote_retrieve_response_code <= 200 || $wp_remote_retrieve_response_code >= 300 ) {
			return [
				'status'    => 'error',
				'message'   => \esc_html__( 'Something went wrong. Please try again later.', 'duplicate-post' ),
			];
		}

		return [
			'status'    => 'success',
			'message'   => \esc_html__( 'You have successfully subscribed to the newsletter. Please check your inbox.', 'duplicate-post' ),
		];
	}
}
