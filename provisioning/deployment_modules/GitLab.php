<?php

class StaticHtmlOutput_GitLab extends StaticHtmlOutput_SitePublisher {

    public function __construct() {
        $this->loadSettings( 'gitlab' );

        $this->files_in_repo_list_path =
            $this->settings['wp_uploads_path'] .
                '/WP2STATIC-GITLAB-FILES-IN-REPO.txt';

        if ( defined( 'WP_CLI' ) ) {
            return;
        }

        switch ( $_POST['ajax_action'] ) {
            case 'gitlab_prepare_export':
                $this->bootstrap();
                $this->loadArchive();
                $this->getListOfFilesInRepo();

                $this->prepareDeploy( true );
                break;
            case 'gitlab_upload_files':
                $this->bootstrap();
                $this->loadArchive();
                $this->upload_files();
                break;
            case 'test_gitlab':
                $this->test_file_create();
                break;
        }
    }

    public function upload_files() {
        $this->files_remaining = $this->getRemainingItemsCount();

        if ( $this->files_remaining < 0 ) {
            echo 'ERROR';
            die();
        }

        $this->initiateProgressIndicator();

        $batch_size = $this->settings['deployBatchSize'];

        if ( $batch_size > $this->files_remaining ) {
            $batch_size = $this->files_remaining;
        }

        $lines = $this->getItemsToDeploy( $batch_size );

        $files_in_tree = file(
            $this->files_in_repo_list_path,
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );

        $files_data = array();

        foreach ( $lines as $line ) {
            list($local_file, $target_path) = explode( ',', $line );

            $local_file = $this->archive->path . $local_file;

            if ( ! is_file( $local_file ) ) {
                continue;
            }

            if ( isset( $this->settings['glPath'] ) ) {
                $target_path = $this->settings['glPath'] . '/' . $target_path;
            }

            if ( in_array( $target_path, $files_in_tree ) ) {
                $files_data[] = array(
                    'action' => 'update',
                    'file_path' => $target_path,
                    'content' => base64_encode(
                        file_get_contents( $local_file )
                    ),
                    'encoding' => 'base64',
                );
            } else {
                $files_data[] = array(
                    'action' => 'create',
                    'file_path' => $target_path,
                    'content' => base64_encode(
                        file_get_contents( $local_file )
                    ),
                    'encoding' => 'base64',
                );
            }

            // NOTE: delay and progress askew in GitLab as we may
            // upload all in one  request. Progress indicates building
            // of list of files that will be deployed/checking if different
            $this->updateProgress();
        }

        $this->pauseBetweenAPICalls();

        $commits_endpoint = 'https://gitlab.com/api/v4/projects/' .
            $this->settings['glProject'] . '/repository/commits';

        try {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/Request.php';
            $client = new WP2Static_Request();

            $post_options = array(
                'branch' => 'master',
                'commit_message' => 'WP2Static Deployment',
                'actions' => $files_data,
            );

            $headers = array(
                'PRIVATE-TOKEN: ' . $this->settings['glToken'],
                'Content-Type: application/json',
            );

            $client->postWithJSONPayloadCustomHeaders(
                $commits_endpoint,
                $post_options,
                $headers
            );

            $this->checkForValidResponses(
                $client->status_code,
                array( '200', '201', '301', '302', '304' )
            );
        } catch ( Exception $e ) {
            $this->handleException( $e );
        }

        if ( $this->uploadsCompleted() ) {
            $this->createGitLabPagesConfig();

            $this->finalizeDeployment();
        }
    }

    public function addToListOfFilesInRepos( $items ) {
        file_put_contents(
            $this->files_in_repo_list_path,
            implode( PHP_EOL, $items ) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    public function getFilePathsFromTree( $json_response ) {
        $partial_tree_array = json_decode( (string) $json_response, true );

        $formatted_elements = array();

        foreach ( $partial_tree_array as $object ) {
            if ( $object['type'] === 'blob' ) {
                $formatted_elements[] = $object['path'];
            }
        }

        return $formatted_elements;
    }

    public function getRepositoryTree( $page ) {
        $tree_endpoint = 'https://gitlab.com/api/v4/projects/' .
            $this->settings['glProject'] .
            '/repository/tree?recursive=true&per_page=100&page=' . $page;

        require_once dirname( __FILE__ ) .
            '/../library/StaticHtmlOutput/Request.php';
        $client = new WP2Static_Request();

        $headers = array(
            'PRIVATE-TOKEN: ' . $this->settings['glToken'],
            'Content-Type: application/json',
        );

        $client->getWithCustomHeaders(
            $tree_endpoint,
            $headers
        );

        $good_response_codes = array( '200', '201', '301', '302', '304' );

        if ( ! in_array( $client->status_code, $good_response_codes ) ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/WsLog.php';
            WsLog::l(
                'BAD RESPONSE STATUS (' . $client->status_code . '): '
            );

            throw new Exception( 'GitLab API bad response status' );
        }

        $total_pages = $client->headers['x-total-pages'];
        $next_page = $client->headers['x-next-page'];
        $current_page = $client->headers['x-page'];

        // if we have results, append them to files to delete array
        $json_items = $client->body;

        $this->addToListOfFilesInRepos(
            $this->getFilePathsFromTree( $json_items )
        );

        // if current page is less than total pages
        if ( $current_page < $total_pages ) {
            // call this again with an increment
            $this->getRepositoryTree( $next_page );
        }
    }

    public function getListOfFilesInRepo() {
        $this->getRepositoryTree( 1 );
    }

    public function test_file_create() {
        $remote_path = 'https://gitlab.com/api/v4/projects/' .
            $this->settings['glProject'] . '/repository/commits';

        try {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/Request.php';
            $client = new WP2Static_Request();

            $post_options = array(
                'branch' => 'master',
                'commit_message' => 'test deploy from plugin',
                'actions' => array(
                    array(
                        'action' => 'create',
                        'file_path' => '.wpsho_' . time(),
                        'content' => 'test file',
                    ),
                    array(
                        'action' => 'create',
                        'file_path' => '.wpsho2_' . time(),
                        'content' => 'test file 2',
                    ),
                ),
            );

            $headers = array(
                'PRIVATE-TOKEN: ' . $this->settings['glToken'],
                'Content-Type: application/json',
            );

            $client->postWithJSONPayloadCustomHeaders(
                $remote_path,
                $post_options,
                $headers
            );

            $this->checkForValidResponses(
                $client->status_code,
                array( '200', '201', '301', '302', '304' )
            );
        } catch ( Exception $e ) {
            $this->handleException( $e );
        }

        $this->finalizeDeployment();
    }

    public function createGitLabPagesConfig() {
        // NOTE: required for GitLab Pages to build static site
        $config_file = <<<EOD
pages:
  stage: deploy
  script:
  - mkdir .public
  - cp -r * .public
  - mv .public public
  artifacts:
    paths:
    - public
  only:
  - master

EOD;

        $target_path = $this->archive->path . '.gitlab-ci.yml';
        file_put_contents( $target_path, $config_file );
        chmod( $target_path, 0664 );
        $export_line = '.gitlab-ci.yml,.gitlab-ci.yml';

        file_put_contents(
            $this->export_file_list,
            $export_line . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        chmod( $this->export_file_list, 0664 );
    }
}

$gitlab = new StaticHtmlOutput_GitLab();
