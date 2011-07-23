<?php
/**
 * Plugin Name: Twitter Widget Pro
 * Plugin URI: http://xavisys.com/wordpress-plugins/wordpress-twitter-widget/
 * Description: A widget that properly handles twitter feeds, including @username, #hashtag, and link parsing.  It can even display profile images for the users.  Requires PHP5.
 * Version: 2.2.3
 * Author: Aaron D. Campbell
 * Author URI: http://xavisys.com/
 * License: GPLv2 or later
 * Text Domain: twitter-widget-pro
 */

/*
	Copyright 2006-current  Aaron D. Campbell  ( email : wp_plugins@xavisys.com )

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	( at your option ) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require_once( 'xavisys-plugin-framework.php' );
class wpTwitterWidgetException extends Exception {}

/**
 * WP_Widget_Twitter_Pro is the class that handles the main widget.
 */
class WP_Widget_Twitter_Pro extends WP_Widget {
	public function WP_Widget_Twitter_Pro () {
		$this->_slug = 'twitter-widget-pro';
		$wpTwitterWidget = wpTwitterWidget::getInstance();
		$widget_ops = array(
			'classname' => 'widget_twitter',
			'description' => __( 'Follow a Twitter Feed', $wpTwitterWidget->get_slug() )
		);
		$control_ops = array(
			'width' => 400,
			'height' => 350,
			'id_base' => 'twitter'
		);
		$name = __( 'Twitter Widget Pro', $wpTwitterWidget->get_slug() );

		$this->WP_Widget( 'twitter', $name, $widget_ops, $control_ops );
	}

	private function _getInstanceSettings ( $instance ) {
		$wpTwitterWidget = wpTwitterWidget::getInstance();
		return $wpTwitterWidget->getSettings( $instance );
	}

