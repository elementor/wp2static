<?php

use GuzzleHttp\Client;

class StaticHtmlOutput_GitHub extends StaticHtmlOutput_SitePublisher {

    public function __construct() {
        $target_settings = array(
            'general',
            'wpenv',
            'github',
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
            $this->settings['ghRepo']
        );

        $this->exportFileList =
            $this->settings['working_directory'] .
                '/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT';
        $this->globHashAndPathList =
            $this->settings['working_directory'] .
                '/WP-STATIC-EXPORT-GITHUB-GLOBS-PATHS';

        // TODO: move this where needed
        require_once dirname( __FILE__ ) .
            '/../StaticHtmlOutput/Archive.php';
        $this->archive = new Archive();
        $this->archive->setToCurrentArchive();

        $this->r_path = '';

        if ( isset( $this->settings['ghPath'] ) ) {
            $this->r_path = $this->settings['ghPath'];
        }

        switch ( $_POST['ajax_action'] ) {
            case 'github_prepare_export':
                $this->prepare_export();
                break;
            case 'github_upload_blobs':
                $this->upload_blobs();
                break;
            case 'github_finalise_export':
                $this->commit_new_tree();
                break;
            case 'test_blob_create':
                $this->test_blob_create();
                break;
        }
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

        $batch_size = $this->settings['ghBlobIncrement'];

        if ( $batch_size > $filesRemaining ) {
            $batch_size = $filesRemaining;
        }

        $lines = $this->get_items_to_export( $batch_size );
        $globHashPathLines = array();

        $client = new \Github\Client();
        $client->authenticate(
            $this->settings['ghToken'],
            Github\Client::AUTH_HTTP_TOKEN
        );

        foreach ( $lines as $line ) {
            list($fileToTransfer, $targetPath) = explode( ',', $line );

            $fileToTransfer = $this->archive->path . $fileToTransfer;

            if ( isset( $this->settings['ghBlobDelay'] ) &&
                $this->settings['ghBlobDelay'] > 0 ) {
                sleep( $this->settings['ghBlobDelay'] );
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
                // TODO:  https://developer.github.com/v3/rate_limit/
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
            $this->settings['ghToken'],
            Github\Client::AUTH_HTTP_TOKEN
        );
        $reference = $client->api( 'gitData' )->references()->show(
            $this->user,
            $this->repository,
            'heads/' . $this->settings['ghBranch']
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
                'heads/' . $this->settings['ghBranch'],
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
        require_once __DIR__ . '/../Github/autoload.php';

        $client = new \Github\Client();
        $client->authenticate(
            $this->settings['ghToken'],
            Github\Client::AUTH_HTTP_TOKEN
        );

        $encodedFile = chunk_split(
            base64_encode( 'test string' )
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
            // TODO:  rate limits: https://developer.github.com/v3/rate_limit/
            $coreLimit = $client->api( 'rate_limit' )->getCoreLimit();
            error_log( $coreLimit );
            return;
        }

        echo 'SUCCESS';
    }
}

$github = new StaticHtmlOutput_GitHub();
