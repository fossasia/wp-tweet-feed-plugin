<?php
/**
 * Plugin Name: Twitter Widget Pro
 * Plugin URI: http://xavisys.com/wordpress-twitter-widget/
 * Description: A widget that properly handles twitter feeds, including @username and link parsing, feeds that include friends or just one user, and can even display profile images for the users.  Requires PHP5.
 * Version: 1.1.1
 * Author: Aaron D. Campbell
 * Author URI: http://xavisys.com/
 */

/**
 * Changelog:
 * 04/23/2008: 1.1.1
 * 	- Fixed issue with @username parsing of two names with one space between them (@test @ing)
 * 	- Fixed readme typo
 *
 * 04/23/2008: 1.1.0
 * 	- Most major fix is the inclusion of json_decode.php for users that don't have json_decode() which was added in PHP 5.2.0
 * 	- Fixed problem with displaying a useless li when profile images aren't displayed on a single user widget
 * 	- Default title is now set to "Twitter: UserName"
 *
 * 04/17/2008: 1.0.0
 * 	- Released to wordpress.org repository
 *
 * 04/14/2008: 0.0.3
 * 	- Fixed some of the settings used with Snoopy
 *  - Set a read timeout for fetching the files
 *
 * 04/14/2008: 0.0.2
 * 	- Changed some function names
 * 	- Moved form display to a separate function (_showForm)
 * 	- Now uses wp_parse_args to handle defaults
 * 	- Added comments
 * 	- Added seconds to the _timeSince function so you can have something like "about 25 seconds ago"
 *
 * 04/11/2008: 0.0.1
 * 	- Original Version
 */

/*  Copyright 2006  Aaron D. Campbell  (email : wp_plugins@xavisys.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
/**
 * wpTwitterWidget is the class that handles ALL of the plugin functionality.
 * It helps us avoid name collisions
 * http://codex.wordpress.org/Writing_a_Plugin#Avoiding_Function_Name_Collisions
 */

class wpTwitterWidget
{
	/**
	 * User Agent to send when requesting the feeds
	 *
	 * @var string
	 */
	private $userAgent;

	/**
	 * Read timeout to use when fetching the feeds.  Defaults to 2 seconds.
	 *
	 * @todo make a set function for this
	 *
	 * @var int
	 */
	private $fetchTimeOut = 2;

	/**
	 * Whether to use GZip when fetching feeds.  Defaults to true
	 *
	 * @todo make a set function for this
	 *
	 * @var bool
	 */
	private $useGzip = true;

	public function __construct() {
		// Set the user agent to Wordpress/x.x.x
		$this->userAgent = 'WordPress/' . $GLOBALS['wp_version'];
	}

	/**
	 * Pulls the JSON feed from Twitter and returns an array of objects
	 *
	 * @param array $widgetOptions - settings needed to get feed url, etc
	 * @return array
	 */
	private function _parseFeed($widgetOptions) {
		$feedUrl = $this->_getFeedUrl($widgetOptions);
		$resp = $this->_fetch_remote_file($feedUrl);
		if ( $resp->status >= 200 && $resp->status < 300 ) {
	        if (function_exists('json_decode')) {
	            return json_decode($resp->results);
	        } else {
				require_once('json_decode.php');
	        	return Zend_Json_Decoder::decode($resp->results);
			}
		} else {
			// Failed to fetch url;
			return array();
		}
	}

	/**
	 * Gets the URL for the desired feed.
	 *
	 * @param array $widgetOptions - settings needed such as username, feet type, etc
	 * @param string[optional] $type - 'rss' or 'json'
	 * @param bool[optional] $count - If true, it adds the count parameter to the URL
	 * @return string - Twitter feed URL
	 */
	private function _getFeedUrl($widgetOptions, $type = 'json', $count = true) {
		if (!in_array($type, array('rss', 'json'))) {
			$type = 'json';
		}
		if ($count) {
			$count = sprintf('?count=%u', $widgetOptions['items']);
		} else {
			$count = '';
		}
		return sprintf('http://twitter.com/statuses/%1$s_timeline/%2$s.%3$s%4$s',$widgetOptions['feed'], $widgetOptions['username'], $type, $count);
	}

