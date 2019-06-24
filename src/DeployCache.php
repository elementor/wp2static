<?php

namespace WP2Static;

class DeployCache {

    public static function createTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_deploy_cache';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            path_hash CHAR(32) NOT NULL,
            file_hash CHAR(32) NOT NULL,
            PRIMARY KEY  (path_hash)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function addFile(
        string $local_path
    ) : void {
        global $wpdb;

        $deploy_cache_table = $wpdb->prefix . 'wp2static_deploy_cache';

        $path_hash = md5( $local_path );
        $file_hash = md5( file_get_contents( $local_path ) );

        $sql = "INSERT INTO {$deploy_cache_table} (path_hash,file_hash)" .
            " VALUES (%s,%s) ON DUPLICATE KEY UPDATE file_hash = %s";

        $sql = $wpdb->prepare( $sql, $path_hash, $file_hash, $file_hash );

        $wpdb->query($sql); 
    }

    public static function fileisCached( string $local_path ) : bool {
        global $wpdb;

        $path_hash = md5( $local_path );
        $file_hash = md5( file_get_contents( $local_path ) );

        $table_name = $wpdb->prefix . 'wp2static_deploy_cache';

        $sql = $wpdb->prepare(
            "SELECT path_hash FROM $table_name WHERE" .
            ' path_hash = %s AND file_hash = %s LIMIT 1',
            $path_hash,
            $file_hash
        );

        $hash = $wpdb->get_var( $sql );

        return (bool) $hash;
    }

    public static function truncate() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_deploy_cache';

        $wpdb->query( "TRUNCATE TABLE $table_name" );
    }
}
