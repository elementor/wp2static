<?php

class StaticHtmlOutput_GitHub extends StaticHtmlOutput_SitePublisher {

    public function __construct() {
        $this->loadSettings( 'github' );

        list($this->user, $this->repository) = explode(
            '/',
            $this->settings['ghRepo']
        );

        $this->api_base = 'https://api.github.com/repos/';

        $this->previous_hashes_path =
            $this->settings['wp_uploads_path'] .
                '/WP2STATIC-GITHUB-PREVIOUS-HASHES.txt';

        if ( defined( 'WP_CLI' ) ) {
            return; }

        switch ( $_POST['ajax_action'] ) {
            case 'github_prepare_export':
                $this->bootstrap();
                $this->loadArchive();
                $this->prepareDeploy( true );
                break;
            case 'github_upload_files':
                $this->bootstrap();
                $this->loadArchive();
                $this->upload_files();
                break;
            case 'test_github':
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

        foreach ( $lines as $line ) {
            list($local_file, $this->target_path) = explode( ',', $line );

            $local_file = $this->archive->path . $local_file;

            if ( ! is_file( $local_file ) ) {
                continue; }

            if ( isset( $this->settings['ghPath'] ) ) {
                $this->target_path =
                    $this->settings['ghPath'] . '/' . $this->target_path;
            }

            $this->local_file_contents = file_get_contents( $local_file );

            if ( isset( $this->file_paths_and_hashes[ $this->target_path ] ) ) {
                $prev = $this->file_paths_and_hashes[ $this->target_path ];
                $current = crc32( $this->local_file_contents );

                if ( $prev != $current ) {
                    if ( $this->fileExistsInGitHub() ) {
                        $this->updateFileInGitHub();
                    } else {
                        $this->createFileInGitHub();
                    }

                    $this->recordFilePathAndHashInMemory(
                        $this->target_path,
                        $this->local_file_contents
                    );
                }
            } else {
                if ( $this->fileExistsInGitHub() ) {
                    $this->updateFileInGitHub();
                } else {
                    $this->createFileInGitHub();
                }

                $this->recordFilePathAndHashInMemory(
                    $this->target_path,
                    $this->local_file_contents
                );
            }

            $this->updateProgress();
        }

        $this->writeFilePathAndHashesToFile();

        $this->pauseBetweenAPICalls();

        if ( $this->uploadsCompleted() ) {
            $this->finalizeDeployment();
        }
    }

    public function test_upload() {
        try {
            $this->remote_path = $this->api_base . $this->settings['ghRepo'] .
                '/contents/' . '.WP2Static/' . uniqid();

            $b64_file_contents = base64_encode( 'WP2Static test upload' );

            $ch = curl_init();

            curl_setopt( $ch, CURLOPT_URL, $this->remote_path );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
            curl_setopt( $ch, CURLOPT_HEADER, 0 );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
            curl_setopt( $ch, CURLOPT_POST, 1 );
            curl_setopt( $ch, CURLOPT_USERAGENT, 'WP2Static.com' );
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT' );

            $post_options = array(
                'message' => 'Test WP2Static connectivity',
                'content' => $b64_file_contents,
                'branch' => $this->settings['ghBranch'],
            );

            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                json_encode( $post_options )
            );

            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    'Authorization: ' .
                        'token ' . $this->settings['ghToken'],
                )
            );

            $output = curl_exec( $ch );
            $status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

            curl_close( $ch );

            $good_response_codes = array( '200', '201', '301', '302', '304' );

