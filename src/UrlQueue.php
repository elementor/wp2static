<?php

namespace WP2Static;

class UrlQueue {

    public static function createTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url VARCHAR(2083) NOT NULL,
            status VARCHAR(10),
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function addUrl( string $url ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $wpdb->insert(
            $table_name,
            array(
                'url' => $url,
            )
        );
    }
}
