<?php
/**
 * Version: 1.0.4
 */
/**
 * Changelog:
 *
 * 1.0.4:
 *  - Added donate link to the plugin meta
 *
 * 1.0.3:
 *  - Changed to use new cdn for images
 */
if (!class_exists('XavisysPlugin')) {
	/**
	 * Abstract class XavisysPlugin used as a WordPress Plugin framework
	 *
	 * @abstract
	 */
	abstract class XavisysPlugin {
		/**
		 * @var array Plugin settings
		 */
		protected $_settings;

		/**
		 * @var string - The options page name used in the URL
		 */
		protected $_hook = '';

		/**
		 * @var string - The filename for the main plugin file
		 */
		protected $_file = '';

		/**
		 * @var string - The options page title
		 */
		protected $_pageTitle = '';

		/**
		 * @var string - The options page menu title
		 */
		protected $_menuTitle = '';

		/**
		 * @var string - The access level required to see the options page
		 */
		protected $_accessLevel = '';

		/**
		 * @var string - The option group to register
		 */
		protected $_optionGroup = '';

		/**
		 * @var array - An array of options to register to the option group
		 */
		protected $_optionNames = array();

		/**
		 * @var array - An associated array of callbacks for the options, option name should be index, callback should be value
		 */
		protected $_optionCallbacks = array();

		/**
		 * @var string - The plugin slug used on WordPress.org and/or Xavisys forums
		 */
		protected $_slug = '';

		/**
		 * @var string - The button ID for the PayPal button, override this generic one with a plugin-specific one
		 */
		protected $_paypalButtonId = '9925248';

		/**
		 * This is our constructor, which is private to force the use of getInstance()
		 * @return void
		 */
		protected function __construct() {
			if ( is_callable( array($this, '_init') ) ) {
				$this->_init();
			}
			$this->_getSettings();
			if ( is_callable( array($this, '_postSettingsInit') ) ) {
				$this->_postSettingsInit();
			}
			add_filter( 'init', array( $this, 'init_locale' ) );
			add_action( 'admin_init', array( $this, 'registerOptions' ) );
			add_filter( 'plugin_action_links', array( $this, 'addPluginPageLinks' ), 10, 2 );
			add_filter( 'plugin_row_meta', array( $this, 'addPluginMetaLinks' ), 10, 2 );
			add_action( 'admin_menu', array( $this, 'registerOptionsPage' ) );
			if ( is_callable(array( $this, 'addOptionsMetaBoxes' )) ) {
				add_action( 'admin_init', array( $this, 'addOptionsMetaBoxes' ) );
			}
			add_action( 'admin_init', array( $this, 'addDefaultOptionsMetaBoxes' ) );
			add_action( 'wp_dashboard_setup', array( $this, 'addDashboardWidgets' ), null, 9 );
			add_action( 'admin_print_scripts', array( $this,'optionsPageScripts' ) );
			add_action( 'admin_print_styles', array( $this,'optionsPageStyles' ) );
			/**
			 * Add update messages that can be attached to the CURRENT release (not
			 * this one), but only for 2.8+
			 */
			global $wp_version;
			if ( version_compare('2.8', $wp_version, '<=') ) {
				add_action ( 'in_plugin_update_message-'.$this->_file , array ( $this , 'changelog' ), null, 2 );
			}
		}

		/**
		 * Function to instantiate our class and make it a singleton
		 */
		abstract public static function getInstance();

		public function init_locale() {
			$lang_dir = basename(dirname(__FILE__)) . '/languages';
			load_plugin_textdomain( $this->_slug, 'wp-content/plugins/' . $lang_dir, $lang_dir);
		}

		protected function _getSettings() {
			foreach ( $this->_optionNames as $opt ) {
				$this->_settings[$opt] = apply_filters($this->_slug.'-opt-'.$opt, get_option($opt));
			}
		}

		public function registerOptions() {
			foreach ( $this->_optionNames as $opt ) {
				if ( !empty($this->_optionCallbacks[$opt]) && is_callable( $this->_optionCallbacks[$opt] ) ) {
					$callback = $this->_optionCallbacks[$opt];
				} else {
					$callback = '';
				}
				register_setting( $this->_optionGroup, $opt, $callback );
			}
		}

		public function changelog ($pluginData, $newPluginData) {
			require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );

			$plugin = plugins_api( 'plugin_information', array( 'slug' => $newPluginData->slug ) );

			if ( !$plugin || is_wp_error( $plugin ) || empty( $plugin->sections['changelog'] ) ) {
				return;
			}

			$changes = $plugin->sections['changelog'];
			$pos = strpos( $changes, '<h4>' . preg_replace('/[^\d\.]/', '', $pluginData['Version'] ) );
			if ( $pos !== false ) {
				$changes = trim( substr( $changes, 0, $pos ) );
			}

			$replace = array(
				'<ul>'	=> '<ul style="list-style: disc inside; padding-left: 15px; font-weight: normal;">',
				'<h4>'	=> '<h4 style="margin-bottom:0;">',
			);
			echo str_replace( array_keys($replace), $replace, $changes );
		}

		public function registerOptionsPage() {
			if ( is_callable( array( $this, 'options_page' ) ) ) {
				add_options_page( $this->_pageTitle, $this->_menuTitle, $this->_accessLevel, $this->_hook, array( $this, 'options_page' ), 'http://cdn.xavisys.com/logos/xavisys-logo-32.png' );
			}
		}

		protected function _filterBoxesMain($boxName) {
			if ( 'main' == strtolower($boxName) ) {
				return false;
			}
			return $this->_filterBoxesHelper($boxName, 'main');
		}

		protected function _filterBoxesSidebar($boxName) {
			return $this->_filterBoxesHelper($boxName, 'sidebar');
		}

		protected function _filterBoxesHelper($boxName, $test) {
			return ( strpos( strtolower($boxName), strtolower($test) ) !== false );
		}

		public function options_page() {
			global $wp_meta_boxes;
			$allBoxes = array_keys( $wp_meta_boxes['xavisys-'.$this->_slug] );
			$mainBoxes = array_filter( $allBoxes, array( $this, '_filterBoxesMain' ) );
			unset($mainBoxes['main']);
			sort($mainBoxes);
			$sidebarBoxes = array_filter( $allBoxes, array( $this, '_filterBoxesSidebar' ) );
			unset($sidebarBoxes['sidebar']);
			sort($sidebarBoxes);
			?>
				<div class="wrap">
					<?php $this->screenIconLink(); ?>
					<h2><?php echo esc_html($this->_pageTitle); ?></h2>
					<div class="metabox-holder">
						<div class="postbox-container" style="width:75%;">
							<form action="options.php" method="post">
								<?php settings_fields( $this->_optionGroup ); ?>
								<?php do_meta_boxes( 'xavisys-' . $this->_slug, 'main', '' ); ?>
								<p class="submit">
									<input type="submit" name="Submit" value="<?php esc_attr_e('Update Options &raquo;', $this->_slug); ?>" />
								</p>
							</form>
							<?php
							foreach( $mainBoxes as $context ) {
								do_meta_boxes( 'xavisys-' . $this->_slug, $context, '' );
							}
							?>
						</div>
						<div class="postbox-container" style="width:24%;">
							<?php
							foreach( $sidebarBoxes as $context ) {
								do_meta_boxes( 'xavisys-' . $this->_slug, $context, '' );
							}
							?>
						</div>
					</div>
				</div>
				<?php
		}

		public function addPluginPageLinks( $links, $file ){
			if ( $file == $this->_file ) {
				// Add Widget Page link to our plugin
				$link = $this->getOptionsLink();
				array_unshift( $links, $link );

				// Add Support Forum link to our plugin
				$link = $this->getSupportForumLink();
				array_unshift( $links, $link );
			}
			return $links;
		}

		public function addPluginMetaLinks( $meta, $file ){
			if ( $file == $this->_file ) {
				// Add Widget Page link to our plugin
				$meta[] = $this->getDonateLink(__('Donate'));
			}
			return $meta;
		}

		public function getSupportForumLink( $linkText = '' ) {
			if ( empty($linkText) ) {
				$linkText = __( 'Support Forum', $this->_slug );
			}
			return '<a href="' . $this->getSupportForumUrl() . '">' . $linkText . '</a>';
		}

		public function getDonateLink( $linkText = '' ) {
			$url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=' . $this->_paypalButtonId;
			if ( empty($linkText) ) {
				$linkText = __( 'Donate to show your appreciation.', $this->_slug );
			}
			return "<a href='{$url}'>{$linkText}</a>";
		}

		public function getSupportForumUrl() {
			return 'http://xavisys.com/support/forum/'.$this->_slug;
		}

		public function getOptionsLink( $linkText = '' ) {
			if ( empty($linkText) ) {
				$linkText = __( 'Settings', $this->_slug );
			}
			return '<a href="' . $this->getOptionsUrl() . '">' . $linkText . '</a>';
		}

		public function getOptionsUrl() {
			return admin_url( 'options-general.php?page=' . $this->_hook );
		}

		public function optionsPageStyles() {
			if (isset($_GET['page']) && $_GET['page'] == $this->_hook) {
				wp_enqueue_style('dashboard');
				wp_enqueue_style('xavisys-options-css', plugin_dir_url( __FILE__ ) . 'xavisys-plugin-framework.css');
			}
		}

		public function addDefaultOptionsMetaBoxes() {
			add_meta_box( $this->_slug . '-like-this', __('Like this Plugin?', $this->_slug), array($this, 'likeThisMetaBox'), 'xavisys-' . $this->_slug, 'sidebar');
			add_meta_box( $this->_slug . '-support', __('Need Support?', $this->_slug), array($this, 'supportMetaBox'), 'xavisys-' . $this->_slug, 'sidebar');
			add_meta_box( $this->_slug . '-xavisys-feed', __('Latest news from Xavisys', $this->_slug), array($this, 'xavisysFeedMetaBox'), 'xavisys-' . $this->_slug, 'sidebar');
		}

		public function likeThisMetaBox() {
			echo '<p>';
			_e('Then please do any or all of the following:', $this->_slug);
			echo '</p><ul>';

			$url = apply_filters('xavisys-plugin-url-'.$this->_slug, 'http://xavisys.com/wordpress-plugins/'.$this->_slug);
			echo "<li><a href='{$url}'>";
			_e('Link to it so others can find out about it.', $this->_slug);
			echo "</a></li>";

			$url = 'http://wordpress.org/extend/plugins/' . $this->_slug;
			echo "<li><a href='{$url}'>";
			_e('Give it a good rating on WordPress.org.', $this->_slug);
			echo "</a></li>";

			echo '<li>' . $this->getDonateLink() . '</li>';

			echo '</ul>';
		}

		public function supportMetaBox() {
			echo '<p>';
			echo sprintf(__('If you have any problems with this plugin or ideas for improvements or enhancements, please use the <a href="%s">Xavisys Support Forums</a>.', $this->_slug), $this->getSupportForumUrl() );
			echo '</p>';
		}

		public function xavisysFeedMetaBox() {
			$args = array(
				'url'			=> 'http://xavisys.com/feed/',
				'items'			=> '5',
			);
			echo '<div class="rss-widget">';
			wp_widget_rss_output( $args );
			echo "</div>";
		}

		public function addDashboardWidgets() {
			wp_add_dashboard_widget( 'dashboardb_xavisys' , 'The Latest News From Xavisys' , array( $this, 'dashboardWidget' ) );
		}

		public function dashboardWidget() {
			$args = array(
				'url'			=> 'http://xavisys.com/feed/',
				'items'			=> '3',
				'show_date'		=> 1,
				'show_summary'	=> 1,
			);
			echo '<div class="rss-widget">';
			echo '<a href="http://xavisys.com"><img class="alignright" src="http://cdn.xavisys.com/logos/xavisys-logo-small.png" /></a>';
			wp_widget_rss_output( $args );
			echo '<p style="border-top: 1px solid #CCC; padding-top: 10px; font-weight: bold;">';
			echo '<a href="http://xavisys.com/feed/"><img src="'.get_bloginfo('wpurl').'/wp-includes/images/rss.png" alt=""/> Subscribe with RSS</a>';
			echo "</p>";
			echo "</div>";
		}

		public function screenIconLink($name = 'xavisys') {
			echo '<a href="http://xavisys.com">';
			screen_icon($name);
			echo '</a>';
		}


		public function optionsPageScripts() {
			if (isset($_GET['page']) && $_GET['page'] == $this->_hook) {
				wp_enqueue_script('postbox');
				wp_enqueue_script('dashboard');
			}
		}
	}
}