            if ( ! in_array( $status_code, $good_response_codes ) ) {
                require_once dirname( __FILE__ ) .
                    '/../library/StaticHtmlOutput/WsLog.php';
                WsLog::l(
                    'BAD RESPONSE STATUS (' . $status_code . '): '
                );

                throw new Exception( 'GitHub API bad response status' );
            }
        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/WsLog.php';
            WsLog::l( 'GITHUB EXPORT: error encountered' );
            WsLog::l( $e );
            throw new Exception( $e );
            return;
        }

        if ( ! defined( 'WP_CLI' ) ) {
            echo 'SUCCESS';
        }
    }

    public function fileExistsInGitHub() {
        $this->remote_path = $this->api_base . $this->settings['ghRepo'] .
            '/contents/' . $this->target_path;
        // GraphQL query to get sha of existing file
        $this->query = <<<JSON
query{
  repository(owner: "{$this->user}", name: "{$this->repository}") {
    object(expression: "{$this->settings['ghBranch']}:{$this->target_path}") {
      ... on Blob {
        oid
        byteSize
      }
    }
  }
}
JSON;
        require_once dirname( __FILE__ ) .
            '/../library/StaticHtmlOutput/Request.php';
        $this->client = new WP2Static_Request();

        $post_options = array(
            'query' => $this->query,
            'variables' => '',
        );

        $headers = array(
            'Authorization: ' .
                    'token ' . $this->settings['ghToken'],
        );

        $this->client->postWithJSONPayloadCustomHeaders(
            'https://api.github.com/graphql',
            $post_options,
            $headers,
            $curl_options = array(
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            )
        );

        $this->checkForValidResponses(
            $this->client->status_code,
            array( '200', '201', '301', '302', '304' )
        );

        $gh_file_info = json_decode( $this->client->body, true );

        $this->existing_file_object =
            $gh_file_info['data']['repository']['object'];

        $action = '';
        $commit_message = '';

        if ( ! empty( $this->existing_file_object ) ) {
            return true;
        }
    }

    public function updateFileInGitHub() {
        $action = 'UPDATE';
        $existing_sha = $this->existing_file_object['oid'];
        $existing_bytesize = $this->existing_file_object['byteSize'];

        $b64_file_contents = base64_encode( $this->local_file_contents );

        if ( isset( $this->settings['ghCommitMessage'] ) ) {
            $commit_message = str_replace(
                array(
                    '%ACTION%',
                    '%FILENAME%',
                ),
                array(
                    $action,
                    $this->target_path,
                ),
                $this->settings['ghCommitMessage']
            );
        } else {
            $commit_message = 'WP2Static ' .
                $action . ' ' .
                $this->target_path;
        }

        try {
            $post_options = array(
                'message' => $commit_message,
                'content' => $b64_file_contents,
                'branch' => $this->settings['ghBranch'],
                'sha' => $existing_sha,
            );

            $headers = array(
                'Authorization: ' .
                        'token ' . $this->settings['ghToken'],
            );

            $this->client->putWithJSONPayloadCustomHeaders(
                $this->remote_path,
                $post_options,
                $headers,
                $curl_options = array(
                    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                )
            );

            $this->debugResponseBody();

            $this->checkForValidResponses(
                $this->client->status_code,
                array( '200', '201', '301', '302', '304' )
            );
        } catch ( Exception $e ) {
            $this->handleException( $e );
        }
    }

    public function createFileInGitHub() {
        $action = 'CREATE';

        $b64_file_contents = base64_encode( $this->local_file_contents );

        if ( isset( $this->settings['ghCommitMessage'] ) ) {
            $commit_message = str_replace(
                array(
                    '%ACTION%',
                    '%FILENAME%',
                ),
                array(
                    $action,
                    $this->target_path,
                ),
                $this->settings['ghCommitMessage']
            );
        } else {
            $commit_message = 'WP2Static ' .
                $action . ' ' .
                $this->target_path;
        }

        try {
            $post_options = array(
                'message' => $commit_message,
                'content' => $b64_file_contents,
                'branch' => $this->settings['ghBranch'],
            );

            $headers = array(
                'Authorization: ' .
                        'token ' . $this->settings['ghToken'],
            );

            $this->client->putWithJSONPayloadCustomHeaders(
                $this->remote_path,
                $post_options,
                $headers,
                $curl_options = array(
                    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                )
            );

            $this->debugResponseBody();

            $this->checkForValidResponses(
                $this->client->status_code,
                array( '200', '201', '301', '302', '304' )
            );

        } catch ( Exception $e ) {
            $this->handleException( $e );
        }
    }

    public function debugResponseBody() {
        if ( ! isset( $this->settings['debug_mode'] ) ) {
            return;
        }

        require_once dirname( __FILE__ ) .
            '/../library/StaticHtmlOutput/WsLog.php';
        WsLog::l(
            '# API response body' . $this->client->body
        );
    }
}

$github = new StaticHtmlOutput_GitHub();
