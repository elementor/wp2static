<?php

namespace WP2Static;

class WsLog {

    public static function l( string $text ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_export_log';

        $wpdb->insert(
            $table_name,
            array(
                'time' => current_time( 'mysql' ),
                'log' => $text,
            )
        );
    }
}

