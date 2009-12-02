=== Plugin Name ===
Contributors: lopo
Tags: duplicate, post
Requires at least: 2.6.5
Tested up to: 2.9
Stable tag: 0.6.1

Create a copy of a post.

== Description ==

Allows to create a draft copy of a current post in two ways:
1. In 'Manage Posts', you can click on 'Duplicate' link;
2. While editing a post, you can click on 'Click here to create a copy of this post' at the bottom of the editing page.

Both ways lead to the edit page of the new draft: change what you want, click on 'Publish' and you're done.

If you want to clone a page, you can use the first method abov.

Credit must be given to the (great) [Post Template](http://post-templates.vincentprat.info) plugin by Vincent Prat: I made this by hacking his work to get something more focused to a sporadic use, without the need to create and manage templates just to make simple copies of some posts every now and then. If my plugin doesn't fits your needs (and even if it does) check Vincent's.

Thank you to all the suggestions and bug reports, mainly:
- Franz, for giving me some hints on where to search to fix the bug with WP 2.8.1;
- Ben ter Stal, for WPMU compatibility and some fixes;
- Naoko McCracken, for helping me with i18n (now the plugin ships Japanese and Italian language files - I haven't actually tested them yet because I've been rushing to fix the above bug)

An example of use: I started this for a small movie theater website which I'm building. Every friday there's a new movie showing with a new timetable, and thus a new post: but sometimes a movie stays for more than a week, so I need to copy the last post and change only the dates, leaving movie title, director's and actors' names etc. unchanged.
The website is http://www.kino-desse.org and the cinema is located in Livorno, Italy.

== Installation ==

1. Upload `duplicate-post` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

