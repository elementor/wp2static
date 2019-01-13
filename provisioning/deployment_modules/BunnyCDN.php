<?php

class StaticHtmlOutput_BunnyCDN extends StaticHtmlOutput_SitePublisher {

    public function __construct() {
        $target_settings = array(
            'general',
            'wpenv',
            'bunnycdn',
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

        $this->export_file_list =
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-EXPORT-BUNNYCDN-FILES-TO-EXPORT.txt';

        $archiveDir = file_get_contents(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-CURRENT-ARCHIVE.txt'
        );

        $this->r_path = '';

        $this->api_base = 'https://storage.bunnycdn.com';

        if ( isset( $this->settings['bunnycdnRemotePath'] ) ) {
            $this->r_path = $this->settings['bunnycdnRemotePath'];
        }

        // TODO: move this where needed
        require_once dirname( __FILE__ ) .
            '/../library/StaticHtmlOutput/Archive.php';
        $this->archive = new Archive();
        $this->archive->setToCurrentArchive();

        switch ( $_POST['ajax_action'] ) {
            case 'bunnycdn_prepare_export':
                $this->prepare_export();
                break;
            case 'bunnycdn_transfer_files':
                $this->transfer_files();
                break;
            case 'bunnycdn_purge_cache':
                $this->purge_all_cache();
                break;
            case 'test_bunny':
                $this->test_deploy();
                break;
        }
    }


    public function transfer_files() {
        require_once dirname( __FILE__ ) .
            '/../library/GuzzleHttp/autoloader.php';

        $filesRemaining = $this->get_remaining_items_count();

        if ( $filesRemaining < 0 ) {
            echo 'ERROR';
            die();
        }

        $batch_size = $this->settings['bunnyBlobIncrement'];

        if ( $batch_size > $filesRemaining ) {
            $batch_size = $filesRemaining;
        }

        $lines = $this->get_items_to_export( $batch_size );

        $client = new Client(
            array(
                'base_uri' => 'https://storage.bunnycdn.com',
            )
        );

        foreach ( $lines as $line ) {
            list($fileToTransfer, $targetPath) = explode( ',', $line );

            $fileToTransfer = $this->archive->path . $fileToTransfer;

            $targetPath = rtrim( $targetPath );

            try {
                $target_path = '/' . $this->settings['bunnycdnPullZoneName'] .
                    '/' . $targetPath . basename( $fileToTransfer );

                $response = $client->request(
                    'PUT',
                    $target_path,
                    array(
                        'headers'  => array(
                            'AccessKey' => ' ' .
                            $this->settings['bunnycdnAPIKey'],
                        ),
                        'body' => fopen( $fileToTransfer, 'rb' ),
                    )
                );
            } catch ( Exception $e ) {
                require_once dirname( __FILE__ ) .
                    '/../library/StaticHtmlOutput/WsLog.php';
                WsLog::l( 'BUNNYCDN EXPORT: error encountered' );
                WsLog::l( $e );
                error_log( $e );
                throw new Exception( $e );
            }
        }

        if ( isset( $this->settings['bunnyBlobDelay'] ) &&
            $this->settings['bunnyBlobDelay'] > 0 ) {
            sleep( $this->settings['bunnyBlobDelay'] );
        }

        $filesRemaining = $this->get_remaining_items_count();

        if ( $filesRemaining > 0 ) {

            if ( defined( 'WP_CLI' ) ) {
                $this->transfer_files();
            } else {
                echo $filesRemaining;
            }
        } else {
            if ( ! defined( 'WP_CLI' ) ) {
                echo 'SUCCESS';
            }
        }
    }

    public function purge_all_cache() {
        require_once dirname( __FILE__ ) .
            '/../library/GuzzleHttp/autoloader.php';
        // purege cache for each file
        $client = new Client();

        try {
            $endpoint = 'https://bunnycdn.com/api/pullzone/' .
                $this->_zoneID . '/purgeCache';

            $response = $client->request(
                'POST',
                $endpoint,
                array(
                    'headers'  => array(
                        'AccessKey' => ' ' . $this->settings['bunnycdnAPIKey'],
                    ),
                )
            );

            if ( $response->getStatusCode() === 200 ) {
                if ( ! defined( 'WP_CLI' ) ) {
                    echo 'SUCCESS';
                }
            } else {
                echo 'FAIL';
            }
        } catch ( Exception $e ) {
            WsLog::l( 'BUNNYCDN EXPORT: error encountered' );
            WsLog::l( $e );
            error_log( $e );
            throw new Exception( $e );
        }
    }

    public function test_deploy() {

        try {
            $remote_path = $this->api_base . '/' .
                $this->settings['bunnycdnPullZoneName'] .
                '/tmpFile';

            $ch = curl_init();

            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt( $ch, CURLOPT_URL, $remote_path );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
            curl_setopt( $ch, CURLOPT_HEADER, 0);
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
            curl_setopt( $ch, CURLOPT_POST, 1);

            curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                'AccessKey: ' . $this->settings['bunnycdnAPIKey'],
            ));

            $post_options = array(
                'body' => 'Test WP2Static connectivity',
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

                throw new Exception( 'BunnyCDN API bad response status' );
            }
        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/WsLog.php';
            WsLog::l( 'BUNNYCDN EXPORT: error encountered' );
            WsLog::l( $e );
            throw new Exception( $e );
        }

        if ( ! defined( 'WP_CLI' ) ) {
            echo 'SUCCESS';
        }
    }
}

$bunny = new StaticHtmlOutput_BunnyCDN();
