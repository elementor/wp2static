<?php

namespace WP2Static;

// TODO: add option in UI to also write to PHP error_log
class WsLog {
    public static function createTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_export_log';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
            log TEXT NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function l( string $text ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_export_log';

        $wpdb->insert(
            $table_name,
            array(
                'log' => $text,
            )
        );
    }

    /**
     * Log multiple lines at once
     *
     * @param string[] $lines List of lines to log
     */
    public static function lines( array $lines ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_export_log';

        $current_time = current_time( 'mysql' );

        $query = "INSERT INTO $table_name (log) VALUES ";

        foreach ( $lines as $line ) {
            $query .= "('$line'),";
        }

        $query = rtrim( $query, ',' );

        $wpdb->query( $query );
    }

    public static function getAll() : array {
        global $wpdb;
        $logs = [];

        $table_name = $wpdb->prefix . 'wp2static_export_log';

        $rows = $wpdb->get_results( "SELECT time, log FROM $table_name ORDER BY id DESC" );

        foreach ( $rows as $row ) {
            $logs[] = $row;
        }

        return $logs;
    }
}

