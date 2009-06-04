=== Twitter Widget Pro ===
Contributors: aaroncampbell
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=paypal%40xavisys%2ecom&item_name=Twitter%20Widget%20Pro&no_shipping=0&no_note=1&tax=0&currency_code=USD&lc=US&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: twitter, widget, feed
Requires at least: 2.7
Tested up to: 2.8
Stable tag: 1.3.5

A widget that properly handles twitter feeds, including parsing @username and URLs into links. Requires PHP5.

== Description ==

A widget that properly handles twitter feeds, including @username and link
parsing.  It supports displaying profiles images, and even lets you control
whether to display the time and date of a tweet or how log ago it happened
(about 5 hours ago, etc).  Requires PHP5.

You may also be interested in WordPress tips and tricks at <a href="http://wpinformer.com">WordPress Informer</a>

== Installation ==

1. Verify that you have PHP5, which is required for this plugin.
1. Upload the whole `twitter-widget-pro` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Can I have use than one instance of this widget? =

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
