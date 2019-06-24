<?php

namespace WP2Static;

class DeployCache {

    public static function createTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_deploy_cache';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            file_hash VARCHAR(32) NOT NULL,
            path_hash VARCHAR(2083) NOT NULL,
            PRIMARY KEY  (file_hash)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function addFileHash(
        string $local_path
    ) : void {
        global $wpdb;

        $path_hash = crc32( $local_path );
        $file_hash = crc32( file_get_contents( $local_path ) );

        $deploy_cache_table = $wpdb->prefix . 'wp2static_deploy_cache';
        $path_data = [ 'path_hash' => $path_hash, 'file_hash' => $file_hash ];
        $wpdb->insert( $deploy_cache_table, $path_data, [ '%s','%s' ] );
    }

    public static function fileisCached( string $local_file ) : bool {
        $path_hash = crc32( $local_path );
        $file_hash = crc32( file_get_contents( $local_path ) );

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
