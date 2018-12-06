<?php

use Aws\S3\S3Client;

class StaticHtmlOutput_S3 {

    public function __construct() {
        $target_settings = array(
            'general',
            'wpenv',
            's3',
            'advanced',
        );

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $target_settings );
            $this->viaCLI = false;
        } else {
            $this->viaCLI = true;
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/DBSettings.php';

            $this->settings = WPSHO_DBSettings::get( $target_settings );
        }

        $this->exportFileList =
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-EXPORT-S3-FILES-TO-EXPORT';

        switch ( $_POST['ajax_action'] ) {
            case 'test_s3':
                $this->test_s3();
                break;
            case 's3_prepare_export':
                $this->prepare_deployment();
                break;
            case 's3_transfer_files':
                $this->transfer_files();
                break;
            case 'cloudfront_invalidate_all_items':
                $this->cloudfront_invalidate_all_items();
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
    public function create_s3_deployment_list( $dir, $archive, $rem_path ) {
        $files = scandir( $dir );

        foreach ( $files as $item ) {
            if ( $item != '.' && $item != '..' && $item != '.git' ) {
                if ( is_dir( $dir . '/' . $item ) ) {
                    $this->create_s3_deployment_list(
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

                    chmod( $this->exportFileList, 0664 );
                }
            }
        }
    }

    // TODO: move to parent class as identical to bunny and probably others
    public function prepare_deployment() {
            $this->clear_file_list();

            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/Archive.php';

            $archive = new Archive();
            $archive->setToCurrentArchive();

            $remote_path = isset( $this->settings['s3RemotePath'] ) ?
                $this->settings['s3RemotePath'] : '';

            $this->create_s3_deployment_list(
                $archive->path,
                $archive->path,
                $remote_path
            );

            if ( ! defined( 'WP_CLI' ) ) {
                echo 'SUCCESS';
            } 
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

        chmod( $this->exportFileList, 0664 );

        return $lines;
    }

    public function get_remaining_items_count() {
        $contents = file( $this->exportFileList, FILE_IGNORE_NEW_LINES );

        // return the amount left if another item is taken
        // return count($contents) - 1;
        return count( $contents );
    }

    public function transfer_files() {
        $filesRemaining = $this->get_remaining_items_count();
        error_log($filesRemaining);

        if ( $filesRemaining < 0 ) {
            echo 'ERROR';
            die();
        }

        $batch_size = $this->settings['s3BlobIncrement'];

        if ( $batch_size > $filesRemaining ) {
            $batch_size = $filesRemaining;
        }

        $lines = $this->get_items_to_export( $batch_size );

        // vendor specific from here
        require_once dirname( __FILE__ ) . '/../library/StaticHtmlOutput/MimeTypes.php';
        require_once dirname( __FILE__ ) . '/../library/aws/aws-autoloader.php';
        require_once dirname( __FILE__ ) . '/../library/GuzzleHttp/autoloader.php';

        $S3 = Aws\S3\S3Client::factory(
            array(
                'version' => '2006-03-01',
                'region' => $this->settings['s3Region'],
                'credentials' => array(
                    'key' => $this->settings['s3Key'],
                    'secret'  => $this->settings['s3Secret'],
                ),
            )
        );

        foreach ( $lines as $line ) {
            list($fileToTransfer, $targetPath) = explode( ',', $line );

            $targetPath = rtrim( $targetPath );

            try {
                $S3->PutObject(
                    array(
                        'Bucket'      => $this->settings['s3Bucket'],
                        'Key'         => $targetPath .
                            basename( $fileToTransfer ),
                        'Body'        => file_get_contents( $fileToTransfer ),
                        'ACL'         => 'public-read',
                        'ContentType' => GuessMimeType( $fileToTransfer ),
                    )
                );

            } catch ( Aws\S3\Exception\S3Exception $e ) {
                error_log( $e );
                require_once dirname( __FILE__ ) .
                    '/../library/StaticHtmlOutput/WsLog.php';

                WsLog::l( 'S3 ERROR RETURNED: ' . $e );
                echo "There was an error testing S3.\n";
            }
        }

        if ( isset( $this->settings['s3BlobDelay'] ) &&
            $this->settings['s3BlobDelay'] > 0 ) {
            sleep( $this->settings['s3BlobDelay'] );
        }

        // end vendor specific
        $filesRemaining = $this->get_remaining_items_count();

        error_log($filesRemaining);

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

    public function test_s3() {
        require_once dirname( __FILE__ ) . '/../library/aws/aws-autoloader.php';
        require_once dirname( __FILE__ ) . '/../library/GuzzleHttp/autoloader.php';

        $S3 = Aws\S3\S3Client::factory(
            array(
                'version' => '2006-03-01',
                'region' => $this->settings['s3Region'],
                'credentials' => array(
                    'key' => $this->settings['s3Key'],
                    'secret'  => $this->settings['s3Secret'],
                ),
            )
        );

        try {
            $S3->PutObject(
                array(
                    'Bucket'      => $this->settings['s3Bucket'],
                    'Key'         => '.tmp_wpsho' . time(),
                    'Body'        => 'test plugin connectivity',
                    'ACL'         => 'public-read',
                    'ContentType' => 'text/plain',
                )
            );

        } catch ( Aws\S3\Exception\S3Exception $e ) {
            error_log( $e );
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/WsLog.php';

            WsLog::l( 'S3 ERROR RETURNED: ' . $e );
            echo "There was an error testing S3.\n";
        }

            if ( ! defined( 'WP_CLI' ) ) {
                echo 'SUCCESS';
            } 
    }

    public function cloudfront_invalidate_all_items() {
        if ( ! isset( $this->settings['cfDistributionId'] ) ) {
            if ( ! defined( 'WP_CLI' ) ) {
                echo 'SUCCESS';
            } 
            return;
        }

        require_once __DIR__ . '/../library/CloudFront/CloudFront.php';
        $cloudfront_id = $this->settings['cfDistributionId'];

        if ( ! empty( $cloudfront_id ) ) {

            $cf = new CloudFront(
                $this->settings['s3Key'],
                $this->settings['s3Secret'],
                $cloudfront_id
            );

            $cf->invalidate( '/*' );

            if ( $cf->getResponseMessage() === '200' ||
                $cf->getResponseMessage() === '201' ||
                $cf->getResponseMessage() === '201: Request accepted' ) {

                if ( ! defined( 'WP_CLI' ) ) {
                    echo 'SUCCESS';
                } 
            } else {
                require_once dirname( __FILE__ ) .
                    '/../library/StaticHtmlOutput/WsLog.php';
                WsLog::l( 'CF ERROR: ' . $cf->getResponseMessage() );
            }
        } else {
            if ( ! defined( 'WP_CLI' ) ) {
                echo 'SUCCESS';
            } 
        }
    }
}

$s3 = new StaticHtmlOutput_S3();
