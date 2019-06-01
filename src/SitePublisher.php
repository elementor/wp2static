<?php

namespace WP2Static;

use Exception;

class SitePublisher {

    public $settings;
    public $export_file_list;
    public $files_remaining;
    public $file_paths_and_hashes;
    public $previous_hashes_path;

    public function loadSettings() : void {
        $plugin = Controller::getInstance();
        $this->settings = $plugin->options->getSettings( true );
    }

    public function bootstrap() : void {
        $this->export_file_list =
            SiteInfo::getPath( 'uploads' ) .
                'wp2static-working-files/FILES-TO-DEPLOY.txt';
    }

    public function pauseBetweenAPICalls() : void {
        if ( isset( $this->settings['delayBetweenAPICalls'] ) &&
            $this->settings['delayBetweenAPICalls'] > 0 ) {
            sleep( $this->settings['delayBetweenAPICalls'] );
        }
    }

    public function clearFileList() : void {
        if ( is_file( $this->export_file_list ) ) {
            $f = fopen( $this->export_file_list, 'r+' );
            if ( $f !== false ) {
                ftruncate( $f, 0 );
                fclose( $f );
            }
        }

        if ( isset( $this->glob_hash_path_list ) ) {
            if ( is_file( $this->glob_hash_path_list ) ) {
                $f = fopen( $this->glob_hash_path_list, 'r+' );
                if ( $f !== false ) {
                    ftruncate( $f, 0 );
                    fclose( $f );
                }
            }
        }
    }

    public function isSkippableFile( string $file ) : bool {
        if ( $file == '.' || $file == '..' || $file == '.git' ) {
            return true;
        }

        return false;
    }

    public function getLocalFileToDeploy(
        string $file_in_archive,
        string $replace_path
    ) : string {
        // NOTE: untested fix for Windows filepaths
        // https://github.com/leonstafford/wp2static/issues/221
        $original_filepath = str_replace(
            '\\',
            '\\\\',
            $file_in_archive
        );

        $original_file_without_archive = str_replace(
            $replace_path,
            '',
            $original_filepath
        );

        $original_file_without_archive = ltrim(
            $original_file_without_archive,
            '/'
        );

        return $original_file_without_archive;
    }

    public function getRemoteDeploymentPath(
        string $dir,
        string $file_in_archive,
        string $archive_path,
        string $basename_in_target
    ) : string {
        $deploy_path = str_replace(
            $archive_path,
            '',
            $dir
        );

        $deploy_path = ltrim( $deploy_path, '/' );
        $deploy_path .= '/';

        if ( $basename_in_target ) {
            $deploy_path .= basename(
                $file_in_archive
            );
        }

        $deploy_path = ltrim( $deploy_path, '/' );

        return $deploy_path;
    }

