<?php

namespace WP2Static;

use Exception;

class SitePublisher {

    public function loadSettings( $deploy_method, $specify_keys = array() ) {
        $target_settings = array(
            'general',
            'wpenv',
            'advanced',
        );

        $target_settings[] = $deploy_method;

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            $this->settings =
                PostSettings::get( $target_settings, $specify_keys );
        } else {
            $this->settings =
                DBSettings::get( $target_settings, $specify_keys );
        }
    }

    public function bootstrap() {
        $site_info = new SiteInfo();
        $site_info = $site_info->get();

        $this->export_file_list =
            $site_info['uploads_path'] .
                '/wp2static-working-files/FILES-TO-DEPLOY.txt';

        $this->archive_dir = '/wp2static-exported-site/';
    }

    public function pauseBetweenAPICalls() {
        if ( isset( $this->settings['delayBetweenAPICalls'] ) &&
            $this->settings['delayBetweenAPICalls'] > 0 ) {
            sleep( $this->settings['delayBetweenAPICalls'] );
        }
    }

    public function clearFileList() {
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

    public function isSkippableFile( $file ) {
        if ( $file == '.' || $file == '..' || $file == '.git' ) {
            return true;
        }
    }

    public function getLocalFileToDeploy( $file_in_archive, $replace_path ) {
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

        if ( ! is_string( $original_file_without_archive ) ) {
            return false;
        }

        $original_file_without_archive = ltrim(
            $original_file_without_archive,
            '/'
        );

        return $original_file_without_archive;
    }

    public function getArchivePathForReplacement( $archive_path ) {
        $local_path_to_strip = $archive_path;
        $local_path_to_strip = rtrim( $local_path_to_strip, '/' );

        $local_path_to_strip = str_replace(
            '//',
            '/',
            $local_path_to_strip
        );

        return $local_path_to_strip;
    }

    public function getRemoteDeploymentPath(
        $dir, $file_in_archive, $archive_path_to_replace, $basename_in_target
        ) {
        $deploy_path = str_replace(
            $archive_path_to_replace,
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

    public function createDeploymentList( $dir, $basename_in_target ) {
        $archive_path_to_replace =
            $this->getArchivePathForReplacement( $this->archive->path );

        $dir_files = scandir( $dir );

        if ( ! $dir_files ) {
            $err = "Couldn't get files in dir: " . $dir;
            WsLog::l( $err );
            throw new Exception( $err );
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
                        $archive_path_to_replace
                    );

                $remote_deployment_path =
                    $this->getRemoteDeploymentPath(
                        $dir,
                        $file_in_archive,
                        $archive_path_to_replace,
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

    public function prepareDeploy( $basename_in_target = false ) {
        $site_info = new SiteInfo();
        $site_info = $site_info->get();

        $this->clearFileList();

        $this->createDeploymentList(
            $site_info['uploads_path'] . '/wp2static-exported-site/',
            $basename_in_target
        );

        if ( ! defined( 'WP_CLI' ) ) {
            echo 'SUCCESS';
        }
    }

    public function getItemsToDeploy( $batch_size = 1 ) {
        $lines = array();

        $f = fopen( $this->export_file_list, 'r' );

        if ( ! $f ) {
            $err = 'Failed to open export file list';
            WsLog::l( $err );
            throw new Exception( $err );
        }

        for ( $i = 0; $i < $batch_size; $i++ ) {
            $item_to_deploy = fgets( $f );

            if ( ! is_string( $item_to_deploy ) ) {
                $err = 'Failed getting item to deploy';
                WsLog::l( $err );
                throw new Exception( $err );
            }

            $lines[] = rtrim( $item_to_deploy );
        }

        fclose( $f );

        // TODO: optimize this for just one read, one write within func
        $contents = file( $this->export_file_list, FILE_IGNORE_NEW_LINES );

        if ( ! is_array( $contents ) ) {
            return false;
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

    public function getRemainingItemsCount() {
        if ( is_file( $this->export_file_list ) ) {
            $contents = file( $this->export_file_list, FILE_IGNORE_NEW_LINES );

            if ( ! is_array( $contents ) ) {
                return false;
            }

            return count( $contents );
        }

        return 0;
    }

    // TODO: rename to signalSuccessfulAction or such
    // as is used in deployment tests/not just finalizing deploys
    public function finalizeDeployment() {
        if ( ! defined( 'WP_CLI' ) ) {
            echo 'SUCCESS'; }
    }

    public function uploadsCompleted() {
        $this->files_remaining = $this->getRemainingItemsCount();

        if ( $this->files_remaining <= 0 ) {
            return true;
        } else {
            echo $this->files_remaining;
        }
    }

    public function handleException( $e ) {
        WsLog::l( 'Deployment: error encountered' );
        WsLog::l( $e );

        throw new Exception( $e );
    }

    public function checkForValidResponses( $code, $good_codes ) {
        if ( ! in_array( $code, $good_codes ) ) {
            WsLog::l(
                'BAD RESPONSE STATUS FROM API (' . $code . ')'
            );

            http_response_code( $code );

            throw new Exception(
                'BAD RESPONSE STATUS FROM API (' . $code . ')'
            );
        }
    }

    public function openPreviousHashesFile() {
        $this->file_paths_and_hashes = array();

        if ( is_file( $this->previous_hashes_path ) ) {
            $file = fopen( $this->previous_hashes_path, 'r' );

            if ( ! $file ) {
                return false;
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
        $target_path,
        $local_file_contents
        ) {
        $this->file_paths_and_hashes[ $target_path ] =
            crc32( $local_file_contents );
    }

    public function writeFilePathAndHashesToFile() {
        $fp = fopen( $this->previous_hashes_path, 'w' );

        if ( ! $fp ) {
            $err = "Couldn't open previous hashes file for writing";
            WsLog::l( $err );
            throw new Exception( $err );
        }

        foreach ( $this->file_paths_and_hashes as $key => $value ) {
            fwrite( $fp, $key . ',' . $value . PHP_EOL );
        }

        fclose( $fp );
    }

    public function logAction( $action ) {
        if ( ! isset( $this->settings['debug_mode'] ) ) {
            return;
        }

        WsLog::l( $action );
    }
}