	public function form( $instance ) {
		$instance = $this->_getInstanceSettings( $instance );
		$wpTwitterWidget = wpTwitterWidget::getInstance();
?>
			<p>
				<label for="<?php echo $this->get_field_id( 'username' ); ?>"><?php _e( 'Twitter username:', $this->_slug ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" type="text" value="<?php esc_attr_e( $instance['username'] ); ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Give the feed a title ( optional ):', $this->_slug ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php esc_attr_e( $instance['title'] ); ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'items' ); ?>"><?php _e( 'How many items would you like to display?', $this->_slug ); ?></label>
				<select id="<?php echo $this->get_field_id( 'items' ); ?>" name="<?php echo $this->get_field_name( 'items' ); ?>">
					<?php
						for ( $i = 1; $i <= 20; ++$i ) {
							echo "<option value='$i' ". selected( $instance['items'], $i, false ). ">$i</option>";
						}
					?>
				</select>
			</p>
			<p>
				<input class="checkbox" type="checkbox" value="true" id="<?php echo $this->get_field_id( 'hidereplies' ); ?>" name="<?php echo $this->get_field_name( 'hidereplies' ); ?>"<?php checked( $instance['hidereplies'], 'true' ); ?> />
				<label for="<?php echo $this->get_field_id( 'hidereplies' ); ?>"><?php _e( 'Hide @replies', $this->_slug ); ?></label>
			</p>
			<p>
				<input class="checkbox" type="checkbox" value="true" id="<?php echo $this->get_field_id( 'hidefrom' ); ?>" name="<?php echo $this->get_field_name( 'hidefrom' ); ?>"<?php checked( $instance['hidefrom'], 'true' ); ?> />
				<label for="<?php echo $this->get_field_id( 'hidefrom' ); ?>"><?php _e( 'Hide sending applications', $this->_slug ); ?></label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'errmsg' ); ?>"><?php _e( 'What to display when Twitter is down ( optional ):', $this->_slug ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'errmsg' ); ?>" name="<?php echo $this->get_field_name( 'errmsg' ); ?>" type="text" value="<?php esc_attr_e( $instance['errmsg'] ); ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'fetchTimeOut' ); ?>"><?php _e( 'Number of seconds to wait for a response from Twitter ( default 2 ):', $this->_slug ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'fetchTimeOut' ); ?>" name="<?php echo $this->get_field_name( 'fetchTimeOut' ); ?>" type="text" value="<?php esc_attr_e( $instance['fetchTimeOut'] ); ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'showts' ); ?>"><?php _e( 'Show date/time of Tweet ( rather than 2 ____ ago ):', $this->_slug ); ?></label>
				<select id="<?php echo $this->get_field_id( 'showts' ); ?>" name="<?php echo $this->get_field_name( 'showts' ); ?>">
					<option value="0" <?php selected( $instance['showts'], '0' ); ?>><?php _e( 'Always', $this->_slug );?></option>
					<option value="3600" <?php selected( $instance['showts'], '3600' ); ?>><?php _e( 'If over an hour old', $this->_slug );?></option>
					<option value="86400" <?php selected( $instance['showts'], '86400' ); ?>><?php _e( 'If over a day old', $this->_slug );?></option>
					<option value="604800" <?php selected( $instance['showts'], '604800' ); ?>><?php _e( 'If over a week old', $this->_slug );?></option>
					<option value="2592000" <?php selected( $instance['showts'], '2592000' ); ?>><?php _e( 'If over a month old', $this->_slug );?></option>
					<option value="31536000" <?php selected( $instance['showts'], '31536000' ); ?>><?php _e( 'If over a year old', $this->_slug );?></option>
					<option value="-1" <?php selected( $instance['showts'], '-1' ); ?>><?php _e( 'Never', $this->_slug );?></option>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'dateFormat' ); ?>"><?php echo sprintf( __( 'Format to dispaly the date in, uses <a href="%s">PHP date()</a> format:', $this->_slug ), 'http://php.net/date' ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'dateFormat' ); ?>" name="<?php echo $this->get_field_name( 'dateFormat' ); ?>" type="text" value="<?php esc_attr_e( $instance['dateFormat'] ); ?>" />
			</p>
			<p>
				<input class="checkbox" type="checkbox" value="true" id="<?php echo $this->get_field_id( 'hiderss' ); ?>" name="<?php echo $this->get_field_name( 'hiderss' ); ?>"<?php checked( $instance['hiderss'], 'true' ); ?> />
				<label for="<?php echo $this->get_field_id( 'hiderss' ); ?>"><?php _e( 'Hide RSS Icon and Link', $this->_slug ); ?></label>
			</p>
			<p>
				<input class="checkbox" type="checkbox" value="true" id="<?php echo $this->get_field_id( 'targetBlank' ); ?>" name="<?php echo $this->get_field_name( 'targetBlank' ); ?>"<?php checked( $instance['targetBlank'], 'true' ); ?> />
				<label for="<?php echo $this->get_field_id( 'targetBlank' ); ?>"><?php _e( 'Open links in a new window', $this->_slug ); ?></label>
			</p>
			<p>
				<input class="checkbox" type="checkbox" value="true" id="<?php echo $this->get_field_id( 'avatar' ); ?>" name="<?php echo $this->get_field_name( 'avatar' ); ?>"<?php checked( $instance['avatar'], 'true' ); ?> />
				<label for="<?php echo $this->get_field_id( 'avatar' ); ?>"><?php _e( 'Show Profile Image', $this->_slug ); ?></label>
			</p>
			<p>
				<input class="checkbox" type="checkbox" value="true" id="<?php echo $this->get_field_id( 'showXavisysLink' ); ?>" name="<?php echo $this->get_field_name( 'showXavisysLink' ); ?>"<?php checked( $instance['showXavisysLink'], 'true' ); ?> />
				<label for="<?php echo $this->get_field_id( 'showXavisysLink' ); ?>"><?php _e( 'Show Link to Twitter Widget Pro', $this->_slug ); ?></label>
			</p>
			<p><?php echo $wpTwitterWidget->getSupportForumLink(); ?></p>
<?php
		return;
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $this->_getInstanceSettings( $new_instance );

		// Clean up the free-form areas
		$instance['title'] = stripslashes( $new_instance['title'] );
		$instance['errmsg'] = stripslashes( $new_instance['errmsg'] );

		// If the current user isn't allowed to use unfiltered HTML, filter it
		if ( !current_user_can( 'unfiltered_html' ) ) {
			$instance['title'] = strip_tags( $new_instance['title'] );
			$instance['errmsg'] = strip_tags( $new_instance['errmsg'] );
		}

		return $instance;
	}

	public function flush_widget_cache() {
		wp_cache_delete( 'widget_twitter_widget_pro', 'widget' );
	}

	public function widget( $args, $instance ) {
		$instance = $this->_getInstanceSettings( $instance );
		$wpTwitterWidget = wpTwitterWidget::getInstance();
		echo $wpTwitterWidget->display( wp_parse_args( $instance, $args ) );
	}
}


