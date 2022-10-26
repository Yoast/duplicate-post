=== Yoast Duplicate Post ===
Contributors: 		yoast, lopo
Donate link: 		https://yoast.com/wordpress/plugins/duplicate-post/
Tags: 				duplicate post, copy, clone
Requires at least: 	5.9
Tested up to: 		6.1
Stable tag: 		4.5
Requires PHP:		5.6.20
License: 			GPLv2 or later
License URI: 		http://www.gnu.org/licenses/gpl-2.0.html

The go-to tool for cloning posts and pages, including the powerful Rewrite & Republish feature.

== Description ==

This plugin allows users to clone posts of any type, or copy them to new drafts for further editing.

How it works:

1. In 'Edit Posts'/'Edit Pages', you can click on 'Clone' link below the post/page title: this will immediately create a copy and return to the list.

2. In 'Edit Posts'/'Edit Pages', you can select one or more items, then choose 'Clone' in the 'Bulk Actions' dropdown to copy them all at once.

3. In 'Edit Posts'/'Edit Pages', you can click on 'New Draft' link below the post/page title.

4. On the post edit screen, you can click on 'Copy to a new draft' above "Cancel"/"Move to trash" or in the admin bar.

5. While viewing a post as a logged in user, you can click on 'Copy to a new draft' in the admin bar.

3, 4 and 5 will lead to the edit page for the new draft: change what you want, click on 'Publish' and you're done.

There is also a **template tag**, so you can put it in your templates and clone your posts/pages from the front-end. Clicking on the link will lead you to the edit page for the new draft, just like the admin bar link.

Duplicate Post has many useful settings to customize its behavior and restrict its use to certain roles or post types. Check out the extensive documentation [on yoast.com](https://yoast.com/wordpress/plugins/duplicate-post/) and our [developer docs](https://developer.yoast.com/duplicate-post/).

== Installation ==

Use WordPress' Add New Plugin feature, searching "Duplicate Post", or download the archive and:

1. Unzip the archive on your computer
2. Upload `duplicate-post` directory to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings -> Duplicate Post and customize behaviour as needed

== Frequently Asked Questions ==

= The plugin doesn't work, why? =

First, check your version of WordPress: the plugin is not supposed to work on old versions anymore. Make sure also to upgrade to the last version of the plugin!

Then try to deactivate and re-activate it, some user have reported that this fixes some problems.

Pay also attention to the "Permissions" tab in the Settings: make sure the plugin is enabled for the desired roles and post types.

If it still doesn't work, maybe there is some kind of conflict with other plugins: feel free [to write in the forum](https://wordpress.org/support/plugin/duplicate-post) and we'll try to discover a solution (it will be *really* helpful if you try to deactivate all your other plugins one by one to see which one conflicts with mine... But do it only if you know what you're doing, I will not be responsible of any problem you may experience).

= The plugin is not translated in my language! =

From version 3.0 the plugin's translations are managed by the WordPress.org platform and the plugin is shipped without language files, so first of all update translations under Dashboard->Updates.

If Duplicate Post is still in English, or if there are some untranslated strings, you can [help translating to your language](https://translate.wordpress.org/projects/wp-plugins/duplicate-post): you only need a WordPress.org account.

== Screenshots ==

1. Classic editor.
2. Block editor.
3. Post list.
4. Admin bar menu.
5. Bulk actions.
6. The options page.

== Changelog ==

= 4.5 =
Release Date: June 28th, 2022

Enhancements:

* Improves the impact of the plugin on the performance of the site by avoiding useless calls on the `gettext` filter.

Bugfixes:

* Fixes a bug where a section in the Classic Editor's submitbox would be displayed with incorrect margins.

Other:

* Sets the WordPress tested up to version to 6.0.

= 4.4 =
Release Date: January 25th, 2022

Enhancements:

* Converts the upgrade notice into a welcome notice for first-time users.

Bugfixes:

* Fixes a bug where HTML tags in a Custom HTML block would be removed when republishing a scheduled Rewrite & Republish copy.
* Fixes a bug where the button style would be broken in the Classic Editor.
* Fixes a bug where a fatal error would be triggered in the Widgets page in combination with some themes or plugins.

Other:

* Sets the WordPress tested up to version to 5.9.

= Earlier versions =
For the changelog of earlier versions, please refer to [the changelog on yoast.com](https://yoa.st/duplicate-post-changelog).

== Contribute ==

If you find this useful and if you want to contribute, there are two ways:

   1. Submit your bug reports, suggestions and requests for features on [GitHub](https://github.com/Yoast/duplicate-post);
   2. If you want to translate it to your language (there are just a few lines of text), you can use the [translation project](https://translate.wordpress.org/projects/wp-plugins/duplicate-post);