	/**
	 * Replace @username with a link to that twitter user
	 *
	 * @param string $text - Tweet text
	 * @return string - Tweet text with @replies linked
	 */
	public function linkTwitterUsers($text) {
		$text = preg_replace('/(^|\s)@(\w*)/i', '$1@<a href="http://twitter.com/$2" class="twitter-user">$2</a>', $text);
		return $text;
	}

	/**
	 * Turn URLs into links
	 *
	 * @param string $text - Tweet text
	 * @return string - Tweet text with URLs repalced with links
	 */
	public function linkUrls($text) {
		/**
		 * match protocol://address/path/file.extension?some=variable&another=asf%
		 * $1 is a possible space, this keeps us from linking href="[link]" etc
		 * $2 is the whole URL
		 * $3 is protocol://
		 * $4 is the URL without the protocol://
		 * $5 is the URL parameters
		 */
		$text = preg_replace("/(^|\s)(([a-zA-Z]+:\/\/)([a-z][a-z0-9_\..-]*[a-z]{2,6})([a-zA-Z0-9\/*-?&%]*))/i", "$1<a href=\"$2\">$2</a>", $text);

		/**
		 * match www.something.domain/path/file.extension?some=variable&another=asf%
		 * $1 is a possible space, this keeps us from linking href="[link]" etc
		 * $2 is the whole URL that was matched.  The protocol is missing, so we assume http://
		 * $3 is www.
		 * $4 is the URL matched without the www.
		 * $5 is the URL parameters
		 */
		$text = preg_replace("/(^|\s)(www\.([a-z][a-z0-9_\..-]*[a-z]{2,6})([a-zA-Z0-9\/*-?&%]*))/i", "$1<a href=\"http://$2\">$2</a>", $text);

		return $text;
	}

	/**
	 * Uses snoopy class to pull file contents
	 *
	 * @param string $url - Url to get
	 * @param array $headers - Raw headers to pass
	 * @return Snoopy
	 */
	private function _fetch_remote_file ($url, $headers = "" ) {
		require_once( ABSPATH . 'wp-includes/class-snoopy.php' );
		// Snoopy is an HTTP client in PHP
		$client = new Snoopy();
		$client->agent = $this->userAgent;
		$client->read_timeout = $this->fetchTimeOut;
		$client->use_gzip = $this->useGzip;
		if (is_array($headers) ) {
			$client->rawheaders = $headers;
		}

		@$client->fetch($url);
		return $client;
	}

	/**
	 * Gets tweets, from cache if possible
	 *
	 * @param array $widgetOptions - options needed to get feeds
	 * @return array - Array of objects
	 */
	private function _getTweets($widgetOptions) {
		// Get cache of feed if it exists
		$tweets = wp_cache_get($widgetOptions['feed'] . $widgetOptions['username'], 'widget_twitter');
		// If there is no cache
		if ($tweets == false) {
			$tweets = $this->_parseFeed($widgetOptions);
			// Cache for 60 seconds, Tweets are supposed to be current, so we don't cache for very long
			wp_cache_set($widgetOptions['feed'] . $widgetOptions['username'], $tweets, 'widget_twitter', 60);
		}
		return $tweets;
	}

	/**
	 * Displays the Twitter widget, with all tweets in an unordered list.
	 * Things are classed but not styled to allow easy styling.
	 *
	 * @param array $args - Widget Settings
	 * @param array|int $widget_args - Widget Number
	 */
	public function display($args, $widget_args = 1) {
		extract( $args, EXTR_SKIP );
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );

		$options = get_option('widget_twitter');
		if ( !isset($options[$number]) ) {
			return;
		}

		// Validate our options
		if (!isset($options[$number]['feed']) || !in_array($options[$number]['feed'], array('user', 'friends'))) {
			$options[$number]['feed'] = 'user';
		}
		$options[$number]['items'] = (int) $options[$number]['items'];
		if ( $options[$number]['items'] < 1 || 20 < $options[$number]['items'] ) {
			$options[$number]['items'] = 10;
		}
		if (!isset($options[$number]['showts'])) {
			$options[$number]['showts'] = 86400;
		}

		$options[$number]['avatar'] = (isset($options[$number]['avatar']) && $options[$number]['avatar']);

