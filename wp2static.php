<?php
/**
 * Plugin Name: WP2Static
 * Plugin URI:  https://wp2static.com
 * Description: Security & Performance via static website publishing. One plugin to solve WordPress's biggest problems.
 * Version:     6.5.1
 * Author:      Leon Stafford
 * Author URI:  https://leonstafford.github.io
 * Text Domain: static-html-output-plugin
 *
 * @package     WP_Static_HTML_Output
 */


// intercept low latency dependent actions and avoid boostrapping whole plugin
require_once dirname( __FILE__ ) .
    '/library/StaticHtmlOutput/Dispatcher.php';

require_once 'library/StaticHtmlOutput/WP2Static.php';
require_once 'library/StaticHtmlOutput/Options.php';
require_once 'library/StaticHtmlOutput/TemplateHelper.php';
require_once 'library/StaticHtmlOutput/View.php';
require_once 'library/StaticHtmlOutput/WsLog.php';
require_once 'library/StaticHtmlOutput/FilesHelper.php';
require_once 'library/StaticHtmlOutput.php';
require_once 'library/URL2/URL2.php';

StaticHtmlOutput_Controller::init( __FILE__ );

function plugin_action_links( $links ) {
    $settings_link = '<a href="admin.php?page=wp2static">' . __( 'Settings', 'static-html-output-plugin' ) . '</a>';
    array_unshift( $links, $settings_link );

    return $links;
}


function wp_static_html_output_server_side_export() {
    $plugin = StaticHtmlOutput_Controller::getInstance();
    $plugin->doExportWithoutGUI();
    wp_die();
    return null;
}

add_action( 'wp_static_html_output_server_side_export_hook', 'wp_static_html_output_server_side_export', 10, 0 );


function plugins_have_been_loaded() {
      load_plugin_textdomain( 'static-html-output-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
      return null;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'plugin_action_links' );
add_action( 'plugins_loaded', 'plugins_have_been_loaded' );
add_action( 'wp_ajax_wp_static_html_output_ajax', 'wp_static_html_output_ajax' );

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

remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

function wp_static_html_output_add_dashboard_widgets() {

    wp_add_dashboard_widget(
        'wp_static_html_output_dashboard_widget',
        'Static HTML Output',
        'wp_static_html_output_dashboard_widget_function'
    );
}
// add_action( 'wp_dashboard_setup', 'wp_static_html_output_add_dashboard_widgets' );
function wp_static_html_output_dashboard_widget_function() {

    echo '<p>Publish whole site as static HTML</p>';
    echo "<button class='button button-primary'>Publish whole site</button>";
}

function wp_static_html_output_deregister_scripts() {
    wp_deregister_script( 'wp-embed' );
    wp_deregister_script( 'comment-reply' );
}
add_action( 'wp_footer', 'wp_static_html_output_deregister_scripts' );
remove_action( 'wp_head', 'wlwmanifest_link' );

// WP CLI support
if ( defined( 'WP_CLI' ) ) {
    require_once dirname( __FILE__ ) . '/library/wp2static-wp-cli-commands.php';
}