/**
 * wpTwitterWidget is the class that handles everything outside the widget. This
 * includes filters that modify tweet content for things like linked usernames.
 * It also helps us avoid name collisions.
 */
class wpTwitterWidget extends XavisysPlugin {
	/**
	 * @var wpTwitterWidget - Static property to hold our singleton instance
	 */
	static $instance = false;

	protected function _init() {
		$this->_hook = 'twitterWidgetPro';
		$this->_file = plugin_basename( __FILE__ );
		$this->_pageTitle = __( 'Twitter Widget Pro', $this->_slug );
		$this->_menuTitle = __( 'Twitter Widget', $this->_slug );
		$this->_accessLevel = 'manage_options';
		$this->_optionGroup = 'twp-options';
		$this->_optionNames = array( 'twp' );
		$this->_optionCallbacks = array();
		$this->_slug = 'twitter-widget-pro';
		$this->_paypalButtonId = '9993090';

		/**
		 * Add filters and actions
		 */
		add_action( 'widgets_init', array( $this, 'register' ) );
		add_filter( 'widget_twitter_content', array( $this, 'linkTwitterUsers' ) );
		add_filter( 'widget_twitter_content', array( $this, 'linkUrls' ) );
		add_filter( 'widget_twitter_content', array( $this, 'linkHashtags' ) );
		add_filter( 'widget_twitter_content', 'convert_chars' );
		add_filter( $this->_slug .'-opt-twp', array( $this, 'filterSettings' ) );
		add_shortcode( 'twitter-widget', array( $this, 'handleShortcodes' ) );
	}

	/**
	 * Function to instantiate our class and make it a singleton
	 */
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function get_slug() {
		return $this->_slug;
	}

	public function addOptionsMetaBoxes() {
		add_meta_box( $this->_slug . '-general-settings', __( 'General Settings', $this->_slug ), array( $this, 'generalSettingsMetaBox' ), 'xavisys-' . $this->_slug, 'main' );
	}

