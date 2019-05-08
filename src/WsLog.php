<?php

namespace WP2Static;

class WsLog {

    public static function l( $text ) {
        $target_settings = array(
            'general',
        );

        $site_info = new SiteInfo();
        $site_info = $site_info->get();

        $log_file_path = $site_info['uploads_path'] .
            'wp2static-working-files/EXPORT-LOG.txt';

        file_put_contents(
            $log_file_path,
            $text . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        chmod( $log_file_path, 0664 );
    }
}

