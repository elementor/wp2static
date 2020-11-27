<?php
/**
 * Plugin Name: WP2Static
 * Plugin URI:  https://wp2static.com
 * Description: Static site generator functionality for WordPress.
 * Version:     7.1.5-dev
 * Author:      WP2Static
 * Author URI:  https://wp2static.com
 * Text Domain: wp2static
 *
 * @package     WP2Static
 */

if ( ! defined( 'ABSPATH' ) ) {
    die;
}

define( 'WP2STATIC_VERSION', '7.1.5-dev' );
define( 'WP2STATIC_PATH', plugin_dir_path( __FILE__ ) );

if ( file_exists( WP2STATIC_PATH . 'vendor/autoload.php' ) ) {
    require_once WP2STATIC_PATH . 'vendor/autoload.php';
}

function wp2static_print_composer_error() : void {
    echo '<div class="notice notice-error"><p>' .
        'Some error: use composer or download pre-compiled' .
        '</p></div>';
}

if ( ! class_exists( 'WP2Static\Controller' ) ) {
    add_action( 'admin_notices', 'wp2static_print_composer_error' );
    throw new Exception(
        'Some error: use composer or download pre-compiled'
    );
}

WP2Static\Controller::init( __FILE__ );

/**
 * Define Settings link for plugin
 *
 * @param string[] $links array of links
 * @return string[] modified array of links
 */
function plugin_action_links( $links ) {
    $settings_link =
        '<a href="admin.php?page=wp2static">' .
        __( 'Settings', 'static-html-output-plugin' ) .
        '</a>';
    array_unshift( $links, $settings_link );

    return $links;
}

add_filter(
    'plugin_action_links_' .
    plugin_basename( __FILE__ ),
    'plugin_action_links'
);

/**
 * Prevent WP scripts from loading which aren't useful
 * on a statically exported site
 */
function wp2static_deregister_scripts() : void {
    wp_deregister_script( 'wp-embed' );
    wp_deregister_script( 'comment-reply' );
}

add_action( 'wp_footer', 'wp2static_deregister_scripts' );

// TODO: move into own plugin for WP cleanup, don't belong in core
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

if ( defined( 'WP_CLI' ) ) {
    WP_CLI::add_command( 'wp2static', WP2Static\CLI::class );
    WP_CLI::add_command(
        'wp2static options',
        [ WP2Static\CLI::class, 'options' ]
    );
}

