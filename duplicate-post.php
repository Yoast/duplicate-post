<?php
/**
 * Plugin Name: Duplicate Post
 * Plugin URI: https://duplicate-post.lopo.it/
 * Description: Clone posts and pages.
 * Version: 4.0alpha
 * Author: Enrico Battocchi
 * Author URI: https://lopo.it
 * Text Domain: duplicate-post
 *
 * @package Duplicate Post
 * @since 0.1
 **/

/*
 * Copyright 2009-2012 Enrico Battocchi (email : enrico.battocchi@gmail.com)
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
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

define( 'DUPLICATE_POST_CURRENT_VERSION', '4.0alpha' );

/**
 * Initialises the internationalisation domain
 */
function duplicate_post_load_plugin_textdomain() {
	load_plugin_textdomain( 'duplicate-post', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'duplicate_post_load_plugin_textdomain' );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'duplicate_post_plugin_actions', 10, 4 );

/**
 * Adds 'Settings' link to plugin entry in the Plugins list.
 *
 * @ignore
 * @see 'plugin_action_links_$plugin_file'
 *
 * @param array  $actions An array of plugin action links.
 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
 * @param array  $plugin_data An array of plugin data.
 * @param string $context The plugin context.
 * @return array
 */
function duplicate_post_plugin_actions( $actions, $plugin_file, $plugin_data, $context ) {
	array_unshift(
		$actions,
		'<a href="' . menu_page_url( 'duplicatepost', false ) . '">' . esc_html__( 'Settings', 'default' ) . '</a>'
	);
	return $actions;
}

require_once dirname( __FILE__ ) . '/duplicate-post-common.php';

if ( is_admin() ) {
	include_once dirname( __FILE__ ) . '/duplicate-post-admin.php';
}
