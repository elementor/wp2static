<?php

namespace WP2Static;

class WsLog {

    public static function l( $text ) {
        $target_settings = array(
            'general',
            'wpenv',
        );

        $wp_uploads_path = '';
        $settings = '';

        if ( defined( 'WP_CLI' ) ) {
            $settings = DBSettings::get( $target_settings );
        } else {
            $settings = PostSettings::get( $target_settings );
        }

        // NOTE: should be checked before sending to speed up requests
        // extra check here until old WsLog calls updated
        if ( ! isset( $settings['debug_mode'] ) ) {
            return;
        }

        $wp_uploads_path = $settings['wp_uploads_path'];

        $log_file_path = $wp_uploads_path .
            '/wp2static-working-files/EXPORT-LOG.txt';

        file_put_contents(
            $log_file_path,
            $text . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        chmod( $log_file_path, 0664 );
    }
}

