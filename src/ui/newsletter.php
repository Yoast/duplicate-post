<?php

namespace Yoast\WP\Duplicate_Post\UI;

class Newsletter {
	public function register_hooks()
	{
		add_action('admin_init', [$this, 'newsletter_signup_form']);
	}

	/**
	 * Handles subscription request.
	 *
	 * @param $url API endpoint.
	 * @param $email Subscriber email.
	 *
	 * @return array Feedback response.
	 */
	private static function newsletter_subscribe_to_mailblue($url, $email) {
		$response = wp_remote_post(
			$url,
			[
				'method'      => 'POST',
				'body'        => [
					'customerDetails' => [
						'email' => $email,
					],
					'list'			 => 'Newsletter staging test'
				],
			]
		);

		$wp_remote_retrieve_response_code = wp_remote_retrieve_response_code($response);

		if($wp_remote_retrieve_response_code < 201 || $wp_remote_retrieve_response_code >= 300) {
			return [
				'status'	=> 'error',
				'message'	=> esc_html__('Something went wrong. Please try again later.', 'duplicate-post'),
			];
		}

		return [
			'status'	=> 'success',
			'message'	=> esc_html__('You have successfully subscribed to the newsletter. Please check your inbox to confirm.', 'duplicate-post'),
		];
	}

	/**
	 * Renders the newsletter signup form.
	 *
	 * @return string The HTML of the newsletter signup form (escaped).
	 */
	public static function newsletter_signup_form() {

		$newsletter_form_response = self::newsletter_handle_form();

		$response_html = '';
		if(is_array($newsletter_form_response)) {
			$response_message = $newsletter_form_response['message'];

			$response_html = '
			<div id="newsletter-responses" class="clear">
				<div class="newsletter-response" id="newsletter-response">' . $response_message . '</div>
			</div>
			';
		}

		$copy = sprintf(
		/* translators: 1: Yoast */
			esc_html__(
				'If you want to stay up to date about all the exciting developments around Duplicate Post, subscribe to the %1$s newsletter!',
				'duplicate-post'
			),
			'Yoast'
		);

		$email_label = esc_html__( 'Email Address', 'duplicate-post' );

		$html = '
		<!-- Begin Newsletter Signup Form -->
		<div>
		<form  method="post" id="newsletter-subscribe-form" name="newsletter-subscribe-form" class="validate" novalidate>
		<p>' . $copy . '</p>
		<div class="newsletter-field-group" style="margin-top: 8px;">
			<label for="newsletter-email" style="display: block; margin-bottom: 8px;"><strong>' . $email_label . '</strong></label>
			<input type="email" value="" name="EMAIL" class="required email" id="newsletter-email">
			<input type="submit" value="' . esc_attr__( 'Subscribe', 'duplicate-post' ) . '" name="subscribe" id="newsletter-subscribe" class="button">
		</div>
		' . $response_html . '
			<div class="screen-reader-text" aria-hidden="true"><input type="text" name="b_ffa93edfe21752c921f860358_972f1c9122" tabindex="-1" value=""></div>
		</form>
		</div>
		<!--End Newsletter Signup Form-->
		';



		return $html;
	}

	/**
	 * Handles and validates Newsletter form.
	 *
	 * @returns null|array
	 */
	private static function newsletter_handle_form() {
		if (! isset($_POST['EMAIL']) || $_POST['EMAIL'] === '') {
			return null;
		}

		if (! is_email($_POST['EMAIL'])) {
			return null;
		}

		return self::newsletter_subscribe_to_mailblue('https://staging-platform-my.yoast.com/api/Mailing-list/subscribe', $_POST['EMAIL']);
	}
}
