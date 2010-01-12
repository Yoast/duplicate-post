=== Duplicate Post ===
Contributors: lopo
Donate link: http://www.lopo.it/duplicate-post-plugin/
Tags: duplicate, post, copy
Requires at least: 2.7
Tested up to: 2.9
Stable tag: 1.0

Creates a copy of a post.

== Description ==

Allows to create a draft copy of a post (or page) in two ways:

1. In 'Edit Posts'/'Edit Pages', you can click on 'Duplicate' link;
2. While editing a post/page, you can click on 'Copy to a new draft' above "Cancel"/"Move to trash".

Both ways lead to the edit page for the new draft: change what you want, click on 'Publish' and you're done.

Duplicate post is natively in English, and is shipped with Italian, Japanese, French and Spanish language files. Feel free to contact me if you want to add your language.

If you're a plugin developer, I suggest to read the section made just for you under [Other Notes](other_notes/), to ensure compatibility between your plugin(s) and mine!

This plugin used to be tested on at least WP 2.6.5. From version 1.0 onwards, it uses some APIs first introduced with WP 2.7 to achieve better integration with the new WordPress interface.


Credit must be given to the (great) [Post Template](http://post-templates.vincentprat.info) plugin by Vincent Prat: I made this by hacking his work to get something more focused to a sporadic use, without the need to create and manage templates just to make simple copies of some posts every now and then. If my plugin doesn't fits your needs (and even if it does) check Vincent's.

Thanks for all the suggestions and bug reports, mainly:

* Franz, for giving me some hints on where to search to fix the bug with WP 2.8.1;
* Ben ter Stal, for WPMU compatibility and some fixes;
* [Naoko McCracken](http://blog.detlog.org), for helping me with i18n and for the Japanese language files
* [Simon Wheatley](http://www.simonwheatley.co.uk/), for his suggestions, especially about adding actions for other developers to use.

An example of use: I started this for a small movie theater website which I'm building. Every Friday there's a new movie showing with a new timetable, and thus a new post: but sometimes a movie stays for more than a week, so I need to copy the last post and change only the dates, leaving movie title, director's and actors' names etc. unchanged.
The website is http://www.kino-desse.org and the cinema is located in Livorno, Italy.

== Installation ==

1. Upload `duplicate-post` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== For plugin developers ==

From version 1.0 onwards, thanks to [Simon Wheatley](http://www.simonwheatley.co.uk/)'s suggestion, Duplicate Post adds two actions (*dp_duplicate_post* and *dp_duplicate_page*) which can be used by other developers if their plugins store extra data for posts in non-standard WP tables.
Since Duplicate Post knows only of standard WP tables, it can't copy other data relevant to the post which is being copied if this information is stored elsewhere. So, if you're a plugin developer which acts this way, and you want to ensure compatibility with Duplicate Post, you can hook your functions to those actions to make sure that they will be called when a post (or page) is cloned.

It's very simple. Just write your function that copies post metadata to a new row of your table:

`function myplugin_copy_post($new_post_id, $old_post_object){
/* your code */
}`


Then hook the function to the action:

`add_action("dp_duplicate_post", "myplugin_copy_post", $mypriority, 2);`


Please refer to the [Plugin API](http://codex.wordpress.org/Plugin_API) for every information about the subject.

== Contribute ==

If you find this useful and you if you want to contribute, there are three ways:

   1. You can [write me](http://www.lopo.it/contatti/) and submit your bug reports, suggestions and requests for features;
   2. If you want to translate it to your language (there are just a few lines of text), you can [contact me](http://www.lopo.it/contatti/) and I’ll send you the .pot catalogue; your translation could be featured in next releases;
   3. Using the plugin is free, but if you want you can send me some bucks with PayPal [here](http://www.lopo.it/duplicate-post-plugin/)


== Changelog ==

= 1.0 =
* Better integration with WP 2.7+ interface
* Added actions for plugins which store post metadata in self-managed tables
* Added French and Spanish language files
* Dropped WP 2.6.5 compatibility

= 0.6.1 =
* Tested WP 2.9 compatibility

= 0.6 =
* Fix for WP 2.8.1
* WPMU compatibility
* Internationalization (Italian and Japanese language files shipped)

= 0.5 =
* Fix for post-meta
* WP2.7 compatibility 

= 0.4 =
* Support for new WP post revision feature
