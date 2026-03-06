Yoast Duplicate Post
=========
Requires at least: 	6.8
Tested up to: 		6.9
Requires PHP: 7.4

Changelog
=========

## 4.6

Release date: 2026-03-17

#### Enhancements

* Improves the style of the _Copy to a new draft_ and _Rewrite & Republish_ actions in the Block Editor.
* Replaces the metabox with a sidebar panel in the Block Editor.
* Improves the compatibility with the Block Editor.

#### Bugfixes

* Fixes a bug where the block editor button were not styled if the admin bar links where not present.
* Fixes a bug where Rewrite & Republish copies could remain orphaned, blocking editors from creating a new Rewrite & Republish copy for the original post.
* Fixes a bug where cloning an attachment did not copy its caption and description as expected. Props to @masteradhoc.
* Fixes a bug where notices would not be appearing in the block editor, throwing console errors, with some locales.
* Fixes a bug where translations where missing in the buttons and the notices in the Block Editor. Props to @petitphp.
* Fixes a bug where using regular expressions in "Do not copy these fields" would not work as expected. Props to @ikuno9233.

#### Other

* Improves security of the Bulk Clone action and the republishing of a copy.
* Adds `duplicate_post_before_republish` and `duplicate_post_after_republish` action hooks fired before and after republishing. Props to @piscis.
* Deprecates the `dp_duplicate_post` and `dp_duplicate_page` hooks and introduces a new unified `duplicate_post_after_duplicated` action hook that replaces them. The new hook includes the post type as a fourth parameter for flexible filtering.
* Sets the minimum supported WordPress version to 6.8.
* Verified compatibility with PHP 8.5
* Sets the WordPress tested up to version to 6.9.
* Drops compatibility with PHP 7.2 and 7.3.
* Fixes the Developer Guide link that was leading to a non-existent page. Props to @masteradhoc.
* Fixes the documentation link to use a shortlink. Props to @masteradhoc.
* Improves how the translations are loaded by relying on the WordPress mechanism for that. Props to @swissspidy.
* Improves discoverability of security policy in Packagist.
* The plugin has no known incompatibilities with PHP 8.3
* Users requiring this package via [WP]Packagist can now use the `composer/installers` v2.
* Drops compatibility with PHP 5.6, 7.0 and 7.1.
* Verified PHP 8.2 compatibility.

## 4.5

Release date: 2022-06-28

#### Enhancements

* Improves the impact of the plugin on the performance of the site by avoiding useless calls on the `gettext` filter.

#### Bugfixes

* Fixes a bug where a section in the Classic Editor's submitbox would be displayed with incorrect margins.

#### Other

* Sets the WordPress tested up to version to 6.0.

### Earlier versions
For the changelog of earlier versions, please refer to [the changelog on yoast.com](https://yoa.st/duplicate-post-changelog).
