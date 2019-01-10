<?php

class StaticHtmlOutput_FTP extends StaticHtmlOutput_SitePublisher {

    public function __construct() {
        $target_settings = array(
            'general',
            'wpenv',
            'ftp',
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

        $this->export_file_list = $this->settings['wp_uploads_path'] .
                '/WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT.txt';

        $this->r_path = '';

        if ( isset( $this->settings['ftpRemotePath'] ) ) {
            $this->r_path = $this->settings['ftpRemotePath'];
        }

        require_once dirname( __FILE__ ) .
            '/../library/StaticHtmlOutput/Archive.php';
        $this->archive = new Archive();
        $this->archive->setToCurrentArchive();

        switch ( $_POST['ajax_action'] ) {
            case 'test_ftp':
                $this->test_ftp();
                break;
            case 'ftp_prepare_export':
                $this->prepare_export();
                break;
            case 'ftp_transfer_files':
                $this->transfer_files();
                break;
        }
    }

    public function transfer_files() {
        require_once __DIR__ . '/../library/FTP/FtpClient.php';
        require_once __DIR__ . '/../library/FTP/FtpException.php';
        require_once __DIR__ . '/../library/FTP/FtpWrapper.php';

        $filesRemaining = $this->get_remaining_items_count();

        if ( $filesRemaining < 0 ) {
            echo 'ERROR';
            die();
        }

        $batch_size = $this->settings['ftpBlobIncrement'];

        if ( $batch_size > $filesRemaining ) {
            $batch_size = $filesRemaining;
        }

        $lines = $this->get_items_to_export( $batch_size );

        $ftp = new \FtpClient\FtpClient();
        $ftp->connect( $this->settings['ftpServer'] );

        try {

            $ftp->login(
                $this->settings['ftpUsername'],
                $this->settings['ftpPassword']
            );
        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/WsLog.php';
            WsLog::l( 'FTP EXPORT: unable to login' );
            WsLog::l( $e );
            throw new Exception( $e );
        }

        if ( isset( $this->settings['activeFTP'] ) ) {
            $ftp->pasv( false );
        } else {
            $ftp->pasv( true );
        }

        foreach ( $lines as $line ) {
            list($fileToTransfer, $targetPath) = explode( ',', $line );

            $fileToTransfer = $this->archive->path . $fileToTransfer;

            $targetPath = rtrim( $targetPath );

            if ( ! $ftp->isdir( $targetPath ) ) {
                $mkdir_result = $ftp->mkdir( $targetPath, true );
            }

            $ftp->chdir( $targetPath );
            $ftp->putFromPath( $fileToTransfer );
        }

        unset( $ftp );

        if ( isset( $this->settings['ftpBlobDelay'] ) &&
            $this->settings['ftpBlobDelay'] > 0 ) {
            sleep( $this->settings['glBlobDelay'] );
        }

        $filesRemaining = $this->get_remaining_items_count();

        if ( $filesRemaining > 0 ) {

            if ( defined( 'WP_CLI' ) ) {
                $this->transfer_files();
            } else {
                echo $filesRemaining;
            }
        } else {
            if ( ! defined( 'WP_CLI' ) ) {
                echo 'SUCCESS';
            }
        }
    }

    public function test_ftp() {
        require_once __DIR__ . '/../library/FTP/FtpClient.php';
        require_once __DIR__ . '/../library/FTP/FtpException.php';
        require_once __DIR__ . '/../library/FTP/FtpWrapper.php';

        $ftp = new \FtpClient\FtpClient();
        $ftp->connect( $this->settings['ftpServer'] );

        try {

            $ftp->login(
                $this->settings['ftpUsername'],
                $this->settings['ftpPassword']
            );

            if ( ! defined( 'WP_CLI' ) ) {
                echo 'SUCCESS';
            }

            unset( $ftp );
            return;
        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/WsLog.php';
            WsLog::l( 'FTP EXPORT: unable to login' );
            WsLog::l( $e );
            throw new Exception( $e );
            unset( $ftp );
        }
    }
}

$ftp = new StaticHtmlOutput_FTP();
