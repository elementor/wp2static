<?php
/**
 * Plugin Name: WP Static HTML Output
 * Plugin URI:  https://leonstafford.github.io
 * Description: The optimum solution to speed up and secure your WordPress site - export to static HTML and hide all traces of WordPress from your site!
 * Version:     3.1
 * Author:      Leon Stafford
 * Author URI:  https://leonstafford.github.io
 * Text Domain: static-html-output-plugin
 * Copyright (c) 2017 Leon Stafford
 * 
 * @fs_premium_only /library/FTP/, /library/Github/, /library/CloudFront/, /library/Psr/, /library/S3/, /library/GuzzleHttp/
 * 
 * @package     WP_Static_HTML_Output
 */

if ( ! function_exists( 'wpsho_fr' ) ) {

	function wpsho_fr() {
		global $wpsho_fr;

		if ( ! isset( $wpsho_fr ) ) {
			if ( ! defined( 'WP_FS__PRODUCT_2226_MULTISITE' ) ) {
				define( 'WP_FS__PRODUCT_2226_MULTISITE', true );
			}

			require_once dirname(__FILE__) . '/freemius/start.php';

			$wpsho_fr = fs_dynamic_init( array(
				'id'                  => '2226',
				'slug'                => 'static-html-output-plugin',
				'type'                => 'plugin',
				'public_key'          => 'pk_8874b676a9189a1b13450673a921f',
				'is_premium'          => true,
				// If your plugin is a serviceware, set this option to false.
				'has_premium_version' => true,
				'has_addons'          => false,
				'has_paid_plans'      => true,
				'menu'                => array(
					'slug'           => 'wp-static-html-output-options',
					'parent'         => array(
						'slug' => 'tools.php',
					),
				),
				// Set the SDK to work in a sandbox mode (for development & testing).
				// IMPORTANT: MAKE SURE TO REMOVE SECRET KEY BEFORE DEPLOYMENT.
				//'secret_key'          => getenv('FREEMIUM_SECRET_KEY'),
			) );
		}

		return $wpsho_fr;
	}

	// Init Freemius.
	wpsho_fr();
	// Signal that SDK was initiated.
	do_action( 'wpsho_fr_loaded' );


	// TODO: find way to enable these based on detected capabilities
	require_once 'library/StaticHtmlOutput/Options.php';
	require_once 'library/StaticHtmlOutput/View.php';
	require_once 'library/StaticHtmlOutput/WsLog.php';
	require_once 'library/StaticHtmlOutput/UrlRequest.php';
	require_once 'library/StaticHtmlOutput/FilesHelper.php';
	require_once 'library/StaticHtmlOutput/Netlify.php';
	require_once 'library/StaticHtmlOutput/BunnyCDN.php';
	require_once 'library/StaticHtmlOutput/FTP.php';
	require_once 'library/StaticHtmlOutput/GitHub.php';
	require_once 'library/StaticHtmlOutput/Dropbox.php';
	require_once 'library/StaticHtmlOutput/S3.php';
	require_once 'library/StaticHtmlOutput.php';

	StaticHtmlOutput_Controller::init( __FILE__ );

	/**
	 * Settings link for WP Static HTML Output plugin
	 *
	 * This creates the link(s) on the installed/active plugins screen
	 *
	 * @since 1.0.0
	 *
	 * @link https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
	 *
	 * @param array $links The links to show on the plugins overview page in an array.
	 * @return array The links to show on the plugins overview page in an array.
	 */
	function plugin_action_links( $links ) {
		$settings_link = '<a href="tools.php?page=wp-static-html-output-options">' . __( 'Settings', 'static-html-output-plugin' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}


	if ( wpsho_fr()->is_plan('professional_edition') ) {
		/**
		 * Allow triggering export via CRON/custom function
		 *
		 * @since 2.3
		 *
		 * @return null
		 */
		function wp_static_html_output_server_side_export() {
			$plugin = StaticHtmlOutput::getInstance();
			$plugin->doExportWithoutGUI();
			wp_die();
			return null;
		}

		add_action( 'wp_static_html_output_server_side_export_hook', 'wp_static_html_output_server_side_export', 10, 0 );
	}


	/**
	 * This hook is called once any activated plugins have been loaded. Is generally used for immediate filter setup, or plugin overrides.
	 *
	 * @since 1.0.0
	 *
	 * @return null
	 */
	function plugins_have_been_loaded() {
			load_plugin_textdomain( 'static-html-output-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			return null;
	}

	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'plugin_action_links' );
	add_action( 'plugins_loaded', 'plugins_have_been_loaded' );
	add_action( 'wp_ajax_wp_static_html_output_ajax', 'wp_static_html_output_ajax' );

	/**
	 * Routes AJAX requests from the client to plugin instance.
	 *
	 * Reduces code by not adding an add_action for each AJAX method. Instead, a parameter
	 * in the payload determines which of the plugin's instance methods to run
	 *
	 * @since 2.5
	 *
	 * @return null
	 */
	function wp_static_html_output_ajax() {
		check_ajax_referer( 'wpstatichtmloutput', 'nonce' );
		$instance_method = filter_input( INPUT_POST, 'ajax_action' );

		if ( '' !== $instance_method && is_string( $instance_method ) ) {
			$plugin_instance = StaticHtmlOutput_Controller::getInstance();
			call_user_func( array( $plugin_instance, $instance_method ) );
		}

		wp_die();
		return null;
	}

	// rm wp emoji
	remove_action('wp_head', 'print_emoji_detection_script', 7);
	remove_action('wp_print_styles', 'print_emoji_styles');

	/**
	 * Add a widget to the dashboard.
	 *
	 * Enable users to statically publish theeir site from the WP Dashboard
	 */
	function wp_static_html_output_add_dashboard_widgets() {

		wp_add_dashboard_widget(
					 'wp_static_html_output_dashboard_widget',
					 'Static HTML Output',
					 'wp_static_html_output_dashboard_widget_function'
			);	
	}
	//add_action( 'wp_dashboard_setup', 'wp_static_html_output_add_dashboard_widgets' );

	function wp_static_html_output_dashboard_widget_function() {

		echo "<p>Publish whole site as static HTML</p>";
		echo "<button class='button button-primary'>Publish whole site</button>";
	}

	function wp_static_html_output_deregister_scripts(){
		wp_deregister_script( 'wp-embed' );
		wp_deregister_script( 'comment-reply' );
	}
	add_action( 'wp_footer', 'wp_static_html_output_deregister_scripts' );
	remove_action('wp_head', 'wlwmanifest_link');

	function wpsho_fr_custom_connect_message_on_update(
			$message,
			$user_first_name,
			$plugin_title,
			$user_login,
			$site_link,
			$freemius_link
		) {
			return sprintf(
				__( 'Want better exports? %2$s improves by sending non-sensitive diagnostics to %5$s.', 'static-html-output-plugin' ),
				$user_first_name,
				'<b>' . $plugin_title . '</b>',
				'<b>' . $user_login . '</b>',
				$site_link,
				$freemius_link
			);
		}

		wpsho_fr()->add_filter('connect_message_on_update', 'wpsho_fr_custom_connect_message_on_update', 10, 6);

		wpsho_fr()->add_filter('connect_message', 'wpsho_fr_custom_connect_message_on_update', 10, 6);

}
