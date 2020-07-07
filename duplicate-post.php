<?php
/**
 * Plugin Name: Yoast Duplicate Post
 * Plugin URI: https://yoast.com/wordpress/plugins/duplicate-post/
 * Description: Clone posts and pages.
 * Version: 3.2.5
 * Author: Enrico Battocchi & Team Yoast
 * Author URI: https://yoast.com
 * Text Domain: duplicate-post
 */

/*  Copyright 2020 Yoast BV (email : info@yoast.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Version of the plugin
define('DUPLICATE_POST_CURRENT_VERSION', '3.2.5' );

/**
 * Initialise the internationalisation domain
 */
function duplicate_post_load_plugin_textdomain() {
    load_plugin_textdomain( 'duplicate-post', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'duplicate_post_load_plugin_textdomain' );

add_filter( "plugin_action_links_" . plugin_basename(__FILE__), "duplicate_post_plugin_actions", 10);

/**
 * Adds 'Settings' link to plugin entry in the Plugins list.
 *
 * @ignore
 * @see 'plugin_action_links_$plugin_file'
 *
 * @param array $actions An array of plugin action links.
 * @return array
 */
function duplicate_post_plugin_actions( $actions ) {
	$settings_action = array(
		'settings' => sprintf(
			'<a href="%1$s" %2$s>%3$s</a>',
			menu_page_url( 'duplicatepost', false ),
			'aria-label="' . __( 'Settings for Duplicate Post', 'duplicate-post' ) . '"',
			esc_html__( 'Settings', 'default' )
		),
	);

	$actions = $settings_action + $actions;
	return $actions;
}

require_once (dirname(__FILE__).'/duplicate-post-common.php');

if (is_admin()){
	require_once (dirname(__FILE__).'/duplicate-post-admin.php');
}
