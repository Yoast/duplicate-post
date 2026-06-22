Yoast Duplicate Post
=========
Requires at least: 	6.8
Tested up to: 		7.0
Requires PHP: 7.4

Changelog
=========

## 4.7

Release date: 2026-06-22

#### Enhancements

* Adds a link to the existing duplicate in the Rewrite & Republish admin notices, so you can open it directly instead of hunting for it in the post list. Props to [@johnbillion](https://github.com/johnbillion).

#### Bugfixes

* Fixes a bug where a published post could be overwritten by a user without permission to edit it, when that user scheduled a Rewrite & Republish copy of the post for future publication.
* Fixes a bug where a PHP deprecation notice appeared in the block editor, when a user opened a Rewrite & Republish copy of a post they were not allowed to edit.
* Fixes a bug where the _Copy to a new draft_ and _Rewrite & Republish_ links broke the layout of the Classic Editor Publish meta box on WordPress 7.0.

#### Other

* Improves the security of the welcome notice dismissal by requiring a valid nonce and the `manage_options` capability.
* Improves the security of the scheduled republish notice in the Classic editor by escaping the post title and permalink before output.
* Sets the _WordPress tested up to_ version to 7.0.

## 4.6

Release date: 2026-03-09

Introduces smoother post duplication, more reliable rewrite workflows, and better compatibility across languages and configurations. [Read more here!](https://yoa.st/563)

#### Enhancements

* Improves the style of the _Copy to a new draft_ and _Rewrite & Republish_ actions in the Block Editor.
* Replaces the metabox with a sidebar panel in the Block Editor.
* Improves the compatibility with the Block Editor.

#### Bugfixes

* Fixes a bug where the block editor button were not styled if the admin bar links where not present.
* Fixes a bug where Rewrite & Republish copies could remain orphaned, blocking editors from creating a new Rewrite & Republish copy for the original post.
* Fixes a bug where cloning an attachment did not copy its caption and description as expected. Props to @masteradhoc.
* Fixes a bug where notices were not appearing in the block editor, throwing console errors, with some locales.
* Fixes a bug where translations where missing in the buttons and the notices in the Block Editor. Props to @petitphp.
* Fixes a bug where using regular expressions in "Do not copy these fields" were not working as expected. Props to @ikuno9233.

#### Other

* Improves security of the Bulk Clone action and the republishing of a copy.
* Adds `duplicate_post_before_republish` and `duplicate_post_after_republish` action hooks fired before and after republishing. Props to @piscis.
* Deprecates the `dp_duplicate_post` and `dp_duplicate_page` hooks and introduces a new unified `duplicate_post_after_duplicated` action hook that replaces them. The new hook includes the post type as a fourth parameter for flexible filtering.
* Sets the minimum supported WordPress version to 6.8.
* Verified compatibility with PHP up to version 8.5.
* Sets the WordPress tested up to version to 6.9.
* Drops compatibility with PHP < 7.4.
* Fixes the Developer Guide link that was leading to a non-existent page. Props to @masteradhoc.
* Fixes the documentation link to use a shortlink. Props to @masteradhoc.
* Improves how the translations are loaded by relying on the WordPress mechanism for that. Props to @swissspidy.
* Improves discoverability of security policy in Packagist.
* Users requiring this package via [WP]Packagist can now use the `composer/installers` v2.

### Earlier versions
For the changelog of earlier versions, please refer to [the changelog on yoast.com](https://yoa.st/duplicate-post-changelog).
