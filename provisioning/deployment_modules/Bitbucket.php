<?php

class StaticHtmlOutput_BitBucket extends StaticHtmlOutput_SitePublisher {

    public function __construct() {
        $target_settings = array(
            'general',
            'wpenv',
            'bitbucket',
            'advanced',
        );

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $target_settings );
        } else {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/DBSettings.php';

            $this->settings = WPSHO_DBSettings::get( $target_settings );
        }

        list($this->user, $this->repository) = explode(
            '/',
            $this->settings['bbRepo']
        );

        $this->export_file_list =
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-EXPORT-BITBUCKET-FILES-TO-EXPORT.txt';
        $archiveDir = file_get_contents(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-CURRENT-ARCHIVE.txt'
        );

        $this->r_path = '';

        if ( isset( $this->settings['bbPath'] ) ) {
            $this->r_path = $this->settings['bbPath'];
        }

        // TODO: move this where needed
        require_once dirname( __FILE__ ) .
            '/../library/StaticHtmlOutput/Archive.php';
        $this->archive = new Archive();
        $this->archive->setToCurrentArchive();

        $this->api_base = 'https://api.bitbucket.org/2.0/repositories/';

        switch ( $_POST['ajax_action'] ) {
            case 'bitbucket_prepare_export':
                $this->prepare_export( true );
                break;
            case 'bitbucket_upload_files':
                $this->upload_files();
                break;
            case 'test_bitbucket':
                $this->test_blob_create();
                break;
        }
    }

    public function upload_files() {
        $filesRemaining = $this->get_remaining_items_count();

        if ( $filesRemaining < 0 ) {
            echo 'ERROR';
            die();
        }

        $batch_size = $this->settings['bbBlobIncrement'];

        if ( $batch_size > $filesRemaining ) {
            $batch_size = $filesRemaining;
        }

        $lines = $this->get_items_to_export( $batch_size );
        $globHashPathLines = array();

        $files_data = array();

        foreach ( $lines as $line ) {
            list($fileToTransfer, $targetPath) = explode( ',', $line );

            $fileToTransfer = $this->archive->path . $fileToTransfer;

            $files_data[ 'message' ] = 'WP2Static deployment';

            if ( is_file( $fileToTransfer ) ) {
                $files_data[ '/' . rtrim( $targetPath ) ] =
                    new CURLFile ( $fileToTransfer );
            }
        }

        if ( isset( $this->settings['bbBlobDelay'] ) &&
            $this->settings['bbBlobDelay'] > 0 ) {
            sleep( $this->settings['bbBlobDelay'] );
        }

        try {
            $remote_path = $this->api_base . $this->settings['bbRepo'] . '/src';

            $ch = curl_init();

            curl_setopt( $ch, CURLOPT_URL, $remote_path );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
            curl_setopt( $ch, CURLOPT_HEADER, 0);
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
            curl_setopt( $ch, CURLOPT_POST, 1);

            $post_options = $files_data;

            // note: straight array over http_build_query for Bitbucket
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS, 
                $post_options
            );

            curl_setopt(
                $ch,
                CURLOPT_USERPWD,
                $this->user . ":" .
                    $this->settings['bbToken']
            );

            $output = curl_exec( $ch );
            $status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

            curl_close( $ch );
            
            $good_response_codes = array( '200', '201', '301', '302', '304' );

            if ( ! in_array( $status_code, $good_response_codes ) ) {
                require_once dirname( __FILE__ ) . '/../library/StaticHtmlOutput/WsLog.php';
                WsLog::l(
                    'BAD RESPONSE STATUS (' . $status_code . '): ' .
                     $output
                );

                throw new Exception( 'Bitbucket API bad response status' );
            }
        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/WsLog.php';
            WsLog::l( 'BITBUCKET EXPORT: error encountered' );
            WsLog::l( $e );
            throw new Exception( $e );
            return;
        }

        $filesRemaining = $this->get_remaining_items_count();

        if ( $filesRemaining > 0 ) {
            if ( defined( 'WP_CLI' ) ) {
                $this->upload_files();
            } else {
                echo $filesRemaining;
            }
        } else {
            if ( ! defined( 'WP_CLI' ) ) {
                echo 'SUCCESS';
            }
        }
    }

    public function test_blob_create() {
        try {
            $remote_path = $this->api_base . $this->settings['bbRepo'] . '/src';

            $ch = curl_init();

            curl_setopt( $ch, CURLOPT_URL, $remote_path );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
            curl_setopt( $ch, CURLOPT_HEADER, 0);
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
            curl_setopt( $ch, CURLOPT_POST, 1);

            $post_options = array(
                '.tmp_wp2static.txt' => 'Test WP2Static connectivity',
                '.tmp_wp2static.txt' => 'Test WP2Static connectivity #2',
                'message' => 'WP2Static deployment test'
            );

            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS, 
                $post_options
            );

            curl_setopt(
                $ch,
                CURLOPT_USERPWD,
                $this->user . ":" .
                    $this->settings['bbToken']
            );

            $output = curl_exec( $ch );
            $status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

            curl_close( $ch );
            
            $good_response_codes = array( '200', '201', '301', '302', '304' );

            if ( ! in_array( $status_code, $good_response_codes ) ) {
                require_once dirname( __FILE__ ) . '/../library/StaticHtmlOutput/WsLog.php';
                WsLog::l(
                    'BAD RESPONSE STATUS (' . $status_code . '): '
                );

                throw new Exception( 'Bitbucket API bad response status' );
            }
        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/WsLog.php';
            WsLog::l( 'BITBUCKET EXPORT: error encountered' );
            WsLog::l( $e );
            throw new Exception( $e );
            return;
        }

        if ( ! defined( 'WP_CLI' ) ) {
            echo 'SUCCESS';
        }
    }
}

$bitbucket = new StaticHtmlOutput_BitBucket();
