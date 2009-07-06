=== Twitter Widget Pro ===
Contributors: aaroncampbell
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=paypal%40xavisys%2ecom&item_name=Twitter%20Widget%20Pro&no_shipping=0&no_note=1&tax=0&currency_code=USD&lc=US&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: twitter, widget, feed
Requires at least: 2.7
Tested up to: 2.8
Stable tag: 1.4.3

A widget that properly handles twitter feeds, including parsing @username, #hashtags, and URLs into links. Requires PHP5.

== Description ==

A widget that properly handles twitter feeds, including @username, #hashtag, and
link parsing.  It supports displaying profiles images, and even lets you control
whether to display the time and date of a tweet or how log ago it happened
(about 5 hours ago, etc).  Requires PHP5.

You may also be interested in WordPress tips and tricks at <a href="http://wpinformer.com">WordPress Informer</a> or gerneral <a href="http://webdevnews.net">Web Developer News</a>

== Installation ==

1. Verify that you have PHP5, which is required for this plugin.
1. Upload the whole `twitter-widget-pro` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Can I use more than one instance of this widget? =

Yes, Twitter Widget Pro employs the multi-widget pattern, which allows you to not only have more than one instance of this widget on your site, but even allows more than one instance of this widget in a single sidebar.

= Can I follow more than one feed? =

Absolutely, each instance of the widget can have different settings and track different feeds.

= Why can't I display a friends feed anymore? =

Aparently the database queries required to display the friends feed was causing twitter to crash, so they removed it.  Unfortunately, this is outside my control.

== Screenshots ==

1. To user the widget, go to Appearance -> Widgets and choose to "Add" the "Twitter Feed" widget.
2. Each widget has settings that need to be set, so the next step is to click "edit" on the newly added widget and adjust all the settings.  When you're done click "Save Changes"
3. This is what the widget looks like in the default theme with no added styles.
4. By using some (X)HTML in the title element and adding a few styles and a background image, you could make it look like this.

== Changelog ==

= 1.4.3 =
* Added the text domain to some translatable strings that were missing it
* Added the Spanish translation thanks to Rafael Poveda <RaveN>!! (Really....thanks for being the first translator for this)

= 1.4.2 =
* Thanks to RaveN and Dries Arnold for pointing out that the "about # ____ ago" phrases weren't translatable

= 1.4.1 =
* Fixed some translatable strings
* Fixed readme text

= 1.4.0 =
* Make translatable
* Include POT file
* Remove JS submitted for for stats and use HTTP class instead

= 1.3.7 =
* Added some spans with classes to make styling to meta data easier

= 1.3.6 =
* Fixes issue with linking URLs containing a ~
* Removed some debugging stuff

= 1.3.5 =
* #Hashtags are now linked to twitter search

= 1.3.4 =
* Added convert_chars filter to the tweet text to properly handle special characters
* Fixed "in reply to" text which stopped working when Twitter changed their API

= 1.3.3 =
* Some configs still couldn't turn off the link to Twitter Widget Pro page

= 1.3.2 =
* Fixed problem with link to Twitter Widget Pro page not turning off

= 1.3.1 =
* Added error handling after wp_remote_request call
* Added link to Twitter Widget Pro page and option to turn it off per widget

= 1.3.0 =
* Updated to use HTTP class and phased out Snoopy
* No longer relies on user having a caching solution in place.  Caches for 5 minutes using blog options
* Allow HTML in title and error message if user can

= 1.2.2 =
* Fixed minor issue with Zend JSON Decoder
* Added an option for Twitter timeout.  2 seconds wasn't enough for some people

= 1.2.1 =
* Fixed some minor errors in the collection code
* Added the admin options page (how did that get missed?!?)

= 1.2.0 =
* Removed friends feed option, twitter removed this functionality
* Added an option to set your own message to display when twitter is down
* Added optional anonymous statistics collection

= 1.1.4 =
* Added an error if there was a problem connecting to Twitter.
* Added some text if there are no tweets.

= 1.1.3 =
* Fixed validation problems if source is a link containg an &

= 1.1.2 =
* Title link always links to correct username, rather than the last person to tweet on that feed
* Added option to hide RSS icon/link

= 1.1.1 =
* Fixed issue with @username parsing of two names with one space between them (@test @ing)
* Fixed readme typo

= 1.1.0 =
* Most major fix is the inclusion of json_decode.php for users that don't have json_decode() which was added in PHP 5.2.0
* Fixed problem with displaying a useless li when profile images aren't displayed on a single user widget
* Default title is now set to "Twitter: UserName"

= 1.0.0 =
* Released to wordpress.org repository

= 0.0.3 =
* Fixed some of the settings used with Snoopy
* Set a read timeout for fetching the files

= 0.0.2 =
* Changed some function names
* Moved form display to a separate function (_showForm)
* Now uses wp_parse_args to handle defaults
* Added comments
* Added seconds to the _timeSince function so you can have something like "about 25 seconds ago"

= 0.0.1 =
* Original Version
