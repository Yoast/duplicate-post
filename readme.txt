=== Yoast Duplicate Post ===
Contributors: 		yoast, lopo
Donate link: 		https://yoast.com/wordpress/plugins/duplicate-post/
Tags: 				duplicate post, copy, clone
Requires at least: 	6.8
Tested up to: 		6.9
Stable tag: 		4.5
Requires PHP:		7.4
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

Duplicate Post has many useful settings to customize its behavior and restrict its use to certain roles or post types. Check out the extensive documentation on [yoast.com](https://yoast.com/wordpress/plugins/duplicate-post/) and our [developer docs](https://developer.yoast.com/duplicate-post/overview/).

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

= 4.6 =

Release date: 2026-03-03

#### Enhancements

* Improves the compatibility with the Block Editor.
* Improves the style of the _Copy to a new draft_ and _Rewrite & Republish_ actions in the Block Editor.
* Replaces the metabox with a sidebar panel in the Block Editor.

#### Bugfixes

* Fixes a bug where cloning an attachment did not copy its caption as expected. Props to @masteradhoc.
* Fixes a bug where cloning an attachment did not copy its description as expected.
* Fixes a bug where notices would not be appearing in the block editor, throwing console errors, with some locales.
* Fixes a bug where Rewrite & Republish copies could remain orphaned, blocking editors from creating a new Rewrite & Republish copy for the original post.
* Fixes a bug where translations where missing in the buttons and the notices in the Block Editor. Props to @petitphp.
* Minor security improvement

#### Other

* Adds `duplicate_post_before_republish` and `duplicate_post_after_republish` action hooks fired before and after republishing. Props to @piscis.
* Deprecates the `dp_duplicate_post` and `dp_duplicate_page` hooks and introduces a new unified `duplicate_post_after_duplicated` action hook that replaces them. The new hook includes the post type as a fourth parameter for flexible filtering.
* Drops compatibility with PHP 5.6, 7.0 and 7.1.
* Drops compatibility with PHP 7.2 and 7.3.
* Improves discoverability of security policy in Packagist.
* Improves how the translations are loaded by relying on the WordPress mechanism for that. Props to @swissspidy.
* Improves security of the Bulk Clone action and the republishing of a copy.
* Sets the minimum supported WordPress version to 6.8.
* Sets the WordPress tested up to version to 6.9.
* The plugin has no known incompatibilities with PHP 8.3
* Users requiring this package via [WP]Packagist can now use the `composer/installers` v2.
* Verified compatibility with PHP 8.5
* Verified PHP 8.2 compatibility.

= 4.5 =

Release date: 2022-06-28

#### Enhancements

* Improves the impact of the plugin on the performance of the site by avoiding useless calls on the `gettext` filter.

#### Bugfixes

* Fixes a bug where a section in the Classic Editor's submitbox would be displayed with incorrect margins.

#### Other

* Sets the WordPress tested up to version to 6.0.

= Earlier versions =
For the changelog of earlier versions, please refer to [the changelog on yoast.com](https://yoa.st/duplicate-post-changelog).

== Contribute ==

If you find this useful and if you want to contribute, there are two ways:

   1. Submit your bug reports, suggestions and requests for features on [GitHub](https://github.com/Yoast/duplicate-post);
   2. If you want to translate it to your language (there are just a few lines of text), you can use the [translation project](https://translate.wordpress.org/projects/wp-plugins/duplicate-post);
