<?php

use GuzzleHttp\Client;

class StaticHtmlOutput_GitHub {

    protected $_user;
    protected $_repository;
    protected $_accessToken;
    protected $_branch;
    protected $_remotePath;
    protected $_uploadsPath;
    protected $_exportFileList;
    protected $_globHashAndPathList;
    protected $_archiveName;

    // TODO: args to come from post or settings
    public function __construct( $repo, $token, $branch, $r_path, $upl_path ) {
        list($this->_user, $this->_repository) = explode( '/', $repo );
        $this->_accessToken = $token;
        $this->_branch = $branch;
        $this->_remotePath = $r_path;
        $this->_exportFileList =
            $upl_path . '/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT';
        $this->_globHashAndPathList =
            $upl_path . '/WP-STATIC-EXPORT-GITHUB-GLOBS-PATHS';
        $archiveDir = file_get_contents(
            $upl_path . '/WP-STATIC-CURRENT-ARCHIVE'
        );
        $this->_archiveName = rtrim( $archiveDir, '/' );
    }

    public function clear_file_list() {
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
    public function create_github_deployment_list( $dir, $archive, $r_path ) {
        $files = scandir( $dir );

        foreach ( $files as $item ) {
            if ( $item != '.' && $item != '..' && $item != '.git' ) {
                if ( is_dir( $dir . '/' . $item ) ) {
                    $this->create_github_deployment_list(
                        $dir . '/' . $item,
                        $archive,
                        $r_path
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

    // TODO: move to a parent class as identical to bunny and probably others
    public function prepare_deployment() {

            $this->clear_file_list();
            $this->create_github_deployment_list(
                $this->_archiveName,
                $this->_archiveName,
                $this->_remotePath
            );

            echo 'SUCCESS';
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


    public function upload_blobs( $viaCLI = false ) {
            require_once dirname( __FILE__ ) .
                '/../GuzzleHttp/autoloader.php';
            require_once __DIR__ . '/../Github/autoload.php';

        if ( $this->get_remaining_items_count() < 0 ) {
            echo 'ERROR';
            die();
        }

            $line = $this->get_item_to_export();

            list($fileToTransfer, $targetPath) = explode( ',', $line );

            $targetPath = rtrim( $targetPath );

            // vendor specific from here
            $encodedFile = chunk_split(
                base64_encode( file_get_contents( $fileToTransfer ) )
            );
            $client = new \Github\Client();
            $client->authenticate(
                $this->_accessToken,
                Github\Client::AUTH_HTTP_TOKEN
            );

        try {
            $globHash = $client->api( 'gitData' )->blobs()->create(
                $this->_user,
                $this->_repository,
                array(
                    'content' => $encodedFile,
                    'encoding' => 'base64',
                )
            ); // utf-8 or base64
        } catch ( Exception $e ) {
            WsLog::l( 'GITHUB: Error creating blob (API limits?):' . $e );
            error_log( 'error creating blog in GitHub (API limits?)' );
            // TODO:  rate limits: https://developer.github.com/v3/rate_limit/
            $coreLimit = $client->api( 'rate_limit' )->getCoreLimit();
            error_log( $coreLimit );
        }

            $globHashPathLine = $globHash['sha'] . ',' .
                rtrim( $targetPath ) . basename( $fileToTransfer ) . "\n";

            file_put_contents(
                $this->_globHashAndPathList,
                $globHashPathLine,
                FILE_APPEND | LOCK_EX
            );

            $filesRemaining = $this->get_remaining_items_count();

        if ( $this->get_remaining_items_count() > 0 ) {

            if ( $viaCLI ) {
                $this->upload_blobs( true );
            }

            echo $this->get_remaining_items_count();
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
            $this->_accessToken,
            Github\Client::AUTH_HTTP_TOKEN
        );
        $reference = $client->api( 'gitData' )->references()->show(
            $this->_user,
            $this->_repository,
            'heads/' . $this->_branch
        );
        $commit = $client->api( 'gitData' )->commits()->show(
            $this->_user,
            $this->_repository,
            $reference['object']['sha']
        );
        $commitSHA = $commit['sha'];
        $treeSHA = $commit['tree']['sha'];
        $treeURL = $commit['tree']['url'];
        $treeContents = array();
        $contents = file( $this->_globHashAndPathList );

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
            $this->_user,
            $this->_repository,
            $treeData
        );

        $commitData = array(
            'message' =>
                'WP Static HTML Output plugin: ' . date( 'Y-m-d h:i:s' ),
            'tree' => $newTree['sha'],
            'parents' => array( $commitSHA ),
        );
        $commit = $client->api( 'gitData' )->commits()->create(
            $this->_user,
            $this->_repository,
            $commitData
        );
        $referenceData = array(
            'sha' => $commit['sha'],
            'force' => true,
        ); // Force is default false

        try {
            $reference = $client->api( 'gitData' )->references()->update(
                $this->_user,
                $this->_repository,
                'heads/' . $this->_branch,
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

}
