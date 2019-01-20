<?php

class StaticHtmlOutput_FTP extends StaticHtmlOutput_SitePublisher {

    public function __construct() {
        $this->loadSettings( 'ftp' );

        $this->previous_hashes_path =
            $this->settings['wp_uploads_path'] .
                '/WP2STATIC-FTP-PREVIOUS-HASHES.txt';

        if ( defined( 'WP_CLI' ) ) {
            return; }

        switch ( $_POST['ajax_action'] ) {
            case 'ftp_prepare_export':
                $this->bootstrap();
                $this->loadArchive();
                $this->prepareDeploy();
                break;
            case 'ftp_transfer_files':
                $this->bootstrap();
                $this->loadArchive();
                $this->upload_files();
                break;
            case 'test_ftp':
                $this->test_ftp();
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

        require_once __DIR__ . '/../library/FTP/FtpClient.php';
        require_once __DIR__ . '/../library/FTP/FtpException.php';
        require_once __DIR__ . '/../library/FTP/FtpWrapper.php';

        $this->ftp = new \FtpClient\FtpClient();

        $port = isset( $this->settings['ftpPort'] ) ?
            $this->settings['ftpPort'] : 21;

        $use_ftps = isset( $this->settings['ftpTLS'] );

        $this->ftp->connect(
            $this->settings['ftpServer'],
            $use_ftps,
            $port
        );

        $this->ftp->login(
            $this->settings['ftpUsername'],
            $this->settings['ftpPassword']
        );

        if ( isset( $this->settings['activeFTP'] ) ) {
            $this->ftp->pasv( false );
        } else {
            $this->ftp->pasv( true );
        }

        foreach ( $lines as $line ) {
            list($this->local_file, $this->target_path) = explode( ',', $line );

            $this->local_file = $this->archive->path . $this->local_file;

            if ( ! is_file( $this->local_file ) ) {
                continue; }

            if ( isset( $this->settings['ftpRemotePath'] ) ) {
                $this->target_path =
                    $this->settings['ftpRemotePath'] . '/' . $this->target_path;
            }

            $this->local_file_contents = file_get_contents( $this->local_file );

            $this->hash_key =
                $this->target_path . basename( $this->local_file );

            if ( isset( $this->file_paths_and_hashes[ $this->hash_key ] ) ) {
                $prev = $this->file_paths_and_hashes[ $this->hash_key ];
                $current = crc32( $this->local_file_contents );

                if ( $prev != $current ) {
                    $this->putFileViaFTP();
                }
            } else {
                $this->putFileViaFTP();
            }

            $this->recordFilePathAndHashInMemory(
                $this->hash_key,
                $this->local_file_contents
            );

            $this->updateProgress();
        }

        unset( $this->ftp );

        $this->writeFilePathAndHashesToFile();

        $this->pauseBetweenAPICalls();

        if ( $this->uploadsCompleted() ) {
            $this->finalizeDeployment();
        }
    }

    public function test_ftp() {
        require_once __DIR__ . '/../library/FTP/FtpClient.php';
        require_once __DIR__ . '/../library/FTP/FtpException.php';
        require_once __DIR__ . '/../library/FTP/FtpWrapper.php';

        $this->ftp = new \FtpClient\FtpClient();

        $port = isset( $this->settings['ftpPort'] ) ?
            $this->settings['ftpPort'] : 21;

        $use_ftps = isset( $this->settings['ftpTLS'] );

        $this->ftp->connect(
            $this->settings['ftpServer'],
            $use_ftps,
            $port
        );

        try {
            $this->ftp->login(
                $this->settings['ftpUsername'],
                $this->settings['ftpPassword']
            );

            if ( ! defined( 'WP_CLI' ) ) {
                echo 'SUCCESS'; }

            unset( $this->ftp );
            return;
        } catch ( Exception $e ) {
            unset( $this->ftp );
            $this->handleException( $e );
        }
    }

    public function putFileViaFTP() {
        if ( ! $this->ftp->isdir( $this->target_path ) ) {
            $mkdir_result = $this->ftp->mkdir( $this->target_path, true );
        }

        $this->ftp->chdir( $this->target_path );
        $this->ftp->putFromPath( $this->local_file );
        $this->ftp->chdir( '/' );
    }
}

$ftp = new StaticHtmlOutput_FTP();
