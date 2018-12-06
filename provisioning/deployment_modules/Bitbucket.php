<?php

use GuzzleHttp\Client;

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

        $this->exportFileList =
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
                $this->prepare_export();
                break;
            case 'bitbucket_upload_files':
                $this->upload_files();
                break;
            case 'test_bitbucket':
                $this->test_blob_create();
                break;
        }
    }

    // NOTE: override parent to include file in path
    public function create_deployment_list( $dir ) {
        $archive = $this->archive->path;

        $files = scandir( $dir );

        foreach ( $files as $item ) {
            if ( $item != '.' && $item != '..' && $item != '.git' ) {
                if ( is_dir( $dir . '/' . $item ) ) {
                    $this->create_deployment_list( $dir . '/' . $item );
                } elseif ( is_file( $dir . '/' . $item ) ) {
                    $wp_subdir = str_replace(
                        '/wp-admin/admin-ajax.php',
                        '',
                        $_SERVER['REQUEST_URI']
                    );

                    $wp_subdir = ltrim( $subdir, '/' );
                    $dirs_in_path = $dir;
                    $filename = $item;
                    $original_filepath = $dir . '/' . $item;

                    $local_path_to_strip = $archive . '/' . $wp_subdir;
                    $local_path_to_strip = rtrim( $local_path_to_strip, '/' );

                    $deploy_path = str_replace(
                        $local_path_to_strip,
                        '',
                        $dirs_in_path
                    );

                    $original_file_without_archive = str_replace(
                        $local_path_to_strip,
                        '',
                        $original_filepath
                    );

                    $original_file_without_archive = ltrim(
                        $original_file_without_archive,
                        '/'
                    );

                    $deploy_path = $this->r_path . $deploy_path;
                    $deploy_path = ltrim( $deploy_path, '/' );
                    $deploy_path .= '/';

                    $export_line =
                        $original_file_without_archive . ',' . // field 1
                        $original_file_without_archive . // field 2
                        "\n";

                    file_put_contents(
                        $this->exportFileList,
                        $export_line,
                        FILE_APPEND | LOCK_EX
                    );

                    chmod( $this->exportFileList, 0664 );
                }
            }
        }
    }

    public function upload_files() {
        require_once dirname( __FILE__ ) .
            '/../library/GuzzleHttp/autoloader.php';

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
                '/../library/StaticHtmlOutput/WsLog.php';
            WsLog::l( 'BITBUCKET EXPORT: error encountered' );
            WsLog::l( $e );
            error_log( $e );
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
        require_once dirname( __FILE__ ) .
            '/../library/GuzzleHttp/autoloader.php';
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
                '/../library/StaticHtmlOutput/WsLog.php';
            WsLog::l( 'BITBUCKET EXPORT: error encountered' );
            WsLog::l( $e );
            error_log( $e );
            throw new Exception( $e );
            return;
        }

        if ( ! defined( 'WP_CLI' ) ) {
            echo 'SUCCESS';
        } 
    }
}

$bitbucket = new StaticHtmlOutput_BitBucket();