	public function generalSettingsMetaBox() {
		?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label for="twp_username"><?php _e( 'Twitter username:', $this->_slug ); ?></label>
						</th>
						<td>
							<input id="twp_username" name="twp[username]" type="text" class="regular-text code" value="<?php esc_attr_e( $this->_settings['twp']['username'] ); ?>" size="40" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="twp_title"><?php _e( 'Give the feed a title ( optional ):', $this->_slug ); ?></label>
						</th>
						<td>
							<input id="twp_title" name="twp[title]" type="text" class="regular-text code" value="<?php esc_attr_e( $this->_settings['twp']['title'] ); ?>" size="40" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="twp_items"><?php _e( 'How many items would you like to display?', $this->_slug ); ?></label>
						</th>
						<td>
							<select id="twp_items" name="twp[items]">
								<?php
									for ( $i = 1; $i <= 20; ++$i ) {
										echo "<option value='$i' ". selected( $this->_settings['twp']['items'], $i, false ). ">$i</option>";
									}
								?>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="twp_errmsg"><?php _e( 'What to display when Twitter is down ( optional ):', $this->_slug ); ?></label>
						</th>
						<td>
							<input id="twp_errmsg" name="twp[errmsg]" type="text" class="regular-text code" value="<?php esc_attr_e( $this->_settings['twp']['errmsg'] ); ?>" size="40" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="twp_fetchTimeOut"><?php _e( 'Number of seconds to wait for a response from Twitter ( default 2 ):', $this->_slug ); ?></label>
						</th>
						<td>
							<input id="twp_fetchTimeOut" name="twp[fetchTimeOut]" type="text" class="regular-text code" value="<?php esc_attr_e( $this->_settings['twp']['fetchTimeOut'] ); ?>" size="40" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="twp_showts"><?php _e( 'Show date/time of Tweet ( rather than 2 ____ ago ):', $this->_slug ); ?></label>
						</th>
						<td>
							<select id="twp_showts" name="twp[showts]">
								<option value="0" <?php selected( $this->_settings['twp']['showts'], '0' ); ?>><?php _e( 'Always', $this->_slug );?></option>
								<option value="3600" <?php selected( $this->_settings['twp']['showts'], '3600' ); ?>><?php _e( 'If over an hour old', $this->_slug );?></option>
								<option value="86400" <?php selected( $this->_settings['twp']['showts'], '86400' ); ?>><?php _e( 'If over a day old', $this->_slug );?></option>
								<option value="604800" <?php selected( $this->_settings['twp']['showts'], '604800' ); ?>><?php _e( 'If over a week old', $this->_slug );?></option>
								<option value="2592000" <?php selected( $this->_settings['twp']['showts'], '2592000' ); ?>><?php _e( 'If over a month old', $this->_slug );?></option>
								<option value="31536000" <?php selected( $this->_settings['twp']['showts'], '31536000' ); ?>><?php _e( 'If over a year old', $this->_slug );?></option>
								<option value="-1" <?php selected( $this->_settings['twp']['showts'], '-1' ); ?>><?php _e( 'Never', $this->_slug );?></option>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="twp_dateFormat"><?php echo sprintf( __( 'Format to dispaly the date in, uses <a href="%s">PHP date()</a> format:', $this->_slug ), 'http://php.net/date' ); ?></label>
						</th>
						<td>
							<input id="twp_dateFormat" name="twp[dateFormat]" type="text" class="regular-text code" value="<?php esc_attr_e( $this->_settings['twp']['dateFormat'] ); ?>" size="40" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e( "Other Setting:", $this->_slug );?>
						</th>
						<td>
							<input class="checkbox" type="checkbox" value="true" id="twp_hidereplies" name="twp[hidereplies]"<?php checked( $this->_settings['twp']['hidereplies'], 'true' ); ?> />
							<label for="twp_hidereplies"><?php _e( 'Hide @replies', $this->_slug ); ?></label>
							<br />
							<input class="checkbox" type="checkbox" value="true" id="twp_hidefrom" name="twp[hidefrom]"<?php checked( $this->_settings['twp']['hidefrom'], 'true' ); ?> />
							<label for="twp_hidefrom"><?php _e( 'Hide sending applications', $this->_slug ); ?></label>
							<br />
							<input class="checkbox" type="checkbox" value="true" id="twp_hiderss" name="twp[hiderss]"<?php checked( $this->_settings['twp']['hiderss'], 'true' ); ?> />
							<label for="twp_hiderss"><?php _e( 'Hide RSS Icon and Link', $this->_slug ); ?></label>
							<br />
							<input class="checkbox" type="checkbox" value="true" id="twp_targetBlank" name="twp[targetBlank]"<?php checked( $this->_settings['twp']['targetBlank'], 'true' ); ?> />
							<label for="twp_targetBlank"><?php _e( 'Open links in a new window', $this->_slug ); ?></label>
							<br />
							<input class="checkbox" type="checkbox" value="true" id="twp_avatar" name="twp[avatar]"<?php checked( $this->_settings['twp']['avatar'], 'true' ); ?> />
							<label for="twp_avatar"><?php _e( 'Show Profile Image', $this->_slug ); ?></label>
							<br />
							<input class="checkbox" type="checkbox" value="true" id="twp_showXavisysLink" name="twp[showXavisysLink]"<?php checked( $this->_settings['twp']['showXavisysLink'], 'true' ); ?> />
							<label for="twp_showXavisysLink"><?php _e( 'Show Link to Twitter Widget Pro', $this->_slug ); ?></label>
						</td>
					</tr>
				</table>
		<?php
	}

	/**
	 * Replace @username with a link to that twitter user
	 *
	 * @param string $text - Tweet text
	 * @return string - Tweet text with @replies linked
	 */
	public function linkTwitterUsers( $text ) {
		$text = preg_replace_callback('/(^|\s)@(\w+)/i', array($this, '_linkTwitterUsersCallback'), $text);
		return $text;
	}

	private function _linkTwitterUsersCallback( $matches ) {
		$linkAttrs = array(
			'href'	=> 'http://twitter.com/' . urlencode( $matches[2] ),
			'class'	=> 'twitter-user'
		);
		return $matches[1] . $this->_buildLink( '@'.$matches[2], $linkAttrs );
	}

	/**
	 * Replace #hashtag with a link to search.twitter.com for that hashtag
	 *
	 * @param string $text - Tweet text
	 * @return string - Tweet text with #hashtags linked
	 */
	public function linkHashtags( $text ) {
		$text = preg_replace_callback('/(^|\s)(#\w*)/i', array($this, '_linkHashtagsCallback'), $text);
		return $text;
	}

