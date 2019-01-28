<?php

class WP2Static_BitBucket extends WP2Static_SitePublisher {

    public function __construct() {
        $this->loadSettings( 'bitbucket' );

        list($this->user, $this->repository) = explode(
            '/',
            $this->settings['bbRepo']
        );

        $this->api_base = 'https://api.bitbucket.org/2.0/repositories/';

        $this->previous_hashes_path =
            $this->settings['wp_uploads_path'] .
                '/WP2STATIC-BITBUCKET-PREVIOUS-HASHES.txt';

        if ( defined( 'WP_CLI' ) ) {
            return; }

        switch ( $_POST['ajax_action'] ) {
            case 'bitbucket_prepare_export':
                $this->bootstrap();
                $this->loadArchive();
                $this->prepareDeploy( true );
                break;
            case 'bitbucket_upload_files':
                $this->bootstrap();
                $this->loadArchive();
                $this->upload_files();
                break;
            case 'test_bitbucket':
                $this->test_upload();
                break;
        }
    }

    public function upload_files() {
        $this->files_remaining = $this->getRemainingItemsCount();

        if ( $this->files_remaining < 0 ) {
            echo 'ERROR';
            die(); }

        $this->initiateProgressIndicator();

        $batch_size = $this->settings['deployBatchSize'];

        if ( $batch_size > $this->files_remaining ) {
            $batch_size = $this->files_remaining;
        }

        $lines = $this->getItemsToDeploy( $batch_size );

        $this->openPreviousHashesFile();

        $this->files_data = array();

        foreach ( $lines as $line ) {
            $this->addFileToBatchForCommitting( $line );

            // NOTE: progress will indicate file preparation, not the transfer
            $this->updateProgress();
        }

        $this->sendBatchToBitbucket();

        $this->writeFilePathAndHashesToFile();

        $this->pauseBetweenAPICalls();

        if ( $this->uploadsCompleted() ) {
            $this->finalizeDeployment();
        }
    }

    public function test_upload() {
        require_once dirname( __FILE__ ) .
            '/../WP2Static/Request.php';
        $this->client = new WP2Static_Request();

        try {
            $remote_path = $this->api_base . $this->settings['bbRepo'] . '/src';

            $post_options = array(
                '.tmp_wp2static.txt' => 'Test WP2Static connectivity',
                '.tmp_wp2static.txt' => 'Test WP2Static connectivity #2',
                'message' => 'WP2Static deployment test',
            );

            $this->client->postWithArray(
                $remote_path,
                $post_options,
                $curl_options = array(
                    CURLOPT_USERPWD => $this->user . ':' .
                        $this->settings['bbToken'],
                )
            );

            $this->checkForValidResponses(
                $this->client->status_code,
                array( '200', '201', '301', '302', '304' )
            );
        } catch ( Exception $e ) {
            $this->handleException( $e );
        }

        $this->finalizeDeployment();
    }

    public function addFileToBatchForCommitting( $line ) {
        list($local_file, $this->target_path) = explode( ',', $line );

        $local_file = $this->archive->path . $local_file;

        $this->files_data['message'] = 'WP2Static deployment';

        if ( ! is_file( $local_file ) ) {
            return; }

        if ( isset( $this->settings['bbPath'] ) ) {
            $this->target_path =
                $this->settings['bbPath'] . '/' . $this->target_path;
        }

        $this->local_file_contents = file_get_contents( $local_file );

        if ( isset( $this->file_paths_and_hashes[ $this->target_path ] ) ) {
            $prev = $this->file_paths_and_hashes[ $this->target_path ];
            $current = crc32( $this->local_file_contents );

            if ( $prev != $current ) {
                $this->files_data[ '/' . rtrim( $this->target_path ) ] =
                    new CURLFile( $local_file );

                $this->recordFilePathAndHashInMemory(
                    $this->target_path,
                    $this->local_file_contents
                );
            }
        } else {
            $this->files_data[ '/' . rtrim( $this->target_path ) ] =
                new CURLFile( $local_file );

            $this->recordFilePathAndHashInMemory(
                $this->target_path,
                $this->local_file_contents
            );
        }

    }

    public function sendBatchToBitbucket() {
        require_once dirname( __FILE__ ) .
            '/../WP2Static/Request.php';
        $this->client = new WP2Static_Request();

        $remote_path = $this->api_base . $this->settings['bbRepo'] . '/src';

        $post_options = $this->files_data;

        try {
            // note: straight array over http_build_query for Bitbucket
            $this->client->postWithArray(
                $remote_path,
                $post_options,
                $curl_options = array(
                    CURLOPT_USERPWD => $this->user . ':' .
                        $this->settings['bbToken'],
                )
            );

            $this->checkForValidResponses(
                $this->client->status_code,
                array( '200', '201', '301', '302', '304' )
            );
        } catch ( Exception $e ) {
            $this->handleException( $e );
        }
    }
}

$bitbucket = new WP2Static_BitBucket();
