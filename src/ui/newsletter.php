<?php

namespace Yoast\WP\Duplicate_Post\UI;

class Newsletter {
	public function register_hooks()
	{
		add_action('admin_init', [$this, 'newsletter_signup_form']);
	}

	/**
	 * Handles subscription request
	 *
	 * @param $url API endpoint.
	 * @param $email Subscriber email.
	 *
	 * @return void.
	 */
	public static function newsletter_subscribe_to_mailblue($url, $email) {
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

		print_r(wp_remote_retrieve_body($response));
	}

	/**
	 * Renders the newsletter signup form.
	 *
	 * @return string The HTML of the newsletter signup form (escaped).
	 */
	public static function newsletter_signup_form() {

		self::newsletter_handle_form();


		$copy = sprintf(
		/* translators: 1: Yoast */
			esc_html__(
				'If you want to stay up to date about all the exciting developments around Duplicate Post, subscribe to the %1$s newsletter!',
				'duplicate-post'
			),
			'Yoast'
		);

		$email_label = esc_html__( 'Email Address', 'duplicate-post' );

		//3. form output
		$html = '
		<!-- Begin Newsletter Signup Form -->
		<div>
		<form  method="post" id="newsletter-subscribe-form" name="newsletter-subscribe-form" class="validate" novalidate>
			<p>' . $copy . '</p>
		<div class="newsletter-field-group" style="margin-top: 8px;">
			<label for="newsletter-EMAIL" style="display: block; margin-bottom: 8px;"><strong>' . $email_label . '</strong></label>
			<input type="email" value="" name="EMAIL" class="required email" id="newsletter-EMAIL">
			<input type="submit" value="' . esc_attr__( 'Subscribe', 'duplicate-post' ) . '" name="subscribe" id="newsletter-subscribe" class="button">
		</div>
			<div id="newsletter-responses" class="clear">
				<div class="newsletter-response" id="newsletter-error-response" style="display: none;"></div>
				<div class="newsletter-response" id="newsletter-success-response" style="display: none;"></div>
			</div>    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
			<div class="screen-reader-text" aria-hidden="true"><input type="text" name="b_ffa93edfe21752c921f860358_972f1c9122" tabindex="-1" value=""></div>
		</form>
		</div>
		<!--End Newsletter Signup Form-->
		';

		return $html;
	}

	/**
	 * Handles and validates Newsletter form.
	 */
	private static function newsletter_handle_form() {
		if (! isset($_POST['EMAIL']) || $_POST['EMAIL'] === '') {
			return;
		}

		if (! is_email($_POST['EMAIL'])) {
			return;
		}

		self::newsletter_subscribe_to_mailblue('https://staging-platform-my.yoast.com/api/Mailing-list/subscribe', $_POST['EMAIL']);
	}
}
