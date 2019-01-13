<?php

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
                '/../library/StaticHtmlOutput/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $target_settings );
        } else {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/DBSettings.php';

            $this->settings = WPSHO_DBSettings::get( $target_settings );
        }

        list($this->user, $this->repository) = explode(
            '/',
            $this->settings['ghRepo']
        );

        $this->export_file_list =
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT.txt';
        $archive_dir = file_get_contents(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-CURRENT-ARCHIVE.txt'
        );

        $this->r_path = '';

        if ( isset( $this->settings['ghPath'] ) ) {
            $this->r_path = $this->settings['ghPath'];
        }

        // TODO: move this where needed
        require_once dirname( __FILE__ ) .
            '/../library/StaticHtmlOutput/Archive.php';
        $this->archive = new Archive();
        $this->archive->setToCurrentArchive();

        $this->api_base = 'https://api.github.com/repos/';

        switch ( $_POST['ajax_action'] ) {
            case 'github_prepare_export':
                $this->prepare_export( true );
                break;
            case 'github_upload_files':
                $this->upload_files();
                break;
            case 'test_github':
                $this->test_upload();
                break;
        }
    }

    public function upload_files() {
        $files_remaining = $this->get_remaining_items_count();

        if ( $files_remaining < 0 ) {
            echo 'ERROR';
            die();
        }

        $batch_size = $this->settings['ghBlobIncrement'];

        if ( $batch_size > $files_remaining ) {
            $batch_size = $files_remaining;
        }

        $lines = $this->get_items_to_export( $batch_size );

        $deploy_count_path = $this->settings['wp_uploads_path'] .
                '/WP-STATIC-TOTAL-FILES-TO-DEPLOY.txt';
        $total_urls_to_crawl = file_get_contents( $deploy_count_path );

        $batch_index = 0;

        foreach ( $lines as $line ) {
            list($local_file, $target_path) = explode( ',', $line );

            $local_file = $this->archive->path . $local_file;
            $target_path = rtrim( $target_path );

            $remote_path = $this->api_base . $this->settings['ghRepo'] .
                '/contents/' . $target_path;

            // GraphQL query to get sha of existing file
            $query = <<<JSON
query{
  repository(owner: "{$this->user}", name: "{$this->repository}") {
    object(expression: "{$this->settings['ghBranch']}:{$target_path}") {
      ... on Blob {
        oid
        byteSize
      }
    }
  }
}
JSON;

            $variables = '';

            $json = array(
                'query' => $query,
                'variables' => $variables,
            );

            $ch = curl_init();

            curl_setopt( $ch, CURLOPT_URL, 'https://api.github.com/graphql' );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
            curl_setopt( $ch, CURLOPT_HEADER, 0 );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
            curl_setopt( $ch, CURLOPT_POST, 1 );
            curl_setopt( $ch, CURLOPT_USERAGENT, 'WP2Static.com' );
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
            curl_setopt( $ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2 );

            $post_options = $json;

            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                json_encode( $json )
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

            $gh_file_info = json_decode( $output, true );

            $existing_file_object =
                $gh_file_info['data']['repository']['object'];

            $skip_same_bytesize = isset( $this->settings['ghSkipSameBytes'] );

            $action = '';
            $commit_message = '';

            if ( ! empty( $existing_file_object ) ) {
                $action = 'UPDATE';
                $existing_sha = $existing_file_object['oid'];
                $existing_bytesize = $existing_file_object['byteSize'];

                $file_contents = file_get_contents( $local_file );
                $b64_file_contents = base64_encode( $file_contents );
                $local_sha = sha1( $b64_file_contents );
                $local_length = strlen( $b64_file_contents );
                $local_length_unencoded = strlen( $file_contents );

                $bytesize_match = $existing_bytesize == $local_length_unencoded;

                if ( ! ( $bytesize_match && $skip_same_bytesize ) ) {
                    if ( isset( $this->settings['ghCommitMessage'] ) ) {
                        $commit_message = str_replace(
                            array(
                                '%ACTION%',
                                '%FILENAME%',
                            ),
                            array(
                                $action,
                                $target_path,
                            ),
                            $this->settings['ghCommitMessage']
                        );
                    } else {
                        $commit_message = 'WP2Static ' .
                            $action . ' ' .
                            $target_path;
                    }

                    try {
                        $ch = curl_init();

                        curl_setopt( $ch, CURLOPT_URL, $remote_path );
                        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
                        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
                        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
                        curl_setopt( $ch, CURLOPT_HEADER, 0 );
                        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
                        curl_setopt( $ch, CURLOPT_POST, 1 );
                        curl_setopt( $ch, CURLOPT_USERAGENT, 'WP2Static.com' );
                        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT' );

                        $post_options = array(
                            'message' => $commit_message,
                            'content' => $b64_file_contents,
                            'branch' => $this->settings['ghBranch'],
                            'sha' => $existing_sha,
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

                        $good_response_codes =
                            array( '200', '201', '301', '302', '304' );

                        if (
                            ! in_array( $status_code, $good_response_codes )
                            ) {
                            require_once dirname( __FILE__ ) .
                                '/../library/StaticHtmlOutput/WsLog.php';
                            WsLog::l(
                                'BAD RESPONSE STATUS (' . $status_code . '): '
                            );

                            throw new Exception(
                                'GitHub API bad response status'
                            );
                        }
                    } catch ( Exception $e ) {
                        require_once dirname( __FILE__ ) .
                            '/../library/StaticHtmlOutput/WsLog.php';
                        WsLog::l( 'GITHUB EXPORT: error encountered' );
                        WsLog::l( $e );
                        throw new Exception( $e );
                        return;
                    }
                }
            } else {
                $action = 'CREATE';

                $file_contents = file_get_contents( $local_file );
                $b64_file_contents = base64_encode( $file_contents );

                if ( isset( $this->settings['ghCommitMessage'] ) ) {
                    $commit_message = str_replace(
                        array(
                            '%ACTION%',
                            '%FILENAME%',
                        ),
                        array(
                            $action,
                            $target_path,
                        ),
                        $this->settings['ghCommitMessage']
                    );
                } else {
                    $commit_message = 'WP2Static ' .
                        $action . ' ' .
                        $target_path;
                }

                try {
                    $ch = curl_init();

                    curl_setopt( $ch, CURLOPT_URL, $remote_path );
                    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
                    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
                    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
                    curl_setopt( $ch, CURLOPT_HEADER, 0 );
                    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
                    curl_setopt( $ch, CURLOPT_POST, 1 );
                    curl_setopt( $ch, CURLOPT_USERAGENT, 'WP2Static.com' );
                    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT' );

                    $post_options = array(
                        'message' => $commit_message,
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

                    $good_response_codes =
                        array( '200', '201', '301', '302', '304' );

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
            }

            $batch_index++;

            $completed_urls =
                $total_urls_to_crawl -
                $files_remaining +
                $batch_index;

            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/ProgressLog.php';
            ProgressLog::l( $completed_urls, $total_urls_to_crawl );
        }

        if ( isset( $this->settings['ghBlobDelay'] ) &&
            $this->settings['ghBlobDelay'] > 0 ) {
            sleep( $this->settings['ghBlobDelay'] );
        }

        $files_remaining = $this->get_remaining_items_count();

        if ( $files_remaining > 0 ) {
            if ( defined( 'WP_CLI' ) ) {
                $this->upload_files();
            } else {
                echo $files_remaining;
            }
        } else {
            if ( ! defined( 'WP_CLI' ) ) {
                echo 'SUCCESS';
            }
        }
    }

    public function test_upload() {
        try {
            $remote_path = $this->api_base . $this->settings['ghRepo'] .
                '/contents/' . '.WP2Static/' . uniqid();

            $b64_file_contents = base64_encode( 'WP2Static test upload' );

            $ch = curl_init();

            curl_setopt( $ch, CURLOPT_URL, $remote_path );
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
}

$github = new StaticHtmlOutput_GitHub();
