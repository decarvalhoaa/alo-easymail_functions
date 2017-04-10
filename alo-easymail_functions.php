<?php

/**
 * ALO Easymail Newsletter custom functions wrapper class
 *
 * @version 1.0
 *
 * @author Antonio de Carvalho <decarvalhoaa@gmail.com>
 * @link https://github.com/decarvalhoaa/alo-em-custom-functions
 */
if ( !class_exists( 'ALO_EasyMail_Custom_Functions' ) ):
class ALO_EasyMail_Custom_Functions {
	
	/**
     * The single instance of the class.
     * @var	object
     * @access	private
     * @since	1.0
     */
    private static $_instance = null;
	
	/**
     * The token.
     * @var     string
     * @access  public
     * @since   1.0
     */
    public $token;
	
	/**
     * The version number
     * 
     * @var     string
     * @access  public
     * @since   1.0
     */
    public $version;
	
	/**
     * The strings needing translation.
     * 
     * @var	array
     * @access	public
     * @since	1.0
     */
	public $strings;
	
	/**
	 * Custom endpoint name.
	 *
	 * @var string
	 */
	public static $endpoint = 'newsletter';
	
	
	/**
     * Main class instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @since 1.0
     * @static
     * @return Main class instance
     */
    public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
    }
	
	/**
     * Cloning is forbidden
     *
     * @since 1.0
     */
    public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0' );
    }

    /**
     * Unserializing instances of this class is forbidden
     *
     * @since 1.0
     */
    public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0' );
    }
	
	/**
     * Log the plugin version number
     *
     * @access  private
     * @since   1.0
     * @return  void
     */
    private function _log_version_number() {
		update_option( $this->token . '-version', $this->version );
    }

	/**
     * The construct
     */
    public function __construct() {
		/**
		 * Init
		 */
		$this->init();
		
		/**
		 * My Account endpoint
		 *
		 * @since: 1.0
		 */
		$this->my_account_endpoint();
		
		/**
		 * Register newsletter strings for translation with Polylang
		 *
		 * @since: 1.0
		 */
		add_action( 'plugins_loaded', array( $this, 'translate_newsletter_strings' ) );
		
		/**
		 * Custom latest posts placeholder: [LATEST-POSTS]
		 *
		 * Adds a new setting to the [LATEST-POSTS] placeholder settings to exclude the
		 * selected post from tthe latest posts list.
		 *
		 * @since: 1.0
		 */
		add_action( 'plugins_loaded', array( $this, 'custom_latest_posts_placeholder' ) );
		
		/**
		 * Adds the new custom post placeholders: [POST-URL], [POST-TITLE-TXT]
		 *
		 * @since: 1.0
		 */
		add_action( 'plugins_loaded', array( $this, 'custom_post_placeholders' ) );
	}
	
	/**
	 * Init
	 *
	 * @since: 1.0
	 */
	public function init() {
		$this->strings = array(
			'read_now'				=> 'Read Now',
			'more_title'			=> 'More from our Blog',
			'more_button'			=> 'Read more from our Blog',
			'footer_copyright'		=> 'This email was sent with <font style="color:#ee6062;">&hearts;</font> by &copy; <a href="[SITE-URL]" target="new" class="links" style="margin:0;clear:both;text-align:center;line-height:24px;font-size:13px;color:#888;text-decoration:none;" >[SITE-NAME]</a>',
			'footer_sender_id'		=> 'Owner: Antonio de Carvalho<br>An Der Fest 10, 40882 Ratingen, Germany<br>Tel.: +49 2102 5356836 | Email: info@thelittlecraft.com<br>',
			'footer_unsubscribe'	=> 'If you no longer want to receive messages from us, you can <u><a href="[USER-UNSUBSCRIBE-URL]" style="color:#aaa;font-size:12px;text-decoration:none;">unsubscribe here.</a></u>'
		);
		$this->token   = 'alo-em-custom-func';
		$this->version = '1.0';
		$this->_log_version_number();
	}
	
	/**
	 * Initialize endpoint
	 *
	 * @since: 1.0
	 */
	public function my_account_endpoint() {
		// Actions used to insert a new endpoint in the WordPress
		add_action( 'init', array( $this, 'add_endpoints' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );

		// Change the My Accout page title
		add_filter( 'the_title', array( $this, 'endpoint_title' ) );

		// Insering your new tab/page into the My Account page
		add_filter( 'woocommerce_account_menu_items', array( $this, 'new_menu_items' ) );
		add_action( 'woocommerce_account_' . self::$endpoint .  '_endpoint', array( $this, 'endpoint_content' ) );
		
		// Remove newsletter widget for logged-in users
		add_filter( 'sidebars_widgets', array( $this, 'hide_widget' ) );
	}
	
	/**
	 * Register new endpoint to use inside My Account page
	 *
	 * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
	 */
	public function add_endpoints() {
		add_rewrite_endpoint( self::$endpoint, EP_ROOT | EP_PAGES );
	}

	/**
	 * Add new query var
	 *
	 * @param array $vars
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = self::$endpoint;

		return $vars;
	}

	/**
	 * Set endpoint title
	 *
	 * @param string $title
	 * @return string
	 */
	public function endpoint_title( $title ) {
		global $wp_query;

		$is_endpoint = isset( $wp_query->query_vars[ self::$endpoint ] );

		if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
			// New page title.
			$title = __( 'Newsletter', 'woocommerce' );

			remove_filter( 'the_title', array( $this, 'endpoint_title' ) );
		}

		return $title;
	}

	/**
	 * Insert the new endpoint into the My Account menu
	 *
	 * @param array $items
	 * @return array
	 */
	public function new_menu_items( $items ) {
		// Remove the logout menu item.
		$logout = $items['customer-logout'];
		unset( $items['customer-logout'] );

		// Insert your custom endpoint.
		$items[ self::$endpoint ] = __( 'Newsletter', 'woocommerce' );

		// Insert back the logout item.
		$items['customer-logout'] = $logout;

		return $items;
	}

	/**
	 * Endpoint HTML content
	 */
	public function endpoint_content() {
		//echo '<p>Hello World!</p>';
		//echo do_shortcode( '[ALO-EASYMAIL-PAGE]' );
		$instance = array( 'title' => '');
		$args = array(
			'before_widget' => '',
			'after_widget'	=> '',
			'before_title' 	=> '',
			'after_title'	=> ''
		);
		the_widget( 'ALO_Easymail_Widget', $instance, $args );
	}
	
	/**
	 * Hide newsletter widget for logged-in users
	 *
	 * @param	array	$widgets	All active widgets
	 * @return	array	New list of active widgets
	 */
	public function hide_widget( $all_widgets ) {
		if ( is_user_logged_in() && ! is_admin() ) {
			foreach ( $all_widgets as $widget_area => $widgets ) {
				foreach ( $widgets as $key => $widget ) {
					if ( strpos( $widget, 'alo-easymail-widget' ) !== false ) {
						unset( $all_widgets[ $widget_area ][ $key ] );
					}
				}
			}	
		}
		return $all_widgets;
	}

	/**
	 * Plugin install action
	 * 
	 * Flush rewrite rules to make our custom endpoint available.
	 */
	public static function install() {
		$option = get_option( $this->token . '-version' );
		if ( $option != $this->version )
			flush_rewrite_rules();
	}
	
	/**
	 * Register newsletter strings for translation with Polylang
	 */
	public function translate_newsletter_strings() {
		if ( class_exists('Polylang') ) {
			foreach ( $this->strings as $name => $string ) {
				$multiline = strlen( $string ) > 50 ? true : false;
				pll_register_string( $name, $string, 'Newsletters', $multiline );
			}
		}
	}

	/**
	 * Custom latest posts placeholder
	 */
	public function custom_latest_posts_placeholder() {
		add_action( 'alo_easymail_newsletter_placeholders_title_custom_latest', array( $this, 'add_exclude_post_setting_placeholder_latest_posts' ), 10, 1 );
		add_action( 'alo_easymail_save_newsletter_meta_extra', array( $this, 'save_exclude_post_setting_placeholder_latest_posts' ), 10, 1 );
		
		remove_filter( 'alo_easymail_newsletter_content',  'alo_em_placeholders_get_latest', 10 );
		add_filter ( 'alo_easymail_newsletter_content',  array( $this, 'custom_placeholders_get_latest_posts' ), 10, 4 );
	}
	
	/**
	 * Add exclude post setting in Latest Posts placeholders table
	 * 
	 * @param	int		$post_id	ID of the newsletter.
	 */
	public function add_exclude_post_setting_placeholder_latest_posts( $post_id ) {
		$checked_custom_latest_exclude = ( get_post_meta ( $post_id, '_placeholder_custom_latest_exclude', true ) != '0' ) ? 'checked': '';
		
		echo '<br />';
		echo ' <input type="checkbox" name="placeholder_custom_latest_exclude" id="placeholder_custom_latest_exclude" value="1" style="margin:0 8px 0 0 !important;" ' . $checked_custom_latest_exclude . '>';
		echo __('Exclude newsletter post', 'alo-easymail');
	}
	
	/**
	 * Save exclude setting latest posts number when the newsletter is saved
	 *
	 * @param	int		$post_id	ID of the newsletter.
	 */
	public function save_exclude_post_setting_placeholder_latest_posts( $post_id ) {
		if ( isset( $_POST['placeholder_custom_latest_exclude'] ) && $_POST['placeholder_custom_latest_exclude'] == '1' && isset( $_POST['placeholder_easymail_post'] ) && is_numeric( $_POST['placeholder_easymail_post'] ) ) {
			update_post_meta ( $post_id, '_placeholder_custom_latest_exclude', $_POST['placeholder_easymail_post'] );
		} else {
			update_post_meta ( $post_id, '_placeholder_custom_latest_exclude', '0' );
		}
	}
	
	/**
	 * Replace the [LATEST-POSTS] placeholder when the newsletter is sending
	 * 
	 * @param	string		$content		The newsletter text
	 * @param	object		$newsletter		Newsletter object, with all post values
	 * @param	object		$recipient		Recipient object, with following properties: ID (int), newsletter (int: recipient ID), email (str), result (int: 1 if successfully sent or 0 if not), lang (str: 2 chars), unikey (str), name (str: subscriber name), user_id (int/false: user ID if registered user exists), subscriber (int: subscriber ID), firstname (str: firstname if registered user exists, otherwise subscriber name)
	 * @param	boolean    	$stop_recursive_the_content		If apply "the_content" filters: useful to avoid recursive and infinite loop
	 *
	 * @return	string		The newsletter text
	 */ 
	public function custom_placeholders_get_latest_posts( $content, $newsletter, $recipient, $stop_recursive_the_content=false ) {
		if ( !is_object( $recipient ) ) $recipient = new stdClass();
		if ( empty( $recipient->lang ) ) $recipient->lang = alo_em_short_langcode ( get_locale() );
		$limit = get_post_meta ( $newsletter->ID, '_placeholder_custom_latest', true );
		$categ = get_post_meta ( $newsletter->ID, '_placeholder_custom_latest_cat', true );
		$exclude = get_post_meta ( $newsletter->ID, '_placeholder_custom_latest_exclude', true );
		$latest = "";
		if ( $limit ) {
			$args = array( 'numberposts' => $limit, 'order' => 'DESC', 'orderby' => 'date', 'lang' => $recipient->lang );
			if ( (int)$categ > 0 ) $args['category'] = $categ;
			if ( (int)$exclude > 0 ) $args['exclude'] = $exclude;
			$myposts = get_posts( $args );
			if ( $myposts ) :
				$latest .= "<table border='0' cellspacing='0' cellpadding='0'>\r\n";
				$latest .= 		"<tbody>\r\n";
				foreach( $myposts as $post ) :	// setup_postdata( $post );
					$post_title = stripslashes ( alo_em_translate_text ( $recipient->lang, $post->post_title, $post->ID, 'post_title' ) );
	
					$post_link = alo_em_translate_url( $post->ID, $recipient->lang );
					$trackable_post_link = alo_em_make_url_trackable ( $recipient, $post_link );
					
					$read_more = function_exists('pll_translate_string') ? pll_translate_string( $this->strings['read_now'], $recipient->lang ) : $this->strings['read_now'];
					
					$latest .= "<tr><td style='color:#5f6062;font-size:19px;font-weight:700;line-height:24px;padding:5px 0;'>";
					$latest .= 		"<font face=\"'Helvetica Neue', Helvetica, Arial, sans-serif;\">". $post_title ."</font>\r\n";
					$latest .= "</td></tr>\r\n";
					$latest .= "<tr><td style='color:#5f6062;font-size:16px;font-weight:500;line-height:22px;padding:5px 0 15px;'>\r\n";
					$latest .= 		"<font face=\"'Helvetica Neue', Helvetica, Arial, sans-serif;\">\r\n";
					$latest .= 			"<a style='text-decoration:none;' href='". $trackable_post_link . "'><span style='color:#ee6062;border-bottom:1px solid #ee6062;'>". $read_more ."</span></a>\r\n";
					$latest .= 		"</font>\r\n";
					$latest .= "</td></tr>\r\n";
				endforeach;
				$latest .= 		"</tbody>\r\n";
				$latest .= "</table>\r\n";
			endif;	     
		} 
		$content = str_replace( '[LATEST-POSTS]', $latest, $content );
	   
		return $content;	
	}
	
	/**
	 * Adds the new post placeholders: [POST-URL], [POST-TITLE-TXT]
	 */
	public function custom_post_placeholders() {
		add_filter ( 'alo_easymail_newsletter_placeholders_table', array( $this, 'add_custom_post_easymail_placeholder' ), 10, 1 );
		add_filter ( 'alo_easymail_newsletter_content',  array( $this, 'custom_placeholders_get_post' ), 10, 4 );
	}
	
	/**
	 * Add [POST-URL] placeholder to table in new/edit newsletter screen
	 *
	 * @param	array		$placeholders	Placeholders array
	 *
	 * @return	array		The placeholders array
	 */
	public function add_custom_post_easymail_placeholder( $placeholders ) {
		if ( isset( $placeholders['easymail_post'] ) && isset( $placeholders['easymail_post']['tags'] ) ) {
			$placeholders['easymail_post']['tags']['[POST-URL]'] = __( 'URL to the selected post', 'alo-easymail' ).'. '.__( 'The visit to this url will be tracked.', 'alo-easymail' );
			$placeholders['easymail_post']['tags']['[POST-TITLE-TXT]'] = __( 'Title of the selected post.', 'alo-easymail' );
		}
		
		return $placeholders;
	}
	
	/**
	 * Replace the [POST-URL] and [POST-TITLE-TXT] and  placeholder when the newsletter is sending
	 * 
	 * @param	string		$content		The newsletter text
	 * @param	object		$newsletter		Newsletter object, with all post values
	 * @param	object		$recipient		Recipient object, with following properties: ID (int), newsletter (int: recipient ID), email (str), result (int: 1 if successfully sent or 0 if not), lang (str: 2 chars), unikey (str), name (str: subscriber name), user_id (int/false: user ID if registered user exists), subscriber (int: subscriber ID), firstname (str: firstname if registered user exists, otherwise subscriber name)
	 * @param	boolean    	$stop_recursive_the_content		If apply "the_content" filters: useful to avoid recursive and infinite loop
	 *
	 * @return	string		The newsletter text
	 */ 
	public function custom_placeholders_get_post( $content, $newsletter, $recipient, $stop_recursive_the_content=false ) {  
		if ( !is_object( $recipient ) ) $recipient = new stdClass();
		if ( empty( $recipient->lang ) ) $recipient->lang = alo_em_short_langcode ( get_locale() );
		$post_id = get_post_meta ( $newsletter->ID, '_placeholder_easymail_post', true );
		$obj_post = ( $post_id ) ? get_post( $post_id ) : false;
	
		if ( $obj_post ) {
			$post_title = stripslashes ( alo_em_translate_text ( $recipient->lang, $obj_post->post_title, $post_id, 'post_title' ) );
	
			$post_link = alo_em_translate_url( $obj_post->ID, $recipient->lang );
			$trackable_post_link = alo_em_make_url_trackable ( $recipient, $post_link );
	
			$content = str_replace( '[POST-URL]', $trackable_post_link, $content );
			$content = str_replace( '[POST-TITLE-TXT]', $post_title, $content );
		} else {
			$content = str_replace( '[POST-URL]', '', $content );
			$content = str_replace( '[POST-TITLE-TXT]', '', $content );
		}
	   
		return $content;	
	}
}
endif;

new ALO_EasyMail_Custom_Functions();
/* EOF */
