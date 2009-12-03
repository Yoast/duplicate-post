=== Plugin Name ===
Contributors: lopo
Donate link: http://www.lopo.it/duplicate-post-plugin/
Tags: duplicate, post, copy
Requires at least: 2.7
Tested up to: 2.9
Stable tag: 0.6.1

Creates a copy of a post.

== Description ==

Allows to create a draft copy of a post (or page) in two ways:
1. In 'Edit Posts'/'Edit Pages', you can click on 'Duplicate' link;
2. While editing a post/page, you can click on 'Copy to a new draft' above "Move to trash".

Both ways lead to the edit page of the new draft: change what you want, click on 'Publish' and you're done.

This plugin used to be tested on at least WP 2.6.5. From version 1.0 onwards, it uses some APIs first introduced with WP 2.7: I couldn't test it on that version actually, but the bulk of the code has remained the same, so I think there shouldn't be any problem (but feel free to tell me if that's wrong).


Credit must be given to the (great) [Post Template](http://post-templates.vincentprat.info) plugin by Vincent Prat: I made this by hacking his work to get something more focused to a sporadic use, without the need to create and manage templates just to make simple copies of some posts every now and then. If my plugin doesn't fits your needs (and even if it does) check Vincent's.

Thanks for all the suggestions and bug reports, mainly:
- Franz, for giving me some hints on where to search to fix the bug with WP 2.8.1;
- Ben ter Stal, for WPMU compatibility and some fixes;
- Naoko McCracken, for helping me with i18n (now the plugin ships Japanese, Italian and French language files: feel free to send me your translation in other languages)

An example of use: I started this for a small movie theater website which I'm building. Every friday there's a new movie showing with a new timetable, and thus a new post: but sometimes a movie stays for more than a week, so I need to copy the last post and change only the dates, leaving movie title, director's and actors' names etc. unchanged.
The website is http://www.kino-desse.org and the cinema is located in Livorno, Italy.

== Installation ==

1. Upload `duplicate-post` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

