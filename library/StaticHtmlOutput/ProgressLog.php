<?php

class ProgressLog {

    public static function l( $portion, $total ) {
        if ( $total === 0 ) {
            return;
        }

        $target_settings = array(
            'wpenv',
        );

        $wp_uploads_path = '';

        if ( defined( 'WP_CLI' ) ) {
            require_once dirname( __FILE__ ) .
                '/DBSettings.php';

            $settings = WPSHO_DBSettings::get( $target_settings );

            $wp_uploads_path = $settings['wp_uploads_path'];
        } else {
            $wp_uploads_path = $_POST['wp_uploads_path'];
        }

        $log_file_path = $wp_uploads_path . '/WP-STATIC-PROGRESS.txt';

        $progress_percent =
            floor( $portion / $total * 100 );

        error_log( $progress_percent . '(' . $portion . ' / ' . $total . ')' );

        file_put_contents(
            $log_file_path,
            $progress_percent . PHP_EOL,
            LOCK_EX
        );

        chmod( $log_file_path, 0664 );
    }
}