	/**
	 * Replace #hashtag with a link to search.twitter.com for that hashtag
	 *
	 * @param array $matches - Tweet text
	 * @return string - Tweet text with #hashtags linked
	 */
	private function _linkHashtagsCallback( $matches ) {
		$linkAttrs = array(
			'href'	=> 'http://search.twitter.com/search?q=' . urlencode( $matches[2] ),
			'class'	=> 'twitter-hashtag'
		);
		return $matches[1] . $this->_buildLink( $matches[2], $linkAttrs );
	}

	/**
	 * Turn URLs into links
	 *
	 * @param string $text - Tweet text
	 * @return string - Tweet text with URLs repalced with links
	 */
	public function linkUrls( $text ) {
		/**
		 * match protocol://address/path/file.extension?some=variable&another=asf%
		 * $1 is a possible space, this keeps us from linking href="[link]" etc
		 * $2 is the whole URL
		 * $3 is protocol://
		 * $4 is the URL without the protocol://
		 * $5 is the URL parameters
		 */
		$text = preg_replace_callback("/(^|\s)(([a-zA-Z]+:\/\/)([a-z][a-z0-9_\..-]*[a-z]{2,6})([a-zA-Z0-9~\/*-?&%]*))/i", array($this, '_linkUrlsCallback'), $text);

		/**
		 * match www.something.domain/path/file.extension?some=variable&another=asf%
		 * $1 is a possible space, this keeps us from linking href="[link]" etc
		 * $2 is the whole URL that was matched.  The protocol is missing, so we assume http://
		 * $3 is www.
		 * $4 is the URL matched without the www.
		 * $5 is the URL parameters
		 */
		$text = preg_replace_callback("/(^|\s)(www\.([a-z][a-z0-9_\..-]*[a-z]{2,6})([a-zA-Z0-9~\/*-?&%]*))/i", array($this, '_linkUrlsCallback'), $text);

		return $text;
	}

	private function _linkUrlsCallback ( $matches ) {
		$linkAttrs = array(
			'href'	=> $matches[2]
		);
		return $matches[1] . $this->_buildLink( $matches[2], $linkAttrs );
	}

	private function _notEmpty( $v ) {
		return !( empty( $v ) );
	}

	private function _buildLink( $text, $attributes = array(), $noFilter = false ) {
		$attributes = array_filter( wp_parse_args( $attributes ), array( $this, '_notEmpty' ) );
		$attributes = apply_filters( 'widget_twitter_link_attributes', $attributes );
		$attributes = wp_parse_args( $attributes );
		if ( strtolower( 'www' == substr( $attributes['href'], 0, 3 ) ) ) {
			$attributes['href'] = 'http://' . $attributes['href'];
		}
		$text = apply_filters( 'widget_twitter_link_text', $text );
		$link = '<a';
		foreach ( $attributes as $name => $value ) {
			$link .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}
		$link .= '>';
		if ( $noFilter ) {
			$link .= $text;
		} else {
			$link .= esc_html( $text );
		}
		$link .= '</a>';
		return $link;
	}

	public function register() {
		register_widget( 'WP_Widget_Twitter_Pro' );
	}

	public function targetBlank( $attributes ) {
		$attributes['target'] = '_blank';
		return $attributes;
	}