    public function createDeploymentList(
        string $dir,
        string $basename_in_target
    ) : void {
        $archive_path =
            SiteInfo::getPath( 'uploads' ) . 'wp2static-exported-site';

        $dir_files = scandir( $dir );

        if ( ! $dir_files ) {
            $err = "Couldn't get files in dir: " . $dir;
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        foreach ( $dir_files as $item ) {
            if ( $this->isSkippableFile( $item ) ) {
                continue;
            }

            $file_in_archive = $dir . '/' . $item;

            if ( is_dir( $file_in_archive ) ) {
                $this->createDeploymentList(
                    $file_in_archive,
                    $basename_in_target
                );
            } elseif ( is_file( $file_in_archive ) ) {
                $local_file_path =
                    $this->getLocalFileToDeploy(
                        $file_in_archive,
                        $archive_path
                    );

                $remote_deployment_path =
                    $this->getRemoteDeploymentPath(
                        $dir,
                        $file_in_archive,
                        $archive_path,
                        $basename_in_target
                    );

                file_put_contents(
                    $this->export_file_list,
                    $local_file_path . ',' . $remote_deployment_path . PHP_EOL,
                    FILE_APPEND | LOCK_EX
                );

                chmod( $this->export_file_list, 0664 );
            }
        }
    }

    public function prepareDeploy( string $basename_in_target = '' ) : void {
        $this->clearFileList();

        $this->createDeploymentList(
            SiteInfo::getPath( 'uploads' ) . 'wp2static-exported-site/',
            $basename_in_target
        );

        $via_ui = filter_input( INPUT_POST, 'ajax_action' );

        if ( is_string( $via_ui ) ) {
            echo 'SUCCESS';
        }
    }

    /**
     * Get list of items to deploy
     *
     * @throws WP2StaticException
     * @return string[] list of URLs
     */
    public function getItemsToDeploy( int $batch_size = 1 ) : array {
        $lines = array();

        $f = fopen( $this->export_file_list, 'r' );

        if ( ! $f ) {
            $err = 'Failed to open export file list';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        for ( $i = 0; $i < $batch_size; $i++ ) {
            $item_to_deploy = fgets( $f );

            if ( ! is_string( $item_to_deploy ) ) {
                $err = 'Failed getting item to deploy';
                WsLog::l( $err );
                throw new WP2StaticException( $err );
            }

            $lines[] = rtrim( $item_to_deploy );
        }

        fclose( $f );

        // TODO: optimize this for just one read, one write within func
        $contents = file( $this->export_file_list, FILE_IGNORE_NEW_LINES );

        if ( ! is_array( $contents ) ) {
            return [];
        }

        for ( $i = 0; $i < $batch_size; $i++ ) {
            // rewrite file minus the lines we took
            array_shift( $contents );
        }

        file_put_contents(
            $this->export_file_list,
            implode( PHP_EOL, $contents )
        );

        chmod( $this->export_file_list, 0664 );

        return $lines;
    }

    public function getRemainingItemsCount() : int {
        if ( is_file( $this->export_file_list ) ) {
            $contents = file( $this->export_file_list, FILE_IGNORE_NEW_LINES );

            if ( ! is_array( $contents ) ) {
                return 0;
            }

            return count( $contents );
        }

        return 0;
    }

    // TODO: rename to signalSuccessfulAction or such
    // as is used in deployment tests/not just finalizing deploys
    public function finalizeDeployment() : void {
        $via_ui = filter_input( INPUT_POST, 'ajax_action' );

        if ( is_string( $via_ui ) ) {
            echo 'SUCCESS'; }
    }

    public function uploadsCompleted() : bool {
        $this->files_remaining = $this->getRemainingItemsCount();

        if ( $this->files_remaining <= 0 ) {
            return true;
        } else {
            echo $this->files_remaining;
            return false;
        }
    }

    /**
     *  Check response code from a list of accpetable ones
     *
     *  @param string[] $good_codes Acceptable status codes
     */
    public function checkForValidResponses(
        int $code,
        array $good_codes
    ) : bool {
        if ( ! in_array( $code, $good_codes ) ) {
            return false;
        }

        return true;
    }

    public function openPreviousHashesFile() : void {
        $this->file_paths_and_hashes = array();

        if ( is_file( $this->previous_hashes_path ) ) {
            $file = fopen( $this->previous_hashes_path, 'r' );

            if ( ! $file ) {
                return;
            }

            while ( ( $line = fgetcsv( $file ) ) !== false ) {
                if ( isset( $line[0] ) && isset( $line[1] ) ) {
                    $this->file_paths_and_hashes[ $line[0] ] = $line[1];
                }
            }

            fclose( $file );
        }
    }

    public function recordFilePathAndHashInMemory(
        string $target_path,
        string $local_file_contents
        ) : void {
        $this->file_paths_and_hashes[ $target_path ] =
            crc32( $local_file_contents );
    }

    public function writeFilePathAndHashesToFile() : void {
        $fp = fopen( $this->previous_hashes_path, 'w' );

        if ( ! $fp ) {
            $err = "Couldn't open previous hashes file for writing";
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        foreach ( $this->file_paths_and_hashes as $key => $value ) {
            fwrite( $fp, $key . ',' . $value . PHP_EOL );
        }

        fclose( $fp );
    }
}

