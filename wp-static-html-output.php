<?php
/**
 * Plugin Name: WP Static HTML Output
 * Plugin URI:  https://leonstafford.github.io
 * Description: The optimum solution to speed up and secure your WordPress site - export to static HTML and hide all traces of WordPress from your site!
 * Version:     2.6.4
 * Author:      Leon Stafford
 * Author URI:  https://leonstafford.github.io
 * Text Domain: static-html-output-plugin
 * Copyright (c) 2017 Leon Stafford

 * @package     WP_Static_HTML_Output
 */

// TODO: find way to enable these based on detected capabilities
require_once 'library/StaticHtmlOutput/Options.php';
require_once 'library/StaticHtmlOutput/View.php';
require_once 'library/StaticHtmlOutput/UrlRequest.php';
require_once 'library/StaticHtmlOutput.php';

StaticHtmlOutput::init( __FILE__ );

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
		$plugin_instance = StaticHtmlOutput::getInstance();
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