	public function display( $args ) {
		$args = wp_parse_args( $args );

		if ( $args['targetBlank'] ) {
			add_filter( 'widget_twitter_link_attributes', array( $this, 'targetBlank' ) );
		}

		// Validate our options
		$args['items'] = (int) $args['items'];
		if ( $args['items'] < 1 || 20 < $args['items'] ) {
			$args['items'] = 10;
		}
		if ( !isset( $args['showts'] ) ) {
			$args['showts'] = 86400;
		}

		try {
			$tweets = $this->_getTweets( $args );
		} catch ( wpTwitterWidgetException $e ) {
			$tweets = $e;
		}

		$widgetContent = $args['before_widget'] . '<div>';

		// If "hide rss" hasn't been checked, show the linked icon
		if ( $args['hiderss'] != 'true' ) {
			if ( file_exists( dirname( __FILE__ ) . '/rss.png' ) ) {
				$icon = str_replace( ABSPATH, get_option( 'siteurl' ).'/', dirname( __FILE__ ) ) . '/rss.png';
			} else {
				$icon = get_option( 'siteurl' ).'/wp-includes/images/rss.png';
			}
			$feedUrl = $this->_getFeedUrl( $args, 'rss', false );
			$linkAttrs = array(
				'class'	=> 'twitterwidget twitterwidget-rss',
				'title'	=> __( 'Syndicate this content', $this->_slug ),
				'href'	=> $feedUrl
			);

			$args['before_title'] .= $this->_buildLink( "<img style='background:orange;color:white;border:none;' width='14' height='14' src='{$icon}' alt='RSS' />", $linkAttrs, true );
		}
		$twitterLink = 'http://twitter.com/' . $args['username'];

		if ( empty( $args['title'] ) ) {
			$args['title'] = "Twitter: {$args['username']}";
		}
		$linkAttrs = array(
			'class'	=> 'twitterwidget twitterwidget-title',
			'title'	=> "Twitter: {$args['username']}",
			'href'	=> $twitterLink
		);
		$args['title'] = $this->_buildLink( $args['title'], $linkAttrs, current_user_can( 'unfiltered_html' ) );
		$widgetContent .= $args['before_title'] . $args['title'] . $args['after_title'];
		if ( !is_a( $tweets, 'wpTwitterWidgetException' ) && !empty( $tweets[0] ) && $args['avatar'] == 'true' ) {
			$widgetContent .= '<div class="twitter-avatar">';
			$widgetContent .= $this->_getProfileImage( $tweets[0]->user );
			$widgetContent .= '</div>';
		}
		$widgetContent .= '<ul>';
		if ( is_a( $tweets, 'wpTwitterWidgetException' ) ) {
			$widgetContent .= '<li class="wpTwitterWidgetError">' . $tweets->getMessage() . '</li>';
		} else if ( count( $tweets ) == 0 ) {
			$widgetContent .= '<li class="wpTwitterWidgetEmpty">' . __( 'No Tweets Available', $this->_slug ) . '</li>';
		} else {
			$count = 0;
			foreach ( $tweets as $tweet ) {
				if ( $args['hidereplies'] != 'true' || empty( $tweet->in_reply_to_user_id_str ) ) {
					// Set our "ago" string which converts the date to "# ___(s) ago"
					$tweet->ago = $this->_timeSince( strtotime( $tweet->created_at ), $args['showts'], $args['dateFormat'] );
					$entryContent = apply_filters( 'widget_twitter_content', $tweet->text );
					$from = sprintf( __( 'from %s', $this->_slug ), str_replace( '&', '&amp;', $tweet->source ) );
					$widgetContent .= '<li>';
					$widgetContent .= "<span class='entry-content'>{$entryContent}</span>";
					$widgetContent .= " <span class='entry-meta'>";
					$widgetContent .= "<span class='time-meta'>";
					$linkAttrs = array(
						'href'	=> "http://twitter.com/{$tweet->user->screen_name}/statuses/{$tweet->id_str}"
					);
					$widgetContent .= $this->_buildLink( $tweet->ago, $linkAttrs );
					$widgetContent .= '</span>';
					if ( 'true' != $args['hidefrom'] )
						$widgetContent .= " <span class='from-meta'>{$from}</span>";
					if ( !empty( $tweet->in_reply_to_screen_name ) ) {
						$rtLinkText = sprintf( __( 'in reply to %s', $this->_slug ), $tweet->in_reply_to_screen_name );
						$widgetContent .=  ' <span class="in-reply-to-meta">';
						$linkAttrs = array(
							'href'	=> "http://twitter.com/{$tweet->in_reply_to_screen_name}/statuses/{$tweet->in_reply_to_status_id_str}",
							'class'	=> 'reply-to'
						);
						$widgetContent .= $this->_buildLink( $rtLinkText, $linkAttrs );
						$widgetContent .= '</span>';
					}
					$widgetContent .= '</span></li>';

					if ( ++$count >= $args['items'] ) {
						break;
					}
				}
			}
		}

		if ( $args['showXavisysLink'] == 'true' ) {
			$widgetContent .= '<li class="xavisys-link"><span class="xavisys-link-text">';
			$linkAttrs = array(
				'href'	=> 'http://xavisys.com/wordpress-plugins/wordpress-twitter-widget/',
				'title'	=> __( 'Get Twitter Widget for your WordPress site', $this->_slug )
			);
			$widgetContent .= __( 'Powered by', $this->_slug );
			$widgetContent .= $this->_buildLink( 'WordPress Twitter Widget Pro', $linkAttrs );
			$widgetContent .= '</span></li>';
		}
		$widgetContent .= '</ul></div>' . $args['after_widget'];
		return $widgetContent;
	}

