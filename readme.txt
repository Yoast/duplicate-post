=== Duplicate Post ===
Contributors: lopo
Donate link: http://www.lopo.it/duplicate-post-plugin/
Tags: duplicate, post, copy
Requires at least: 2.7
Tested up to: 3.1.1
Stable tag: 1.1.2

Creates a copy of a post.

== Description ==

Allows to create a draft copy of a post (or page) in two ways:
1. In 'Edit Posts'/'Edit Pages', you can click on 'Duplicate' link;
2. While editing a post/page, you can click on 'Copy to a new draft' above "Cancel"/"Move to trash".

Both ways lead to the edit page for the new draft: change what you want, click on 'Publish' and you're done.

In the Options page it is now possible to choose:
* if the original post/page date must be copied too
* which custom fields must not be copied
* a prefix to place before the title of the cloned post/page
* the minimum user level to clone posts or pages

Duplicate post is natively in English, but it's shipped with translations in 11 other languages (though some are incomplete). Now there is a [Launchpad translation project](https://translations.launchpad.net/duplicate-post/) available to help translating this plugin: feel free to contribute (you can also send me an e-mail using the form on my website).

If you're a plugin developer, I suggest to read the section made just for you under "Other Notes", to ensure compatibility between your plugin(s) and mine!

The plugin has been tested against versions 2.7 -> 3.1.1. It should be compatible with the Custom Post Type and Custom Taxonomies features of WP 3.0+. It's not yet been tested with the multiblog feature active (but it used to work with WPMU).

Thanks for all the suggestions, bug reports, translations and donations: Franz, Ben ter Stal, [Naoko McCracken](http://blog.detlog.org), [Simon Wheatley](http://www.simonwheatley.co.uk/), [Magnus Anemo](http://www.anemo.se/en), Michelle Drumm, [TVbytheNumbers.com](http://www.TVbytheNumbers.com), Richard Vencu, [el_libre](http://www.catmidia.cat/), Antoine Jouve, Sebastian, Yaron, Hiroshi Tagawa, Adam Skiba, Bartosz Kaszubowski, Szymon Sieciński, Braiam Peguero, Jonay, tam, my friends Livia, Alessandra, Ada and anybody else that I may have forgotten (sorry!)

Credit must be given to the (great) [Post Template](http://post-templates.vincentprat.info) plugin by Vincent Prat: I made this by hacking his work to get something more focused to a sporadic use, without the need to create and manage templates just to make simple copies of some posts every now and then. If my plugin doesn't fits your needs (and even if it does) check Vincent's.

An example of use: I started this for a small movie theater website which I'm building. Every Friday there's a new movie showing with a new timetable, and thus a new post: but sometimes a movie stays for more than a week, so I need to copy the last post and change only the dates, leaving movie title, director's and actors' names etc. unchanged.
The website is http://www.kino-desse.org and the cinema is located in Livorno, Italy.

== Installation ==

1. Upload `duplicate-post` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Options -> Duplicate Post and customize behaviour as needed

== For plugin developers ==

From version 1.0 onwards, thanks to [Simon Wheatley](http://www.simonwheatley.co.uk/)'s suggestion, Duplicate Post adds two actions (*dp_duplicate_post* and *dp_duplicate_page*) which can be used by other developers if their plugins store extra data for posts in non-standard WP tables.
Since Duplicate Post knows only of standard WP tables, it can't copy other data relevant to the post which is being copied if this information is stored elsewhere. So, if you're a plugin developer which acts this way, and you want to ensure compatibility with Duplicate Post, you can hook your functions to those actions to make sure that they will be called when a post (or page) is cloned.

It's very simple. Just write your function that copies post metadata to a new row of your table:
`function myplugin_copy_post($new_post_id, $old_post_object){
/* your code */
}`

Then hook the function to the action:
`add_action( "dp_duplicate_post", "myplugin_copy_post", $priority, 2);`

Please refer to the [Plugin API](http://codex.wordpress.org/Plugin_API) for every information about the subject.

== Contribute ==

If you find this useful and you if you want to contribute, there are three ways:

   1. You can [write me](http://www.lopo.it/contatti/) and submit your bug reports, suggestions and requests for features;
   2. If you want to translate it to your language (there are just a few lines of text), you can use the [Launchpad translation project](https://translations.launchpad.net/duplicate-post/), or [contact me](http://www.lopo.it/contatti/) and I’ll send you the .pot catalogue; your translation could be featured in next releases;
   3. Using the plugin is free, but if you want you can send me some bucks with PayPal [here](http://www.lopo.it/duplicate-post-plugin/)

== Upgrade Notice ==

= 1.1.1 =
Some users have experienced a fatal error when upgrading to v1.1: this may fix it, if it's caused by a plugin conflict.

= 1.1 =
New features and customization, WP 3.0 compatibility: you should upgrade if you want to copy Custom Posts with Custom Taxonomies.

== Changelog ==

= 1.1.2 =
* WP 3.1.1 compatibility (still not tested against multiblog feature, so beware)
* Added complete Polish language files

= 1.1.1 =
* Plugin split in two files for faster opening in Plugins list page
* fix conflicts with a few other plugins
* Added Dutch language files

= 1.1 =
* WP 3.0 compatibility (not tested against multiblog feature, so beware)
* Option page: minimum user level, title prefix, fields not to be copied, copy post/page date also
* Added German, Swedish, Romanian, Hebrew, Catalan (incomplete) and Polish (incomplete) language files

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

