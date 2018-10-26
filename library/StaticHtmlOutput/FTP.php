<?php

class StaticHtmlOutput_FTP {
    public function __construct() {
        $target_settings = array(
            'general',
            'wpenv',
            'ftp',
            'advanced',
        );

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $target_settings );
        } else {
            error_log( 'TODO: load settings from DB' );
        }

        $this->exportFileList = $this->settings['working_directory'] .
                '/WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT';

        switch ( $_POST['ajax_action'] ) {
            case 'test_ftp':
                $this->test_ftp();
                break;

            case 'github_upload_blobs':
                $this->upload_blobs();
                break;
            case 'github_finalise_export':
                $this->commit_new_tree();
                break;
            case 'test_blob_create':
                $this->test_blob_create();
                break;
        }
    }

    public function clear_file_list() {
        // TODO: avoid suppressing
        $f = fopen( $this->exportFileList, 'r+' );
        if ( $f !== false ) {
            ftruncate( $f, 0 );
            fclose( $f );
        }
    }

    public function test_connection() {
        require_once __DIR__ . '/../FTP/FtpClient.php';
        require_once __DIR__ . '/../FTP/FtpException.php';
        require_once __DIR__ . '/../FTP/FtpWrapper.php';

        $ftp = new \FtpClient\FtpClient();

        try {
            $ftp->connect( $this->settings['ftpServer'] );
            $ftp->login( $this->settings['ftpUsername'], $this->settings['ftpPassword'] );
        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
            WsLog::l( 'FTP EXPORT: error encountered' );
            WsLog::l( $e );
            throw new Exception( $e );
        }

        if ( ! $ftp->isdir( $this->settings['ftpRemotePath'] ) ) {
            $ftp->mkdir( $this->settings['ftpRemotePath'], true );
        }

        unset( $ftp );
    }


    // TODO: move into a parent class as identical to bunny and probably others
    public function create_ftp_deployment_list(
        $dir,
        $archiveName,
        $remotePath ) {

        $files = scandir( $dir );

        foreach ( $files as $item ) {
            if ( $item != '.' && $item != '..' && $item != '.git' ) {
                if ( is_dir( $dir . '/' . $item ) ) {
                    $this->create_ftp_deployment_list(
                        $dir . '/' . $item,
                        $archiveName,
                        $remotePath
                    );
                } elseif ( is_file( $dir . '/' . $item ) ) {
                    $subdir = str_replace(
                        '/wp-admin/admin-ajax.php',
                        '',
                        $_SERVER['REQUEST_URI']
                    );
                    $subdir = ltrim( $subdir, '/' );
                    $clean_dir =
                        str_replace( $archiveName . '/', '', $dir . '/' );
                    $clean_dir = str_replace( $subdir, '', $clean_dir );
                    $targetPath = $remotePath . $clean_dir;
                    $targetPath = ltrim( $targetPath, '/' );
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


    // TODO: move into a parent class as identical to bunny and probably others
    public function prepare_deployment() {
        $this->test_connection();

        $this->clear_file_list();

        $this->create_ftp_deployment_list(
            $this->_archiveName,
            $this->_archiveName,
            $this->settings['ftpRemotePath']
        );

        echo 'SUCCESS';
    }

    public function get_item_to_export() {
        $f = fopen( $this->exportFileList, 'r' );
        $line = fgets( $f );
        fclose( $f );

        $contents = file( $this->exportFileList, FILE_IGNORE_NEW_LINES );
        array_shift( $contents );
        file_put_contents(
            $this->exportFileList,
            implode( "\r\n", $contents )
        );

        return $line;
    }

    public function get_remaining_items_count() {
        $contents = file( $this->exportFileList, FILE_IGNORE_NEW_LINES );

        // return the amount left if another item is taken
        // return count($contents) - 1;
        return count( $contents );
    }


    public function transfer_files( $viaCLI = false ) {
        require_once __DIR__ . '/../FTP/FtpClient.php';
        require_once __DIR__ . '/../FTP/FtpException.php';
        require_once __DIR__ . '/../FTP/FtpWrapper.php';

        if ( $this->get_remaining_items_count() < 0 ) {
            echo 'ERROR';
            die();
        }

        $line = $this->get_item_to_export();
        list($fileToTransfer, $targetPath) = explode( ',', $line );
        $targetPath = rtrim( $targetPath );

        // vendor specific from here
        $ftp = new \FtpClient\FtpClient();
        $ftp->connect( $this->settings['ftpServer'] );

        try {

            $ftp->login( $this->settings['ftpUsername'], $this->settings['ftpPassword'] );
        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
            WsLog::l( 'FTP EXPORT: unable to login' );
            WsLog::l( $e );
            throw new Exception( $e );
        }

        if ( $this->settings['activeFTP'] ) {
            $ftp->pasv( false );
        } else {
            $ftp->pasv( true );
        }

        if ( ! $ftp->isdir( $targetPath ) ) {
            $mkdir_result = $ftp->mkdir( $targetPath, true );
        }

        $ftp->chdir( $targetPath );
        $ftp->putFromPath( $fileToTransfer );

        unset( $ftp );
        // end vendor specific
        $filesRemaining = $this->get_remaining_items_count();

        if ( $this->get_remaining_items_count() > 0 ) {

            if ( $viaCLI ) {
                $this->transfer_files( true );
            }

            echo $this->get_remaining_items_count();
        } else {
            echo 'SUCCESS';
        }
    }

    public function test_ftp() {
        require_once __DIR__ . '/../FTP/FtpClient.php';
        require_once __DIR__ . '/../FTP/FtpException.php';
        require_once __DIR__ . '/../FTP/FtpWrapper.php';

        $ftp = new \FtpClient\FtpClient();
        $ftp->connect( $this->settings['ftpServer'] );

        try {

            $ftp->login( $this->settings['ftpUsername'], $this->settings['ftpPassword'] );

            echo 'SUCCESS';
            unset( $ftp );
            return;
        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
            WsLog::l( 'FTP EXPORT: unable to login' );
            WsLog::l( $e );
            throw new Exception( $e );
            unset( $ftp );
        }
    }
}

$ftp_deployer = new StaticHtmlOutput_FTP();
