<?php

use GuzzleHttp\Client;

class StaticHtmlOutput_BunnyCDN {

    public function __construct() {
        $target_settings = array(
            'general',
            'wpenv',
            'bunnycdn',
            'advanced',
        );

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $target_settings );
        } else {
            error_log( 'TODO: load settings from DB' );
        }

        $this->exportFileList =
            $this->settings['working_directory'] .
                '/WP-STATIC-EXPORT-BUNNYCDN-FILES-TO-EXPORT';

        $archiveDir = file_get_contents(
            $this->settings['working_directory'] .
                '/WP-STATIC-CURRENT-ARCHIVE'
        );

        // TODO: move this where needed
        require_once dirname( __FILE__ ) .
            '/../StaticHtmlOutput/Archive.php';
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

    public function clear_file_list() {
        if ( is_file( $this->exportFileList ) ) {
            $f = fopen( $this->exportFileList, 'r+' );
            if ( $f !== false ) {
                ftruncate( $f, 0 );
                fclose( $f );
            }
        }
    }

    public function create_bunny_deployment_list( $dir ) {
        $r_path = '';
        $archive = $this->archive->path;

        if ( isset( $this->settings['bunnycdnRemotePath'] ) ) {
            $r_path = $this->settings['bunnycdnRemotePath'];
        }

        $files = scandir( $dir );

        foreach ( $files as $item ) {
            if ( $item != '.' && $item != '..' && $item != '.git' ) {
                if ( is_dir( $dir . '/' . $item ) ) {
                    $this->create_bunny_deployment_list(
                        $dir . '/' . $item,
                        $archive,
                        $rem_path
                    );
                } elseif ( is_file( $dir . '/' . $item ) ) {
                    $subdir = str_replace(
                        '/wp-admin/admin-ajax.php',
                        '',
                        $_SERVER['REQUEST_URI']
                    );
                    $subdir = ltrim( $subdir, '/' );
                    $clean_dir = str_replace( $archive . '/', '', $dir . '/' );
                    $clean_dir = str_replace( $subdir, '', $clean_dir );
                    $targetPath = $rem_path . $clean_dir;
                    $targetPath = ltrim( $targetPath, '/' );
                    $export_line =
                        $dir . '/' . $item . ',' . $targetPath . "\n";
                    file_put_contents(
                        $this->exportFileList,
                        $export_line,
                        FILE_APPEND | LOCK_EX
                    );
                }
            }
        }
    }

    public function prepare_export() {
            $this->clear_file_list();

            $this->create_bunny_deployment_list(
                $this->settings['working_directory'] . '/' .
                    $this->archive->name
            );

            echo 'SUCCESS';
    }

    public function get_items_to_export( $batch_size = 1 ) {
        $lines = array();

        $f = fopen( $this->exportFileList, 'r' );

        for ( $i = 0; $i < $batch_size; $i++ ) {
            $lines[] = fgets( $f );
        }

        fclose( $f );

        // TODO: optimize this for just one read, one write within func
        $contents = file( $this->exportFileList, FILE_IGNORE_NEW_LINES );

        for ( $i = 0; $i < $batch_size; $i++ ) {
            error_log('removing item from array');
            // rewrite file minus the lines we took
            array_shift( $contents );
        }

        file_put_contents(
            $this->exportFileList,
            implode( "\r\n", $contents )
        );

        return $lines;
    }

    public function get_remaining_items_count() {
        $contents = file( $this->exportFileList, FILE_IGNORE_NEW_LINES );

        // return the amount left if another item is taken
        // return count($contents) - 1;

        return count( $contents );
    }

    public function transfer_files( $viaCLI = false ) {
        require_once dirname( __FILE__ ) . '/../GuzzleHttp/autoloader.php';

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

        foreach ( $lines as $line ) {

            list($fileToTransfer, $targetPath) = explode( ',', $line );

            $targetPath = rtrim( $targetPath );

            // do the bunny export
            $client = new Client(
                array(
                    'base_uri' => 'https://storage.bunnycdn.com',
                )
            );

            try {
                $target_path = '/' . $this->settings['bunnycdnPullZoneName'] . '/' .
                    $targetPath . basename( $fileToTransfer );

                $response = $client->request(
                    'PUT',
                    $target_path,
                    array(
                        'headers'  => array(
                            'AccessKey' => ' ' . $this->settings['bunnycdnAPIKey'],
                        ),
                        'body' => fopen( $fileToTransfer, 'rb' ),
                    )
                );
            } catch ( Exception $e ) {
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/WsLog.php';
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

            if ( $viaCLI ) {
                $this->transfer_files( true );
            }

            echo $filesRemaining;
        } else {
            echo 'SUCCESS';
        }
    }

    public function purge_all_cache() {
        require_once dirname( __FILE__ ) . '/../GuzzleHttp/autoloader.php';
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
                echo 'SUCCESS';
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
        require_once dirname( __FILE__ ) . '/../GuzzleHttp/autoloader.php';

        $client = new Client(
            array(
                'base_uri' => 'https://storage.bunnycdn.com',
                // TODO: these kind of cURL options would be nice in Advanced
                //'force_ip_resolve' => 'v4'
            )
        );

        try {
            $target_path = '/' . $this->settings['bunnycdnPullZoneName'] .
                '/tmpFile'
                ;

            $response = $client->request(
                'PUT',
                $target_path,
                array(
                    'headers'  => array(
                        'AccessKey' => ' ' . $this->settings['bunnycdnAPIKey'],
                    ),
                    'body' => 'deploy',
                )
            );
        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
            WsLog::l( 'BUNNYCDN EXPORT: error encountered' );
            WsLog::l( $e );
            error_log( $e );
            throw new Exception( $e );
        }

        echo 'SUCCESS';
    }
}

$bunny = new StaticHtmlOutput_BunnyCDN();
