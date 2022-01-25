<?php
/**
 * Duplicate Post plugin.
 *
 * @package Yoast\WP\Duplicate_Post
 * @since   0.1
 *
 * @wordpress-plugin
 * Plugin Name: Yoast Duplicate Post
 * Plugin URI:  https://yoast.com/wordpress/plugins/duplicate-post/
 * Description: The go-to tool for cloning posts and pages, including the powerful Rewrite & Republish feature.
 * Version:     4.4
 * Author:      Enrico Battocchi & Team Yoast
 * Author URI:  https://yoast.com
 * Text Domain: duplicate-post
 *
 * Copyright 2020 Yoast BV (email : info@yoast.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

use Yoast\WP\Duplicate_Post\Duplicate_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! defined( 'DUPLICATE_POST_FILE' ) ) {
	define( 'DUPLICATE_POST_FILE', __FILE__ );
}

if ( ! defined( 'DUPLICATE_POST_PATH' ) ) {
	define( 'DUPLICATE_POST_PATH', plugin_dir_path( __FILE__ ) );
}

define( 'DUPLICATE_POST_CURRENT_VERSION', '4.4' );

$duplicate_post_autoload_file = DUPLICATE_POST_PATH . 'vendor/autoload.php';

if ( is_readable( $duplicate_post_autoload_file ) ) {
	require $duplicate_post_autoload_file;
}

if ( class_exists( Duplicate_Post::class ) ) {
	// Initialize the main autoloaded class.
	add_action( 'plugins_loaded', '__duplicate_post_main' );
}

/**
 * Loads the Duplicate Post main class.
 *
 * {@internal Function name change would be BC-break.}
 *
 * @phpcs:disable PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
 * @phpcs:disable WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 */
function __duplicate_post_main() {
	new Duplicate_Post();
}
// phpcs:enable

/**
 * Initialises the internationalisation domain.
 */
function duplicate_post_load_plugin_textdomain() {
	load_plugin_textdomain( 'duplicate-post', false, basename( __DIR__ ) . '/languages/' );
}
add_action( 'plugins_loaded', 'duplicate_post_load_plugin_textdomain' );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'duplicate_post_plugin_actions', 10 );

/**
 * Adds 'Settings' link to plugin entry in the Plugins list.
 *
 * @see 'plugin_action_links_$plugin_file'
 *
 * @param array $actions An array of plugin action links.
 * @return array
 */
function duplicate_post_plugin_actions( $actions ) {
	$settings_action = [
		'settings' => sprintf(
			'<a href="%1$s" %2$s>%3$s</a>',
			menu_page_url( 'duplicatepost', false ),
			'aria-label="' . __( 'Settings for Duplicate Post', 'duplicate-post' ) . '"',
			esc_html__( 'Settings', 'duplicate-post' )
		),
	];

	$actions = ( $settings_action + $actions );
	return $actions;
}

require_once DUPLICATE_POST_PATH . 'common-functions.php';

if ( is_admin() ) {
	include_once DUPLICATE_POST_PATH . 'admin-functions.php';
}
