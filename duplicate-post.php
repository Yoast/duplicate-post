<?php
/*
 Plugin Name: Duplicate Post
 Plugin URI: http://lopo.it/duplicate-post-plugin/
 Description: Clone posts and pages.
 Version: 2.0.2
 Author: Enrico Battocchi
 Author URI: http://lopo.it
 Text Domain: duplicate-post
 */

/*  Copyright 2009-2011	Enrico Battocchi  (email : enrico.battocchi@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// i18n plugin domain
define('DUPLICATE_POST_I18N_DOMAIN', 'duplicate-post');

/**
 * Initialise the internationalisation domain
 */
load_plugin_textdomain(DUPLICATE_POST_I18N_DOMAIN,
			'wp-content/plugins/duplicate-post/languages','duplicate-post/languages');

require_once (dirname(__FILE__).'/duplicate-post-common.php');

if (is_admin()){
	require_once (dirname(__FILE__).'/duplicate-post-admin.php');
}
?>
