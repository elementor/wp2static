<?php

use GuzzleHttp\Client;

class StaticHtmlOutput_BitBucket {

    public function __construct() {
        $target_settings = array(
            'general',
            'wpenv',
            'bitbucket',
            'advanced',
        );

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $target_settings );
        } else {
            error_log( 'TODO: load settings from DB' );
        }

        list($this->user, $this->repository) = explode(
            '/',
            $this->settings['bbRepo']
        );

        $this->exportFileList =
            $this->settings['working_directory'] .
                '/WP-STATIC-EXPORT-BITBUCKET-FILES-TO-EXPORT';
        $archiveDir = file_get_contents(
            $this->settings['working_directory'] .
                '/WP-STATIC-CURRENT-ARCHIVE'
        );

        // TODO: move this where needed
        require_once dirname( __FILE__ ) .
            '/../StaticHtmlOutput/Archive.php';
        $this->archive = new Archive();
        $this->archive->setToCurrentArchive();

        $this->api_base = 'https://api.bitbucket.org/2.0/repositories/';

        switch ( $_POST['ajax_action'] ) {
            case 'bitbucket_prepare_export':
                $this->prepare_deployment();
                break;
            case 'bitbucket_upload_files':
                $this->upload_files();
                break;
            case 'test_bitbucket':
                $this->test_blob_create();
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

    // TODO: move into a parent class as identical to bunny and probably others
    public function create_bitbucket_deployment_list( $dir ) {
        $r_path = '';
        $archive = $this->archive->path;

        if ( isset( $this->settings['bbPath'] ) ) {
            $r_path = $this->settings['bbPath'];
        }

        $files = scandir( $dir );

        foreach ( $files as $item ) {
            if ( $item != '.' && $item != '..' && $item != '.git' ) {
                if ( is_dir( $dir . '/' . $item ) ) {
                    $this->create_bitbucket_deployment_list(
                        $dir . '/' . $item
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
                    $targetPath = $r_path . $clean_dir;
                    $targetPath .= $item;
                    $targetPath =
                        str_replace( $this->archive->path, '', $targetPath );

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

    // TODO: move to a parent class as identical to bunny and probably others
    public function prepare_deployment() {
            $this->clear_file_list();
            $this->create_bitbucket_deployment_list(
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


    public function upload_files( $viaCLI = false ) {
        require_once dirname( __FILE__ ) .
            '/../GuzzleHttp/autoloader.php';

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

            $files_data[] = array(
                'name'     => '/' . rtrim( $targetPath ),
                'contents' => fopen( $fileToTransfer, 'rb' ),
            );
        }

        if ( isset( $this->settings['bbBlobDelay'] ) &&
            $this->settings['bbBlobDelay'] > 0 ) {
            sleep( $this->settings['bbBlobDelay'] );
        }

        $client = new Client(
            array(
                'base_uri' => $this->api_base,
            )
        );

        try {
            $response = $client->request(
                'POST',
                $this->settings['bbRepo'] . '/src',
                array(
                    'auth'  => array(
                        $this->user,
                        $this->settings['bbToken'],
                    ),
                    'multipart' => $files_data,
                )
            );

        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
            WsLog::l( 'BITBUCKET EXPORT: error encountered' );
            WsLog::l( $e );
            error_log( $e );
            throw new Exception( $e );
            return;
        }

        $filesRemaining = $this->get_remaining_items_count();

        if ( $filesRemaining > 0 ) {

            if ( $viaCLI ) {
                $this->upload_files( true );
            }

            echo $filesRemaining;
        } else {
            echo 'SUCCESS';
        }
    }

    public function test_blob_create() {
        require_once dirname( __FILE__ ) .
            '/../GuzzleHttp/autoloader.php';
        $client = new Client(
            array(
                'base_uri' => $this->api_base,
            )
        );

        try {
            $response = $client->request(
                'POST',
                 $this->settings['bbRepo'] . '/src',
                array(
                    'auth'  => array(
                        $this->user,
                        $this->settings['bbToken'],
                    ),
                    // TODO: grab n of these as an array and iterate
                    'multipart' => array(
                        array(
                            'name'     => 'file1.html',
                            'contents' => 'first file',
                        ),
                        array(
                            'name'     => 'file2.html',
                            'contents' => '2nd file',
                        ),
                    ),
                )
            );

        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
            WsLog::l( 'BITBUCKET EXPORT: error encountered' );
            WsLog::l( $e );
            error_log( $e );
            throw new Exception( $e );
            return;
        }

        echo 'SUCCESS';
    }
}

$bitbucket = new StaticHtmlOutput_BitBucket();
