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
	public function newsletter_subscribe_to_mailblue($url, $email) {
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
		$copy = sprintf(
		/* translators: 1: Yoast */
			esc_html__(
				'If you want to stay up to date about all the exciting developments around Duplicate Post, subscribe to the %1$s newsletter!',
				'duplicate-post'
			),
			'Yoast'
		);

		$email_label = esc_html__( 'Email Address', 'duplicate-post' );

		//https://staging-platform-my.yoast.com/api/Mailing-list/subscribe

		$html = '
<!-- Begin Mailchimp Signup Form -->
<div id="mc_embed_signup">
<form method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
	<div id="mc_embed_signup_scroll">
	' . $copy . '
<div class="mc-field-group" style="margin-top: 8px;">
	<label for="mce-EMAIL">' . $email_label . '</label>
	<input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL">
	<input type="submit" value="' . esc_attr__( 'Subscribe', 'duplicate-post' ) . '" name="subscribe" id="mc-embedded-subscribe" class="button">
</div>
	<div id="mce-responses" class="clear">
		<div class="response" id="mce-error-response" style="display:none"></div>
		<div class="response" id="mce-success-response" style="display:none"></div>
	</div>    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
	<div class="screen-reader-text" aria-hidden="true"><input type="text" name="b_ffa93edfe21752c921f860358_972f1c9122" tabindex="-1" value=""></div>
	</div>
</form>
</div>
<!--End mc_embed_signup-->
';

		if(isset($_POST['EMAIL']) && $_POST['EMAIL'] !== '') {
			call_user_func(__NAMESPACE__ . 'Newsletter::newsletter_subscribe_to_mailblue','https://staging-platform-my.yoast.com/api/Mailing-list/subscribe', $_POST['EMAIL']);
		}

		return $html;
	}
}
