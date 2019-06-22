<?php

namespace WP2Static;

class ExportLog {

    public static function createTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_export_log';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            log TEXT NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function addUrl( string $url ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_crawl_cache';

        $wpdb->insert(
            $table_name,
            array(
                'time' => current_time( 'mysql' ),
                'hashed_url' => md5( $url ),
            )
        );
    }

    // TODO: enable date filter as option/alternate method
    public static function getUrl( string $url ) : int {
        global $wpdb;

        $hashed_url = md5( $url );

        $table_name = $wpdb->prefix . 'wp2static_crawl_cache';

        $sql = $wpdb->prepare(
            "SELECT id FROM $table_name WHERE" .
            ' hashed_url = %s LIMIT 1',
            $hashed_url
        );

        $id = $wpdb->get_var( $sql );

        return (int) $id;
    }

    public static function rmUrl( string $url ) : void {
        global $wpdb;

        $hashed_url = md5( $url );

        $table_name = $wpdb->prefix . 'wp2static_crawl_cache';

        $wpdb->delete(
            $table_name,
            array(
                'hashed_url' => md5( $url ),
            )
        );
    }
}
