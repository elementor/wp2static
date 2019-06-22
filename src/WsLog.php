<?php

namespace WP2Static;

class WsLog {

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
}