		$tweets = $this->_getTweets($options[$number]);
		$tweets = array_slice($tweets, 0, $options[$number]['items']);

		echo $before_widget;
		if ( file_exists(dirname(__FILE__) . '/rss.png') ) {
			$icon = str_replace(ABSPATH, get_option('siteurl').'/', dirname(__FILE__)) . '/rss.png';
		} else {
			$icon = get_option('siteurl').'/wp-includes/images/rss.png';
		}
		$feedUrl = $this->_getFeedUrl($options[$number], 'rss', false);
		$before_title .= "<a class='twitterwidget' href='{$feedUrl}' title='" . attribute_escape(__('Syndicate this content')) ."'><img style='background:orange;color:white;border:none;' width='14' height='14' src='{$icon}' alt='RSS' /></a>";
		if (!empty($tweets)) {
			$twitterLink = 'http://twitter.com/' . $tweets[0]->user->screen_name;
			$before_title .= " <a class='twitterwidget' href='$twitterLink' title='" . attribute_escape("Twitter: {$tweets[0]->user->name}") . "'>";
			$after_title = '</a>' . $after_title;
		}
		if (empty($options[$number]['title'])) {
			$options[$number]['title'] = "Twitter: {$options[$number]['username']}";
		}
		echo $before_title . $options[$number]['title'] . $after_title;
?>
				<ul><?php
				if ( $options[$number]['feed'] == 'user' && !empty($tweets)  && $options[$number]['avatar']) {
					echo '<li>';
					echo $this->_getProfileImage($tweets[0]->user);
					echo '<div class="clear" />';
					echo '</li>';
				}
				foreach ($tweets as $tweet) {
					// Set our "ago" string which converts the date to "# ___(s) ago"
					$tweet->ago = $this->_timeSince(strtotime($tweet->created_at), $options[$number]['showts']);
?>
					<li>
<?php
						if ( $options[$number]['feed'] == 'friends' ) {
							if ( $options[$number]['avatar']) {
								echo $this->_getProfileImage($tweet->user);
							}
							echo $this->_getUserName($tweet->user);
						}
?>
						<span class="entry-content"><?php echo apply_filters( 'widget_twitter_content', $tweet->text ); ?></span>
						<span class="entry-meta">
							<a href="http://twitter.com/<?php echo $tweet->user->screen_name; ?>/statuses/<?php echo $tweet->id; ?>">
								<?php echo $tweet->ago; ?>
							</a> from <?php
							echo $tweet->source;
							if (isset($tweet->in_reply_to)) {
								echo $this->_getReplyTo($tweet->in_reply_to);
							} ?>
						</span>
					</li>
<?php
				} ?></ul>
			<?php echo $after_widget; ?>
	<?php
	}

	/**
	 * Returns a "in reply to" link to the user passed
	 *
	 * @param object $replyTo - Tweet
	 * @return string - Link to Twitter user (XHTML)
	 */
	private function _getReplyTo($replyTo) {
		return <<<replyTo
	<a href="http://twitter.com/{$replyTo->user->screen_name}/statuses/{$replyTo->id}">
		in reply to {$replyTo->user->screen_name}
	</a>
replyTo;
	}

	/**
	 * Returns the Twitter user's profile image, linked to that user's profile
	 *
	 * @param object $user - Twitter User
	 * @return string - Linked image (XHTML)
	 */
	private function _getProfileImage($user) {
		return <<<profileImage
	<a title="{$user->name}" href="http://twitter.com/{$user->screen_name}">
		<img alt="{$user->name}" src="{$user->profile_image_url}" />
	</a>
profileImage;
	}

	/**
	 * Returns the user's screen name as a link inside strong tags.
	 *
	 * @param object $user - Twitter user
	 * @return string - Username as link (XHTML)
	 */
	private function _getUserName($user) {
		return <<<profileImage
	<strong>
		<a title="{$user->name}" href="http://twitter.com/{$user->screen_name}">{$user->screen_name}</a>
	</strong>
profileImage;
	}

	/**
	 * Sets up admin forms to manage widgets
	 *
	 * @param array|int $widget_args - Widget Number
	 */
	public function control($widget_args) {
		global $wp_registered_widgets;
		static $updated = false;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );

		$options = get_option('widget_twitter');
		if ( !is_array($options) )
			$options = array();

		if ( !$updated && !empty($_POST['sidebar']) ) {
			$sidebar = (string) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets();
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			foreach ( $this_sidebar as $_widget_id ) {
				if ( array($this,'display') == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "twitter-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
						unset($options[$widget_number]);
				}
			}

			foreach ( (array) $_POST['widget-twitter'] as $widget_number => $widget_twitter ) {
				if ( !isset($widget_twitter['username']) && isset($options[$widget_number]) ) // user clicked cancel
					continue;
				$widget_twitter['title'] = strip_tags(stripslashes($widget_twitter['title']));
				$options[$widget_number] = $widget_twitter;
			}

			update_option('widget_twitter', $options);
			$updated = true;
		}

		if ( -1 != $number ) {
			$options[$number]['number'] = $number;
			$options[$number]['title'] = attribute_escape($options[$number]['title']);
			$options[$number]['username'] = attribute_escape($options[$number]['username']);
			$options[$number]['avatar'] = (bool) $options[$number]['avatar'];
			if (!isset($options[$number]['feed']) || !in_array($options[$number]['feed'], array('user', 'friends'))) {
				$options[$number]['feed'] = 'user';
			}
		}
		$this->_showForm($options[$number]);
	}

	/**
	 * Registers widget in such a way as to allow multiple instances of it
	 *
	 * @see wp-includes/widgets.php
	 */
	public function register() {
		if ( !$options = get_option('widget_twitter') )
			$options = array();
		$widget_ops = array('classname' => 'widget_twitter', 'description' => __('Follow a Twitter Feed'));
		$control_ops = array('width' => 400, 'height' => 350, 'id_base' => 'twitter');
		$name = __('Twitter Feed');

		$id = false;
		foreach ( array_keys($options) as $o ) {
			// Old widgets can have null values for some reason
			if ( !isset($options[$o]['title']) || !isset($options[$o]['username']) )
				continue;
			$id = "twitter-$o"; // Never never never translate an id
			wp_register_sidebar_widget($id, $name, array($this,'display'), $widget_ops, array( 'number' => $o ));
			wp_register_widget_control($id, $name, array($this,'control'), $control_ops, array( 'number' => $o ));
		}

		// If there are none, we register the widget's existance with a generic template
		if ( !$id ) {
			wp_register_sidebar_widget( 'twitter-1', $name, array($this,'display'), $widget_ops, array( 'number' => -1 ) );
			wp_register_widget_control( 'twitter-1', $name, array($this,'control'), $control_ops, array( 'number' => -1 ) );
		}
	}

	/**
	 * Displays the actualy for that populates the widget options box in the
	 * admin section
	 *
	 * @param array $args - Current widget settings and widget number, gets combind with defaults
	 */
	private function _showForm($args) {

		$defaultArgs = array(	'title'		=> '',
								'username'	=> '',
								'avatar'	=> false,
								'feed'		=> 'user',
								'items'		=> 10,
								'showts'	=> 60 * 60 * 24,
								'number'	=> '%i%' );
		$args = wp_parse_args( $args, $defaultArgs );
		extract( $args );
?>
			<p>
				<label for="twitter-username-<?php echo $number; ?>"><?php _e('Twitter username:'); ?></label>
				<input class="widefat" id="twitter-username-<?php echo $number; ?>" name="widget-twitter[<?php echo $number; ?>][username]" type="text" value="<?php echo $username; ?>" />
			</p>
			<p>
				<label for="twitter-title-<?php echo $number; ?>"><?php _e('Give the feed a title (optional):'); ?></label>
				<input class="widefat" id="twitter-title-<?php echo $number; ?>" name="widget-twitter[<?php echo $number; ?>][title]" type="text" value="<?php echo $title; ?>" />
				<input type="hidden" name="widget-twitter[<?php echo $number; ?>][submit]" value="1" />
			</p>
			<p>
				<label for="twitter-items-<?php echo $number; ?>"><?php _e('How many items would you like to display?'); ?></label>
				<select id="twitter-items-<?php echo $number; ?>" name="widget-twitter[<?php echo $number; ?>][items]">
					<?php
						for ( $i = 1; $i <= 20; ++$i ) {
							echo "<option value='$i' ", selected($items, $i), ">$i</option>";
						}
					?>
				</select>
			</p>
			<p>
				<label for="twitter-showts-<?php echo $number; ?>"><?php _e('Show date/time of Tweet (rather than 2 ____ ago):'); ?></label>
				<select id="twitter-showts-<?php echo $number; ?>" name="widget-twitter[<?php echo $number; ?>][showts]">
					<option value="0" <?php echo selected($showts, '0'); ?>>Always</a>
					<option value="3600" <?php echo selected($showts, '3600'); ?>>If over an hour old</a>
					<option value="86400" <?php echo selected($showts, '86400'); ?>>If over a day old</a>
					<option value="604800" <?php echo selected($showts, '604800'); ?>>If over a week old</a>
					<option value="2592000" <?php echo selected($showts, '2592000'); ?>>If over a month old</a>
					<option value="31536000" <?php echo selected($showts, '31536000'); ?>>If over a year old</a>
					<option value="-1" <?php echo selected($showts, '-1'); ?>>Never</a>
				</select>
			</p>
			<p>
				<label for="twitter-feed-<?php echo $number; ?>-user"><input class="checkbox" type="radio" id="twitter-feed-<?php echo $number; ?>-user" name="widget-twitter[<?php echo $number; ?>][feed]" value="user"<?php checked($feed, 'user'); ?> /> <?php _e('Just User'); ?></label><br />
				<label for="twitter-feed-<?php echo $number; ?>-friends"><input class="checkbox" type="radio" id="twitter-feed-<?php echo $number; ?>-friends" name="widget-twitter[<?php echo $number; ?>][feed]" value="friends"<?php checked($feed, 'friends'); ?> /> <?php _e('With Friends'); ?></label>
			</p>
			<p>
				<label for="twitter-avatar-<?php echo $number; ?>"><input class="checkbox" type="checkbox" id="twitter-avatar-<?php echo $number; ?>" name="widget-twitter[<?php echo $number; ?>][avatar]"<?php checked($avatar, true); ?> /> <?php _e('Show Profile Image(s)'); ?></label>
			</p>
<?php
	}

	/**
	 * Twitter displays all tweets that are less than 24 with something like
	 * "about 4 hours ago" and ones older than 24 hours with a time and date.
	 * This function allows us to simulate that functionality, but lets us
	 * choose where the dividing line is.
	 *
	 * @param int $startTimestamp - The timestamp used to calculate time passed
	 * @param int $max - Max number of seconds to conver to "ago" messages.  0 for all, -1 for none
	 * @return string
	 */
	private function _timeSince($startTimestamp, $max) {
	    // array of time period chunks
	    $chunks = array(
	        array('seconds' => 60 * 60 * 24 * 365, 'name' => 'year'),
	        array('seconds' => 60 * 60 * 24 * 30,  'name' => 'month'),
	        array('seconds' => 60 * 60 * 24 * 7,   'name' => 'week'),
	        array('seconds' => 60 * 60 * 24,       'name' => 'day'),
	        array('seconds' => 60 * 60,            'name' => 'hour'),
	        array('seconds' => 60,                 'name' => 'minute'),
	        array('seconds' => 1,                  'name' => 'second')
	    );

	    $since = time() - $startTimestamp;

	    if ($max != '-1' && $since >= $max) {
			return date('h:i:s A F d, Y', $startTimestamp);
	    }

	    // $j saves performing the count function each time around the loop
	    for ($i = 0, $j = count($chunks); $i < $j; $i++) {
	    	extract($chunks[$i]);

	        // finding the biggest chunk (if the chunk fits, break)
	        if (($count = floor($since / $seconds)) != 0) {
	            break;
	        }
	    }

	    $print = "{$count} {$name}";
	    if ($count > 1) {
	    	$print .= 's';
	    }

	    return "about {$print} ago";
	}
}
// Instantiate our class
$wpTwitterWidget = new wpTwitterWidget();

/**
 * Add filters and actions
 */
add_action('widgets_init', array($wpTwitterWidget, 'register'));
add_filter('widget_twitter_content', array($wpTwitterWidget, 'linkTwitterUsers'));
add_filter('widget_twitter_content', array($wpTwitterWidget, 'linkUrls'));