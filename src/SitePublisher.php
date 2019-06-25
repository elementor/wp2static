<?php

namespace WP2Static;

class SitePublisher {

    private $settings;
    public $files_remaining;
    public $file_paths_and_hashes;
    public $previous_hashes_path;

    public function loadSettings() : void {
        $plugin = Controller::getInstance();
        $this->settings = $plugin->options->getSettings( true );
    }

    public function pauseBetweenAPICalls() : void {
        if ( isset( $this->settings['delayBetweenAPICalls'] ) &&
            $this->settings['delayBetweenAPICalls'] > 0 ) {
            sleep( $this->settings['delayBetweenAPICalls'] );
        }
    }

    public function clearFileList() : void {
        DeployQueue::truncate();
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

    /**
     * Recursively add files to deployment list
     *
     * @throws WP2StaticException
     */
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

                DeployQueue::addPath(
                    $local_file_path,
                    $remote_deployment_path
                );
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
        $deployment_items = DeployQueue::getDeployableURLs( $batch_size );

        return $deployment_items;
    }

    // TODO: extra call getting all URL data, optimize
    public function getRemainingItemsCount() : int {
        $urls_to_deploy = DeployQueue::getDeployableURLs();

        return count( $urls_to_deploy );
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
}

