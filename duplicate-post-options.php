<?php

namespace Yoast\WP\Duplicate_Post;

use Yoast\WP\Duplicate_Post\Admin\Options;
use Yoast\WP\Duplicate_Post\Admin\Options_Form_Generator;
use Yoast\WP\Duplicate_Post\Admin\Options_Inputs;
use Yoast\WP\Duplicate_Post\Admin\Options_Page;
use Yoast\WP\Duplicate_Post\UI\Asset_Manager;

if ( ! \defined( 'ABSPATH' ) ) {
	exit();
}

$duplicate_post_options_page = new Options_Page(
	new Options(),
	new Options_Form_Generator( new Options_Inputs() ),
	new Asset_Manager()
);

$duplicate_post_options_page->register_hooks();
