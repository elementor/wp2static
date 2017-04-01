<?php
/*
Plugin Name: WP Static HTML Output
Plugin URI:  https://leonstafford.github.io
Description: Benefit from WordPress as a CMS but with the speed, performance and portability of a static site
Version:     1.9
Author:      Leon Stafford
Author URI:  https://leonstafford.github.io
Text Domain: static-html-output-plugin

Copyright (c) 2017 Leon Stafford
 */

require_once 'library/StaticHtmlOutput/Exception.php';
require_once 'library/StaticHtmlOutput/Options.php';
require_once 'library/StaticHtmlOutput/View.php';
require_once 'library/StaticHtmlOutput/UrlRequest.php';
require_once 'library/StaticHtmlOutput.php';

StaticHtmlOutput::init(__FILE__);

function pluginActionLinks($links) 
{
	$settings_link = '<a href="tools.php?page=wp-static-html-output-options">' . __('Settings', 'static-html-output-plugin') . '</a>'; 
  	array_unshift( $links, $settings_link ); 
  	return $links; 	
}	

function initialise_localisation() {
    load_plugin_textdomain( 'static-html-output-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pluginActionLinks');
add_action('plugins_loaded', 'initialise_localisation');
add_action( 'wp_ajax_generate_archive', 'generate_archive' );

function generate_archive() {
    $plugin = StaticHtmlOutput::getInstance();
    $plugin->genArch();
    wp_die();
}
