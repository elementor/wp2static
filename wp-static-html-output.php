<?php
/*
Plugin Name: WP Static HTML Output
Plugin URI:  https://leonstafford.github.io
Description: Benefit from WordPress as a CMS but with the speed, performance and portability of a static site
Version:     2.3
Author:      Leon Stafford
Author URI:  https://leonstafford.github.io
Text Domain: static-html-output-plugin

Copyright (c) 2017 Leon Stafford
 */

// disable Dropbox lib less than PHP 7 envs
if (floatval(PHP_VERSION) >= 7) {
//    require_once 'library/dropboxsdk/autoload.php';
}
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

// allow triggering export via CRON/custom function
function wp_static_html_output_server_side_export() {
	// TODO: allow options in this function and update the add_action signature
	error_log('');
	error_log('************************');
	error_log('RUNNING EXPORT VIA CRON');
	error_log('************************');
	error_log('');

    $plugin = StaticHtmlOutput::getInstance();
    $plugin->doExportWithoutGUI();
    wp_die();
}	

// add_action( $tag, $function_to_add, $priority, $accepted_args );
add_action( 'wp_static_html_output_server_side_export_hook', 'wp_static_html_output_server_side_export', 10, 0 );

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
add_action( 'wp_ajax_ftp_prepare_export', 'ftp_prepare_export' );
add_action( 'wp_ajax_ftp_transfer_files', 'ftp_transfer_files' );
add_action( 'wp_ajax_bunnycdn_prepare_export', 'bunnycdn_prepare_export' );
add_action( 'wp_ajax_bunnycdn_transfer_files', 'bunnycdn_transfer_files' );
add_action( 'wp_ajax_netlify_do_export', 'netlify_do_export' );
add_action( 'wp_ajax_dropbox_do_export', 'dropbox_do_export' );
add_action( 'wp_ajax_s3_do_export', 's3_do_export' );


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

function ftp_prepare_export() {
    $plugin = StaticHtmlOutput::getInstance();
    $plugin->ftpPrepareExport();
    wp_die();
}

function ftp_transfer_files() {
    $plugin = StaticHtmlOutput::getInstance();
    $plugin->ftpTransferFiles();
    wp_die();
}

function bunnycdn_prepare_export() {
    $plugin = StaticHtmlOutput::getInstance();
    $plugin->bunnycdnPrepareExport();
    wp_die();
}

function bunnycdn_transfer_files() {
    $plugin = StaticHtmlOutput::getInstance();
    $plugin->bunnycdnTransferFiles();
    wp_die();
}

function netlify_do_export() {
    $plugin = StaticHtmlOutput::getInstance();
    $plugin->netlifyExport();
    wp_die();
}

function dropbox_do_export() {
    $plugin = StaticHtmlOutput::getInstance();
    $plugin->dropboxExport();
    wp_die();
}

function s3_do_export() {
    $plugin = StaticHtmlOutput::getInstance();
    $plugin->s3Export();
    wp_die();
}
