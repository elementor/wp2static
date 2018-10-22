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

    // TODO: move to a parent class as identical to bunny and probably others
    public function prepare_deployment() {
            error_log('preparing deployment');
            $this->clear_file_list();
            $this->create_gitlab_deployment_list(
                $this->settings['working_directory'] . '/' .
                    $this->archive->name
            );

            $this->delete_all_files_in_branch();

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

    public function delete_all_files_in_branch() {
        require_once dirname( __FILE__ ) .
            '/../GuzzleHttp/autoloader.php';

        $client = new Client(
            array(
                'base_uri' => $this->api_base,
            )
        );

        try {
            $response = $client->request(
                'GET',
                'https://gitlab.com/api/v4/projects/' . $this->settings['glProject'] . '/repository/tree?recursive=true',
                array(
                    'headers'  => array(
                        'PRIVATE-TOKEN' => $this->settings['glToken'],
                    )
                )
            );

            $repo_tree = json_decode((string) $response->getBody(), true);


            $files_to_delete = array();

            foreach($repo_tree as $potential_file) {
                if ($potential_file['type'] === 'blob') {
                //if (true) {
                    error_log($potential_file['path']);
                    $files_to_delete[] = array(
                        'action' => 'delete',
                        'file_path' => $potential_file['path'],
                    );
                }
            }
            
            // EMPTY!!
            error_log(print_r($files_to_delete, true));

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
                        'actions' => $files_to_delete,
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
                base64_encode(file_get_contents( $fileToTransfer )),
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
