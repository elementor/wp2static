<?php

namespace WP2Static;

class WsLog {

    public static function l( $text ) {
        $site_info = new SiteInfo();
        $site_info = $site_info->get();

        $log_dir = $site_info['uploads_path'] . 'wp2static-working-files';

        if ( ! is_dir( $log_dir ) ) {
            if ( ! mkdir( $log_dir ) ) {
                error_log('Unable to create WP2Static logging dir');
                return;
            }
        }

        $log_file_path = $log_dir . '/EXPORT-LOG.txt';

        file_put_contents(
            $log_file_path,
            $text . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        chmod( $log_file_path, 0664 );
    }
}

