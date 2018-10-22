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
        $this->globHashAndPathList =
            $this->settings['working_directory'] .
                '/WP-STATIC-EXPORT-BITBUCKET-GLOBS-PATHS';
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
            // case 'bitbucket_upload_blobs':
            // $this->upload_blobs();
            // break;
            // case 'bitbucket_finalise_export':
            // $this->commit_new_tree();
            // break;
            case 'test_bitbucket':
                error_log( 'testing bb' );
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

        if ( is_file( $this->globHashAndPathList ) ) {
            $f = fopen( $this->globHashAndPathList, 'r+' );
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
            error_log( 'preparin BB deployment' );
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


    public function upload_blobs( $viaCLI = false ) {
        require_once dirname( __FILE__ ) .
            '/../GuzzleHttp/autoloader.php';
        require_once __DIR__ . '/../Github/autoload.php';

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

        $client = new \Github\Client();
        $client->authenticate(
            $this->settings['bbToken'],
            Github\Client::AUTH_HTTP_TOKEN
        );

        foreach ( $lines as $line ) {
            list($fileToTransfer, $targetPath) = explode( ',', $line );

            if ( isset( $this->settings['bbBlobDelay'] ) &&
                $this->settings['bbBlobDelay'] > 0 ) {
                sleep( $this->settings['bbBlobDelay'] );
            }

            // vendor specific from here
            // TODO: why are we chunk_splitting with no delimiter?
            $encodedFile = chunk_split(
                base64_encode( file_get_contents( $fileToTransfer ) )
            );

            try {
                $globHash = $client->api( 'gitData' )->blobs()->create(
                    $this->user,
                    $this->repository,
                    array(
                        'content' => $encodedFile,
                        'encoding' => 'base64',
                    )
                ); // utf-8 or base64
            } catch ( Exception $e ) {
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/WsLog.php';
                WsLog::l( 'GITHUB: Error creating blob (API limits?):' . $e );
                error_log( 'error creating blog in GitHub (API limits?)' );
                // TODO:  https://developer.bitbucket.com/v3/rate_limit/
                $coreLimit = $client->api( 'rate_limit' )->getCoreLimit();
                error_log( $coreLimit );
            }

            $targetPath = rtrim( $targetPath );

            $globHashPathLines[] = $globHash['sha'] . ',' .
                rtrim( $targetPath ) . basename( $fileToTransfer );
        }

        // TODO: move this file write out of loop - write to array in loop
        file_put_contents(
            $this->globHashAndPathList,
            implode( PHP_EOL, $globHashPathLines ),
            FILE_APPEND | LOCK_EX
        );

        $filesRemaining = $this->get_remaining_items_count();

        if ( $filesRemaining > 0 ) {

            if ( $viaCLI ) {
                $this->upload_blobs( true );
            }

            echo $filesRemaining;
        } else {
            echo 'SUCCESS';
        }
    }

    public function commit_new_tree() {
        require_once dirname( __FILE__ ) . '/../GuzzleHttp/autoloader.php';
        require_once __DIR__ . '/../Github/autoload.php';

        // vendor specific from here
        $client = new \Github\Client();

        $client->authenticate(
            $this->settings['bbToken'],
            Github\Client::AUTH_HTTP_TOKEN
        );
        $reference = $client->api( 'gitData' )->references()->show(
            $this->user,
            $this->repository,
            'heads/' . $this->settings['bbBranch']
        );
        $commit = $client->api( 'gitData' )->commits()->show(
            $this->user,
            $this->repository,
            $reference['object']['sha']
        );
        $commitSHA = $commit['sha'];
        $treeSHA = $commit['tree']['sha'];
        $treeURL = $commit['tree']['url'];
        $treeContents = array();
        $contents = file( $this->globHashAndPathList );

        foreach ( $contents as $line ) {
            list($blobHash, $targetPath) = explode( ',', $line );

            $treeContents[] = array(
                'path' => trim( $targetPath ),
                'mode' => '100644',
                'type' => 'blob',
                'sha' => $blobHash,
            );
        }

        $treeData = array(
            'base_tree' => $treeSHA,
            'tree' => $treeContents,
        );

        $newTree = $client->api( 'gitData' )->trees()->create(
            $this->user,
            $this->repository,
            $treeData
        );

        $commitData = array(
            'message' =>
                'WP Static HTML Output plugin: ' . date( 'Y-m-d h:i:s' ),
            'tree' => $newTree['sha'],
            'parents' => array( $commitSHA ),
        );
        $commit = $client->api( 'gitData' )->commits()->create(
            $this->user,
            $this->repository,
            $commitData
        );
        $referenceData = array(
            'sha' => $commit['sha'],
            'force' => true,
        ); // Force is default false

        try {
            $reference = $client->api( 'gitData' )->references()->update(
                $this->user,
                $this->repository,
                'heads/' . $this->settings['bbBranch'],
                $referenceData
            );
        } catch ( Exception $e ) {
            $this->wsLog( $e );
            throw new Exception( $e );
        }

        // end vendor specific
        $filesRemaining = $this->get_remaining_items_count();

        if ( $this->get_remaining_items_count() > 0 ) {
            echo $this->get_remaining_items_count();
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
                'wp2static/wp2static.bitbucket.io/src',
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
