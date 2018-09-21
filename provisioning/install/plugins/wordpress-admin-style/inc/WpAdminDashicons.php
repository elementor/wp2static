<?php
// avoid direct calls to this file, because now WP core and framework has been used.
! defined( 'ABSPATH' ) and exit;

add_action( 
	'init',
	array( WpAdminDashicons::get_instance(), 'plugin_setup' )
);

class WpAdminDashicons {
	
	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 */
	protected static $instance;
	
	/**
	 * Access this plugin’s working instance
	 *
	 * @wp-hook admin_init
	 * @since   05/02/2013
	 */
	public static function get_instance() {
		
		NULL === self::$instance and self::$instance = new self;
		
		return self::$instance;
	}
	
	/**
	 * Used for regular plugin work.
	 * 
	 * @wp-hook  admin_init
	 * @since    05/02/2013
	 * @return   void
	 */
	public function plugin_setup() {
		
		add_action( 'admin_menu', array( $this, 'register_submenu' ) );
		
	}
	
	/**
	* Constructor.
	* Intentionally left empty and public.
	*
	* @see    plugin_setup()
	* @since  05/02/2013
	*/
	public function __construct() {}
	
	public function register_submenu() {
		
		$hook = add_submenu_page(
			'WordPress_Admin_Style',
			__( 'Dashicons' ),
			__( 'Dashicons' ),
			'manage_options',
			'dashicons',
			array( $this, 'get_dashicon_demo' )
		);
		add_action( 'load-' . $hook, array( $this, 'register_scripts' ) );
	}
	
	public function get_dashicon_demo() {
		?>
		<div class="wrap">
		<h1>Dashicons</h1>
		<p>Dashicons icon font for MP6, currently in development and will go live in WordPress version 3.8.<br>You can check out the WordPress Version 3.8-alpha or the MP6-plugin at <a href="http://wordpress.org/extend/plugins/mp6/">http://wordpress.org/extend/plugins/mp6/</a>.</p>

		<h2>MiniMenu</h2>
		<ul>
			<li><a href="#iconpicker">Iconpicker</a></li>
			<li><a href="#instructions">Instructions</a></li>
			<li><a href="#instructions">Photoshop Usage</a></li>
			<li><a href="#htmlusage">HTML Usage</a></li>
			<li><a href="#cssusage">CSS Usage</a></li>
			<li><a href="#offcialpage">Official Dashicons Page</a></li>
			<li><a href="#alternatives">Alternatives Font Awesome</a></li>
		</ul>

		<h2 id="iconpicker">Iconpicker for Dashicons</h2>
		<div>
			<label for="dashicons_picker_icon"><?php _e( 'Icon' ); ?></label>
			<input class="regular-text" type="text" id="dashicons_picker_icon" name="dashicons_picker_settings[icon]" value="<?php if( isset( $options['icon'] ) ) { echo esc_attr( $options['icon'] ); } ?>"/>
			<input type="button" data-target="#dashicons_picker_icon" class="button dashicons-picker" value="pick" />
		</div>

		<div id="instructions">
	
			<h2>Photoshop Usage</h2>
			<p>Use the .OTF version of the font for Photoshop mockups, the web-font versions won't work. For most accurate results, pick the "Sharp" font smoothing.</p>

			<h2 id="htmlusage">HTML Usage</h2>
			<p>Use the follow html as example and load the stylesheet.</p>
			<p><code>&lt;span class="dashicons dashicons-admin-media"&gt;&lt;/span&gt;</code></p>
			<p>You should see this result: <span class="dashicons dashicons-admin-media"></span></p>
			
			<h2 id="cssusage">CSS Usage</h2>
			<p>Link the stylesheet:</p>
			<pre>&lt;link rel="stylesheet" href="css/dashicons.css"></pre>
			<p>Now add the icons using the <code>:before</code> selector. You can insert the Star icon like this:</p>
		
			<pre><code class="language-css">.myicon:before {
	content: '\2605';
	display: inline-block;
	-webkit-font-smoothing: antialiased;
	font: normal 16px/1 'dashicons';
	vertical-align: top;
}</code></pre>
			<h3>Alternative Selectors</h3>
			<p>For custom post types replace <em>{post_type}</em> with the slug name passed to <code>register_post_type()</code>.<br>
			<code>#menu-posts-{post_type} .wp-menu-image:before</code></p>
			<p>For top level menu pages replace <em>{menu-slug}</em> with the slug name passed to <code>add_menu_page()</code>.<br>
			<code>#toplevel_page_{menu-slug} .wp-menu-image:before</code></p>

			<h2 id="offcialpage">The official Dashicons Page</h2>
			<p>See also the official <a href="https://developer.wordpress.org/resource/dashicons/">Dashicons Page</a> for more comfort or helpful information.</p>

			<h2 id="alternatives">Alternatives</h2>
			<h3>Font Awesome</h3>
			<p>Alternative you can use another icon font, like Font Awesome.<br>
			Include the font via function, the file was enqueued via the bootstrap CDN. Alternative use your custom URL form the plugin.</p>
			<pre><code class="language-php">
add_action( 'admin_enqueue_scripts', 'enqueue_font_awesome' );
function enqueue_font_awesome() {
	wp_enqueue_style(
		'font-awesome',
		'//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css',
		FALSE,
		NULL
	);
}
</code></pre>
			<p>And now set the icon via CSS.</p>
			<pre><code class="language-css">.myicon:before {
	content: '\f07a';
	font-family: FontAwesome !important;
}</code></pre>
			<p>See more hints and the icons on the <a href="http://fontawesome.io/">official page of Font Awesome</a>.</p>
		</div>
		<?php
	}
	
	public function register_scripts() {
		
		wp_register_style( 'dashicons-demo',
			plugin_dir_url( __FILE__ ) . '../css/dashicons-demo.css',
			array( 'dashicons' )
		);
		wp_enqueue_style ( 'dashicons-demo' );
		
		wp_register_script(
			'dashicons-picker',
			plugin_dir_url( __FILE__ ) . '../js/dashicons-picker.js',
			array( 'jquery' ),
			FALSE,
			TRUE
		);
		wp_enqueue_script( 'dashicons-picker' );
	}
} // end class
