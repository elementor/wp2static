<?php

use GuzzleHttp\Client;

class StaticHtmlOutput_GitLab {

    public function __construct() {
        $target_settings = array(
            'general',
            'wpenv',
            'gitlab',
            'advanced',
        );

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $target_settings );
        } else {
            error_log( 'TODO: load settings from DB' );
        }

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
        $this->files_to_delete = array();

        $this->api_base = '';

        switch ( $_POST['ajax_action'] ) {
            case 'gitlab_prepare_export':
                $this->prepare_deployment();
                break;
            case 'gitlab_upload_files':
                $this->upload_files();
                break;
            case 'test_gitlab':
                $this->test_file_create();
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
    public function create_gitlab_deployment_list( $dir ) {
        $r_path = '';
        $archive = $this->archive->path;

        if ( isset( $this->settings['glPath'] ) ) {
            $r_path = $this->settings['glPath'];
        }

        $files = scandir( $dir );

        foreach ( $files as $item ) {
            if ( $item != '.' && $item != '..' && $item != '.git' ) {
                if ( is_dir( $dir . '/' . $item ) ) {
                    $this->create_gitlab_deployment_list(
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

    public function createGitLabPagesConfig() {
        // GL doesn't seem to build the pages unless this file is detected
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

        // force include the gitlab config file
        $gitlab_config_file = $this->archive->path . '.gitlab-ci.yml';
        $export_line = $gitlab_config_file . ',' . '.gitlab-ci.yml';
        file_put_contents( $this->exportFileList, $export_line . PHP_EOL, FILE_APPEND | LOCK_EX );
    }

    // TODO: move to a parent class as identical to bunny and probably others
    public function prepare_deployment() {
            $this->clear_file_list();
            $this->create_gitlab_deployment_list(
                $this->settings['working_directory'] . '/' .
                    $this->archive->name
            );

            $this->delete_all_files_in_branch();

            $this->createGitLabPagesConfig();

            echo 'SUCCESS';
    }

    public function mergeItemsForDeletion( $items ) {
        $old_items = $this->files_to_delete;

        $this->files_to_delete = array_merge($this->files_to_delete, $items);
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

    public function partialTreeToDeletionElements( $json_response ) {
        $partial_tree_array = json_decode( (string) $json_response, true );

        $formatted_elements = array();

        foreach($partial_tree_array as $object) {
            //if ($object['type'] === 'blob' || $object['type'] === 'tree') {
            if ($object['type'] === 'blob') {
                $formatted_elements[] = array(
                    'action' => 'delete',
                    'file_path' => $object['path'],
                );
            }
        }

        return $formatted_elements;
    }

    public function getRepositoryTree( $page ) {
        // make request and get results, including total pages
        require_once dirname( __FILE__ ) .
            '/../GuzzleHttp/autoloader.php';

        $client = new Client(
            array(
                'base_uri' => $this->api_base,
            )
        );

        $response = $client->request(
            'GET',
            'https://gitlab.com/api/v4/projects/' . $this->settings['glProject'] . '/repository/tree?recursive=true&per_page=100&page=' . $page,
            array(
                'headers'  => array(
                    'PRIVATE-TOKEN' => $this->settings['glToken'],
                )
            )
        );

        $total_pages = $response->getHeader('X-Total-Pages');
        $next_page = $response->getHeader('X-Next-Page');
        $current_page = $response->getHeader('X-Page');
        $total_pages = $total_pages[0];
        $next_page = $next_page[0];
        $current_page = $current_page[0];

        // if we have results, append them to files to delete array 
        $json_items = $response->getBody();
        $this->mergeItemsForDeletion($this->partialTreeToDeletionElements( $json_items ) );

        // if current page is less than total pages
        if ( $current_page < $total_pages ) {
            // call this again with an increment
            $this->getRepositoryTree( $next_page );
        }

        return;
    }

    public function getListOfFilesInRepo() {
        $this->getRepositoryTree( 1 );
    }

    public function delete_all_files_in_branch() {
        $this->getListOfFilesInRepo();

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
                'https://gitlab.com/api/v4/projects/' . $this->settings['glProject'] . '/repository/commits',
                array(
                    'headers'  => array(
                        'PRIVATE-TOKEN' => $this->settings['glToken'],
                        'content-type' => 'application/json',
                    ),
                    'json' => array(
                        'branch' => 'master',
                        'commit_message' => 'test deploy from plugin',
                        'actions' => $this->files_to_delete,
                    )
                )
            );
        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
            WsLog::l( 'GITLAB EXPORT: error encountered' );
            WsLog::l( $e );
            error_log( $e );
            throw new Exception( $e );
            return;
        }
    }

    public function upload_files( $viaCLI = false ) {
        require_once dirname( __FILE__ ) .
            '/../GuzzleHttp/autoloader.php';

        $filesRemaining = $this->get_remaining_items_count();

        if ( $filesRemaining < 0 ) {
            echo 'ERROR';
            die();
        }

        $batch_size = $this->settings['glBlobIncrement'];

        if ( $batch_size > $filesRemaining ) {
            $batch_size = $filesRemaining;
        }

        $lines = $this->get_items_to_export( $batch_size );
        $globHashPathLines = array();

        $files_data = array();

        foreach ( $lines as $line ) {
            list($fileToTransfer, $targetPath) = explode( ',', $line );

            $files_data[] = array(
                'action' => 'create',
                'file_path' => rtrim( $targetPath ),
                'content' => base64_encode(file_get_contents( $fileToTransfer )),
                'encoding' => 'base64',
            );
        }

        if ( isset( $this->settings['glBlobDelay'] ) &&
            $this->settings['glBlobDelay'] > 0 ) {
            sleep( $this->settings['glBlobDelay'] );
        }

        $client = new Client(
            array(
                'base_uri' => $this->api_base,
            )
        );

        try {
            $response = $client->request(
                'POST',
                'https://gitlab.com/api/v4/projects/' . $this->settings['glProject'] . '/repository/commits',
                array(
                    'headers'  => array(
                        'PRIVATE-TOKEN' => $this->settings['glToken'],
                        'content-type' => 'application/json',
                    ),
                    'json' => array(
                        'branch' => 'master',
                        'commit_message' => 'static publish from WP',
                        'actions' => $files_data,
                    )
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

    public function test_file_create() {
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
                'https://gitlab.com/api/v4/projects/' . $this->settings['glProject'] . '/repository/commits',
                array(
                    'headers'  => array(
                        'PRIVATE-TOKEN' => $this->settings['glToken'],
                        'content-type' => 'application/json',
                    ),
                    'json' => array(
                        'branch' => 'master',
                        'commit_message' => 'test deploy from plugin',
                        'actions' => array(
                            array(
                                'action' => 'create',
                                'file_path' => '.wpsho_' .  time(),
                                'content' => 'test file',
                            ),
                            array(
                                'action' => 'create',
                                'file_path' => '.wpsho2_' .  time(),
                                'content' => 'test file 2',
                            ),
                        ),
                    )
                )
            );

        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
            WsLog::l( 'GITLAB EXPORT: error encountered' );
            WsLog::l( $e );
            error_log( $e );
            throw new Exception( $e );
            return;
        }

        echo 'SUCCESS';
    }
}

$gitlab = new StaticHtmlOutput_GitLab();
