<?php
/**
 * Plugin Name: WP2Static
 * Plugin URI:  https://wp2static.com
 * Description: Static site generator functionality for WordPress.
 * Version:     7.0-alpha-006
 * Author:      WP2Static
 * Author URI:  https://wp2static.com
 * Text Domain: static-html-output-plugin
 *
 * @package     WP_Static_HTML_Output
 */

define( 'WP2STATIC_PATH', plugin_dir_path( __FILE__ ) );

if ( file_exists( WP2STATIC_PATH . 'vendor/autoload.php' ) ) {
  require_once WP2STATIC_PATH . 'vendor/autoload.php';
}

WP2Static\Controller::init( __FILE__ );

function plugin_action_links( $links ) {
    $settings_link =
        '<a href="admin.php?page=wp2static">' .
        __( 'Settings', 'static-html-output-plugin' ) .
        '</a>';
    array_unshift( $links, $settings_link );

    return $links;
}

function wp_static_html_output_server_side_export() {
    $plugin = WP2Static\Controller::getInstance();
    $plugin->doExportWithoutGUI();
    wp_die();
    return null;
}

add_action(
    'wp_static_html_output_server_side_export_hook',
    'wp_static_html_output_server_side_export',
    10,
    0
);

add_filter(
    'plugin_action_links_' .
    plugin_basename( __FILE__ ),
    'plugin_action_links'
);

add_action(
    'wp_ajax_wp_static_html_output_ajax',
    'wp_static_html_output_ajax'
);

function wp_static_html_output_deregister_scripts() {
    wp_deregister_script( 'wp-embed' );
    wp_deregister_script( 'comment-reply' );
}

add_action( 'wp_footer', 'wp_static_html_output_deregister_scripts' );

// TODO: move into own plugin for WP cleanup, don't belong in core
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

if ( defined( 'WP_CLI' ) ) {
    WP_CLI::add_command( 'wp2static', 'WP2Static\CLI' );
    WP_CLI::add_command(
        'wp2static options',
        [ 'WP2Static\CLI', 'options' ]
    );
}

