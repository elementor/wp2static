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
                '/../StaticHtmlOutput/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $target_settings );
        } else {
            error_log( 'TODO: load settings from DB' );
        }

        $this->_exportFileList =
            $this->settings['working_directory'] . '/WP-STATIC-EXPORT-S3-FILES-TO-EXPORT';

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
        // TODO: avoid suppression
        $f = fopen( $this->_exportFileList, 'r+' );
        if ( $f !== false ) {
            ftruncate( $f, 0 );
            fclose( $f );
        }

        $f = fopen( $this->_globHashAndPathList, 'r+' );
        if ( $f !== false ) {
            ftruncate( $f, 0 );
            fclose( $f );
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
                        $this->_exportFileList,
                        $export_line,
                        FILE_APPEND | LOCK_EX
                    );
                }
            }
        }
    }

    // TODO: move to parent class as identical to bunny and probably others
    public function prepare_deployment() {
        if ( wpsho_fr()->is__premium_only() ) {

            $this->clear_file_list();

            $this->create_s3_deployment_list(
                $this->_archiveName,
                $this->_archiveName,
                $this->_remotePath
            );

            echo 'SUCCESS';
        }
    }

    public function get_item_to_export() {
        $f = fopen( $this->_exportFileList, 'r' );
        $line = fgets( $f );
        fclose( $f );

        $contents = file( $this->_exportFileList, FILE_IGNORE_NEW_LINES );
        array_shift( $contents );
        file_put_contents(
            $this->_exportFileList,
            implode( "\r\n", $contents )
        );

        return $line;
    }

    public function get_remaining_items_count() {
        $contents = file( $this->_exportFileList, FILE_IGNORE_NEW_LINES );

        // return the amount left if another item is taken
        // return count($contents) - 1;
        return count( $contents );
    }

    public function s3_put_object(
        $targetPath,
        $fileContents,
        $contentType = 'text/plain',
        $pluginInstance
        ) {
        if ( wpsho_fr()->is__premium_only() ) {
            require_once dirname( __FILE__ ) . '/../aws/aws-autoloader.php';
            require_once dirname( __FILE__ ) . '/../GuzzleHttp/autoloader.php';

            $S3 = Aws\S3\S3Client::factory(
                array(
                    'version' => '2006-03-01',
                    'region' => $this->_region,
                    'credentials' => array(
                        'key' => $this->_key,
                        'secret'  => $this->_secret,
                    ),
                )
            );

            try {
                $S3->PutObject(
                    array(
                        'Bucket'      => $this->_bucket,
                        'Key'         => $targetPath,
                        'Body'        => $fileContents,
                        'ACL'         => 'public-read',
                        'ContentType' => $contentType,
                    )
                );

            } catch ( Aws\S3\Exception\S3Exception $e ) {
                error_log( $e );
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/WsLog.php';

                WsLog::l( 'S3 ERROR RETURNED: ' . $e );
                echo "There was an error uploading the file.\n";
            }
        }
    }


    public function transfer_files( $viaCLI ) {
        if ( wpsho_fr()->is__premium_only() ) {

            if ( $this->get_remaining_items_count() < 0 ) {
                echo 'ERROR';
                die();
            }

            $line = $this->get_item_to_export();

            list($fileToTransfer, $targetPath) = explode( ',', $line );

            $targetPath = rtrim( $targetPath );

            // vendor specific from here
            require_once __DIR__ . '/MimeTypes.php';

            $this->s3_put_object(
                $targetPath . basename( $fileToTransfer ),
                file_get_contents( $fileToTransfer ),
                GuessMimeType( $fileToTransfer ),
                $this
            );

            // end vendor specific
            $filesRemaining = $this->get_remaining_items_count();
            if ( $filesRemaining > 0 ) {
                // if this is via CLI, then call this function again here
                if ( $viaCLI ) {
                    $this->transfer_files( true );
                }
                echo $filesRemaining;
            } else {
                echo 'SUCCESS';
            }
        }
    }

    public function test_s3() {
        require_once dirname( __FILE__ ) . '/../aws/aws-autoloader.php';
        require_once dirname( __FILE__ ) . '/../GuzzleHttp/autoloader.php';

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
                '/../StaticHtmlOutput/WsLog.php';

            WsLog::l( 'S3 ERROR RETURNED: ' . $e );
            echo "There was an error testing S3.\n";
        }

        echo 'SUCCESS';
    }

    public function cloudfront_invalidate_all_items() {
        if ( wpsho_fr()->is__premium_only() ) {
            require_once __DIR__ . '/CloudFront/CloudFront.php';
            $cloudfront_id = $this->cfDistributionId;

            if ( ! empty( $cloudfront_id ) ) {

                $cf = new CloudFront(
                    $this->s3Key,
                    $this->s3Secret,
                    $cloudfront_id
                );

                $cf->invalidate( '/*' );

                if ( $cf->getResponseMessage() === 200 ||
                    $cf->getResponseMessage() === 201 ) {
                    echo 'SUCCESS';
                } else {
                    WsLog::l( 'CF ERROR: ' . $cf->getResponseMessage() );
                }
            } else {
                echo 'SUCCESS';
            }
        }
    }
}

$s3 = new StaticHtmlOutput_S3();
