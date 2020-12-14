<?php
/**
 * Options page
 *
 * @package Duplicate Post
 * @since   2.0
 */

namespace Yoast\WP\Duplicate_Post;

use Yoast\WP\Duplicate_Post\Admin\Options;
use Yoast\WP\Duplicate_Post\Admin\Options_Form_Generator;
use Yoast\WP\Duplicate_Post\Admin\Options_Inputs;
use Yoast\WP\Duplicate_Post\Admin\Options_Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$options_page = new Options_Page(
	new Options(),
	new Options_Form_Generator( new Options_Inputs() )
);

$options_page->register_hooks();
