<?php
/*
Plugin Name: WP Static HTML Output
Plugin URI:  https://leonstafford.github.io
Description: Benefit from WordPress as a CMS but with the speed, performance and portability of a static site
Version:     2.2
Author:      Leon Stafford
Author URI:  https://leonstafford.github.io
Text Domain: static-html-output-plugin

Copyright (c) 2017 Leon Stafford
 */

// use dropbox sdk lib composer fake autoload
require_once 'library/dropboxsdk/autoload.php';
require_once 'library/Github/autoload.php';
require_once 'library/StaticHtmlOutput/Options.php';
require_once 'library/StaticHtmlOutput/View.php';
require_once 'library/StaticHtmlOutput/UrlRequest.php';
require_once 'library/StaticHtmlOutput.php';

StaticHtmlOutput::init(__FILE__);

function pluginActionLinks($links) {
	$settings_link = '<a href="tools.php?page=wp-static-html-output-options">' . __('Settings', 'static-html-output-plugin') . '</a>'; 
  	array_unshift( $links, $settings_link ); 
  	return $links; 	
}	

function initialise_localisation() {
    load_plugin_textdomain( 'static-html-output-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pluginActionLinks');
add_action('plugins_loaded', 'initialise_localisation');
add_action( 'wp_ajax_start_export', 'start_export' );
add_action( 'wp_ajax_github_upload_blobs', 'github_upload_blobs' );
add_action( 'wp_ajax_crawl_site', 'crawl_site' );
add_action( 'wp_ajax_save_options', 'save_options' );
add_action( 'wp_ajax_create_zip', 'create_zip' );
add_action( 'wp_ajax_github_finalise_export', 'github_finalise_export' );
add_action( 'wp_ajax_github_prepare_export', 'github_prepare_export' );

function save_options() {
    $plugin = StaticHtmlOutput::getInstance();
    $plugin->saveExportSettings();
    wp_die();
}

function crawl_site() {
    $plugin = StaticHtmlOutput::getInstance();
    $plugin->crawlTheWordPressSite();
    wp_die();
}

function create_zip() {
    $plugin = StaticHtmlOutput::getInstance();
    $plugin->createTheArchive();
    wp_die();
}

function start_export() {
    $plugin = StaticHtmlOutput::getInstance();
    $plugin->startExport();
    wp_die();
}

function github_prepare_export() {
    $plugin = StaticHtmlOutput::getInstance();
    $plugin->githubPrepareExport();
    wp_die();
}

function github_upload_blobs() {
    $plugin = StaticHtmlOutput::getInstance();
    $plugin->githubUploadBlobs();
    wp_die();
}

function github_finalise_export() {
    $plugin = StaticHtmlOutput::getInstance();
    $plugin->githubFinaliseExport();
    wp_die();
}