	/**
	 * Gets tweets, from cache if possible
	 *
	 * @param array $widgetOptions - options needed to get feeds
	 * @return array - Array of objects
	 */
	private function _getTweets( $widgetOptions ) {
		$key = md5( $this->_getFeedUrl( $widgetOptions ) );
		if ( false === ( $tweets = get_site_transient( 'twp_' . $key ) ) ) {
			try {
				$tweets = $this->_parseFeed( $widgetOptions );
				set_site_transient( 'twp_' . $key, $tweets, 300 ); // cache for 5 minutes
			} catch ( wpTwitterWidgetException $e ) {
				throw $e;
			}
		}
		return $tweets;
	}

	/**
	 * Pulls the JSON feed from Twitter and returns an array of objects
	 *
	 * @param array $widgetOptions - settings needed to get feed url, etc
	 * @return array
	 */
	private function _parseFeed( $widgetOptions ) {
		$feedUrl = $this->_getFeedUrl( $widgetOptions );
		$resp = wp_remote_request( $feedUrl, array( 'timeout' => $widgetOptions['fetchTimeOut'] ) );

		if ( !is_wp_error( $resp ) && $resp['response']['code'] >= 200 && $resp['response']['code'] < 300 ) {
			$decodedResponse = json_decode( $resp['body'] );
			if ( empty( $decodedResponse ) ) {
				if ( empty( $widgetOptions['errmsg'] ) ) {
					$widgetOptions['errmsg'] = __( 'Invalid Twitter Response.', $this->_slug );
				}
				throw new wpTwitterWidgetException( $widgetOptions['errmsg'] );
			} elseif( !empty( $decodedResponse->error ) ) {
				if ( empty( $widgetOptions['errmsg'] ) ) {
					$widgetOptions['errmsg'] = $decodedResponse->error;
				}
				throw new wpTwitterWidgetException( $widgetOptions['errmsg'] );
			} else {
				return $decodedResponse;
			}
		} else {
			// Failed to fetch url;
			if ( empty( $widgetOptions['errmsg'] ) ) {
				$widgetOptions['errmsg'] = __( 'Could not connect to Twitter', $this->_slug );
			}
			throw new wpTwitterWidgetException( $widgetOptions['errmsg'] );
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
	private function _getFeedUrl( $widgetOptions, $type = 'json', $count = true ) {
		if ( !in_array( $type, array( 'rss', 'json' ) ) ) {
			$type = 'json';
		}
		if ( $count ) {
			$num = ( $widgetOptions['hidereplies'] )? 100:$widgetOptions['items'];
			$count = sprintf( '?count=%u', $num );
		} else {
			$count = '';
		}
		return sprintf( 'http://twitter.com/statuses/user_timeline/%1$s.%2$s%3$s', $widgetOptions['username'], $type, $count );
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
	private function _timeSince( $startTimestamp, $max, $dateFormat ) {
		// array of time period chunks
		$chunks = array(
			'year'   => 60 * 60 * 24 * 365, // 31,536,000 seconds
			'month'  => 60 * 60 * 24 * 30,  // 2,592,000 seconds
			'week'   => 60 * 60 * 24 * 7,   // 604,800 seconds
			'day'    => 60 * 60 * 24,       // 86,400 seconds
			'hour'   => 60 * 60,            // 3600 seconds
			'minute' => 60,                 // 60 seconds
			'second' => 1                   // 1 second
		);

		$since = time() - $startTimestamp;

		if ( $max != '-1' && $since >= $max ) {
			return date_i18n( $dateFormat, $startTimestamp );
		}

		foreach ( $chunks as $key => $seconds ) {
			// finding the biggest chunk ( if the chunk fits, break )
			if ( ( $count = floor( $since / $seconds ) ) != 0 ) {
				break;
			}
		}

		$messages = array(
			'year'   => _n( 'about %s year ago', 'about %s years ago', $count, $this->_slug ),
			'month'  => _n( 'about %s month ago', 'about %s months ago', $count, $this->_slug ),
			'week'   => _n( 'about %s week ago', 'about %s weeks ago', $count, $this->_slug ),
			'day'    => _n( 'about %s day ago', 'about %s days ago', $count, $this->_slug ),
			'hour'   => _n( 'about %s hour ago', 'about %s hours ago', $count, $this->_slug ),
			'minute' => _n( 'about %s minute ago', 'about %s minutes ago', $count, $this->_slug ),
			'second' => _n( 'about %s second ago', 'about %s seconds ago', $count, $this->_slug ),
		);

		return sprintf( $messages[$key], $count );
	}

	/**
	 * Returns the Twitter user's profile image, linked to that user's profile
	 *
	 * @param object $user - Twitter User
	 * @return string - Linked image ( XHTML )
	 */
	private function _getProfileImage( $user ) {
		$linkAttrs = array(
			'href'  => "http://twitter.com/{$user->screen_name}",
			'title' => $user->name
		);
		return $this->_buildLink( "<img alt='{$user->name}' src='{$user->profile_image_url}' />", $linkAttrs, true );
	}

    /**
	 * Replace our shortCode with the "widget"
	 *
	 * @param array $attr - array of attributes from the shortCode
	 * @param string $content - Content of the shortCode
	 * @return string - formatted XHTML replacement for the shortCode
	 */
    public function handleShortcodes( $attr, $content = '' ) {
		$defaults = array(
			'before_widget'   => '',
			'after_widget'    => '',
			'before_title'    => '<h2>',
			'after_title'     => '</h2>',
			'title'           => '',
			'errmsg'          => '',
			'fetchTimeOut'    => '2',
			'username'        => '',
			'hiderss'         => false,
			'hidereplies'     => false,
			'hidefrom'        => false,
			'avatar'          => false,
			'showXavisysLink' => false,
			'targetBlank'     => false,
			'items'           => 10,
			'showts'          => 60 * 60 * 24,
			'dateFormat'      => __( 'h:i:s A F d, Y', $this->_slug ),
		);

		/**
		 * Attribute names are strtolower'd, so we need to fix them to match
		 * the names used through the rest of the plugin
		 */
		if ( array_key_exists( 'fetchtimeout', $attr ) ) {
			$attr['fetchTimeOut'] = $attr['fetchtimeout'];
			unset( $attr['fetchtimeout'] );
		}
		if ( array_key_exists( 'showxavisyslink', $attr ) ) {
			$attr['showXavisysLink'] = $attr['showxavisyslink'];
			unset( $attr['showxavisyslink'] );
		}
		if ( array_key_exists( 'targetblank', $attr ) ) {
			$attr['targetBlank'] = $attr['targetblank'];
			unset( $attr['targetblank'] );
		}
		if ( array_key_exists( 'dateformat', $attr ) ) {
			$attr['dateFormat'] = $attr['dateformat'];
			unset( $attr['dateformat'] );
		}

		if ( !empty( $content ) && empty( $attr['title'] ) ) {
			$attr['title'] = $content;
		}

        $attr = shortcode_atts( $defaults, $attr );

		if ( $attr['hiderss'] && $attr['hiderss'] != 'false' && $attr['hiderss'] != '0' ) {
			$attr['hiderss'] == true;
		}
		if ( $attr['hidereplies'] && $attr['hidereplies'] != 'false' && $attr['hidereplies'] != '0' ) {
			$attr['hidereplies'] == true;
		}
		if ( $attr['hidefrom'] && $attr['hidefrom'] != 'false' && $attr['hidefrom'] != '0' ) {
			$attr['hidefrom'] == true;
		}
		if ( $attr['avatar'] && $attr['avatar'] != 'false' && $attr['avatar'] != '0' ) {
			$attr['avatar'] == true;
		}
		if ( $attr['showXavisysLink'] && $attr['showXavisysLink'] != 'false' && $attr['showXavisysLink'] != '0' ) {
			$attr['showXavisysLink'] == true;
		}
		if ( $attr['targetBlank'] && $attr['targetBlank'] != 'false' && $attr['targetBlank'] != '0' ) {
			$attr['targetBlank'] == true;
		}

		return $this->display( $attr );
	}

	public function filterSettings( $settings ) {
		$defaultArgs = array(
			'title'           => '',
			'errmsg'          => '',
			'fetchTimeOut'    => '2',
			'username'        => '',
			'hiderss'         => false,
			'hidereplies'     => false,
			'hidefrom'        => false,
			'avatar'          => false,
			'showXavisysLink' => false,
			'targetBlank'     => false,
			'items'           => 10,
			'showts'          => 60 * 60 * 24,
			'dateFormat'      => __( 'h:i:s A F d, Y', $this->_slug ),
		);

		return wp_parse_args( $settings, $defaultArgs );
	}

	public function getSettings( $settings ) {
		return wp_parse_args( $settings, $this->_settings['twp'] );
	}
}
// Instantiate our class
$wpTwitterWidget = wpTwitterWidget::getInstance();
