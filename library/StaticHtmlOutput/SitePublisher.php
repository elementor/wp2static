<?php

class StaticHtmlOutput_SitePublisher {
    public function loadSettings( $deploy_method ) {
        $target_settings = array(
            'general',
            'wpenv',
            'advanced',
        );

        $target_settings[] = $deploy_method;

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            require_once dirname( __FILE__ ) .
                '/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $target_settings );
        } else {
            require_once dirname( __FILE__ ) .
                '/DBSettings.php';

            $this->settings = WPSHO_DBSettings::get( $target_settings );
        }
    }

    public function loadArchive() {
        require_once dirname( __FILE__ ) .
            '/Archive.php';
        $this->archive = new Archive();
        $this->archive->setToCurrentArchive();
    }

    public function bootstrap() {
        $this->export_file_list =
            $this->settings['wp_uploads_path'] .
                '/WP2STATIC-FILES-TO-DEPLOY.txt';
        $this->archive_dir = file_get_contents(
            $this->settings['wp_uploads_path'] .
                '/WP2STATIC-CURRENT-ARCHIVE.txt'
        );
    }

    public function pauseBetweenAPICalls() {
        if ( isset( $this->settings['delayBetweenAPICalls'] ) &&
            $this->settings['delayBetweenAPICalls'] > 0 ) {
            sleep( $this->settings['delayBetweenAPICalls'] );
        }
    }

    public function updateProgress() {
        $this->batch_index++;

        $completed_urls =
            $this->total_urls_to_crawl -
            $this->files_remaining +
            $this->batch_index;

        require_once dirname( __FILE__ ) .
            '/ProgressLog.php';
        ProgressLog::l( $completed_urls, $this->total_urls_to_crawl );
    }

    public function initiateProgressIndicator() {
        $this->deploy_count_path = $this->settings['wp_uploads_path'] .
                '/WP-STATIC-TOTAL-FILES-TO-DEPLOY.txt';
        $this->total_urls_to_crawl =
            file_get_contents( $this->deploy_count_path );

        $this->batch_index = 0;
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

        foreach ( scandir( $dir ) as $item ) {
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
                    $local_file_path . ',' . $remote_deployment_path . "\n",
                    FILE_APPEND | LOCK_EX
                );

                chmod( $this->export_file_list, 0664 );
            }
        }
    }

    public function prepareDeploy( $basename_in_target = false ) {
        $this->clearFileList();

        $this->createDeploymentList(
            $this->settings['wp_uploads_path'] . '/' .
                $this->archive->name,
            $basename_in_target
        );

        // TODO: detect and use `cat | wc -l` if available
        $linecount = 0;
        $handle = fopen( $this->export_file_list, 'r' );

        while ( ! feof( $handle ) ) {
            $line = fgets( $handle );
            $linecount++;
        }

        fclose( $handle );

        $deploy_count_path = $this->settings['wp_uploads_path'] .
                '/WP-STATIC-TOTAL-FILES-TO-DEPLOY.txt';

        file_put_contents(
            $deploy_count_path,
            $linecount,
            LOCK_EX
        );

        chmod( $deploy_count_path, 0664 );

        if ( ! defined( 'WP_CLI' ) ) {
            echo 'SUCCESS';
        }
    }

    public function getItemsToDeploy( $batch_size = 1 ) {
        $lines = array();

        $f = fopen( $this->export_file_list, 'r' );

        for ( $i = 0; $i < $batch_size; $i++ ) {
            $lines[] = fgets( $f );
        }

        fclose( $f );

        // TODO: optimize this for just one read, one write within func
        $contents = file( $this->export_file_list, FILE_IGNORE_NEW_LINES );

        for ( $i = 0; $i < $batch_size; $i++ ) {
            // rewrite file minus the lines we took
            array_shift( $contents );
        }

        file_put_contents(
            $this->export_file_list,
            implode( "\r\n", $contents )
        );

        chmod( $this->export_file_list, 0664 );

        return $lines;
    }

    public function getRemainingItemsCount() {
        $contents = file( $this->export_file_list, FILE_IGNORE_NEW_LINES );

        // return the amount left if another item is taken
        // return count($contents) - 1;
        return count( $contents );
    }

    public function finalizeDeployment() {
        if ( ! defined( 'WP_CLI' ) ) {
            echo 'SUCCESS';
        }
    }

    public function uploadsCompleted() {
        $this->files_remaining = $this->getRemainingItemsCount();

        if ( $this->files_remaining <= 0 ) {
            return true;
        } else {
            if ( defined( 'WP_CLI' ) ) {
                $this->upload_files();
            } else {
                echo $this->files_remaining;
            }
        }
    }

    public function handleException( $e ) {
        require_once dirname( __FILE__ ) .
            '/WsLog.php';
        WsLog::l( 'Deployment: error encountered' );
        WsLog::l( $e );
        throw new Exception( $e );
    }

    public function checkForValidResponses( $code, $good_codes ) {
        if ( ! in_array( $code, $good_codes ) ) {
            require_once dirname( __FILE__ ) .
                '/WsLog.php';
            WsLog::l(
                'BAD RESPONSE STATUS FROM API (' . $code . ')'
            );

            throw new Exception(
                'BAD RESPONSE STATUS FROM API (' . $code . ')'
            );
        }
    }
}

