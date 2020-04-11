<?php

// exit uninstall if not called by WP
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

global $wpdb;

$tables_to_drop = [
    'wp2static_core_options',
    'wp2static_crawl_cache',
    'wp2static_deploy_cache',
    'wp2static_jobs',
    'wp2static_log',
    'wp2static_urls',
];

foreach ( $tables_to_drop as $table ) {
    $table_name = $wpdb->prefix . $table;

    $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
}

// TODO: delete crawl_cache, processed_site and zip if exist

