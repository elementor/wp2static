<?php

class WsLog {

    public static function l( $text ) {
        error_log( $text . PHP_EOL );

        $wp_uploads_path = $_POST['wp_uploads_path'];

        $working_directory =
            isset( $_POST['workingDirectory'] ) ?
            $_POST['workingDirectory'] :
            $wp_uploads_path;

        $log_file_path = $working_directory . '/WP-STATIC-EXPORT-LOG';

        file_put_contents(
            $log_file_path,
            $text . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}

