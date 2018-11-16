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

        require_once dirname( __FILE__ ) .
            '/../StaticHtmlOutput/Archive.php';
        $this->archive = new Archive();
        $this->archive->setToCurrentArchive();

        switch ( $_POST['ajax_action'] ) {
            case 'test_ftp':
                $this->test_ftp();
                break;
            case 'ftp_prepare_export':
                $this->prepare_deployment();
                break;
            case 'ftp_transfer_files':
                $this->transfer_files();
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
    public function create_ftp_deployment_list( $dir ) {
        $r_path = '';

        if ( isset( $this->settings['ftpRemotePath'] ) ) {
            $r_path = $this->settings['ftpRemotePath'];
        }

        $files = scandir( $dir );

        foreach ( $files as $item ) {
            if ( $item != '.' && $item != '..' && $item != '.git' ) {
                if ( is_dir( $dir . '/' . $item ) ) {
                    $this->create_ftp_deployment_list(
                        $dir . '/' . $item,
                        $this->archive->name,
                        $r_path
                    );
                } elseif ( is_file( $dir . '/' . $item ) ) {
                    $subdir = str_replace(
                        '/wp-admin/admin-ajax.php',
                        '',
                        $_SERVER['REQUEST_URI']
                    );
                    $subdir = ltrim( $subdir, '/' );
                    $clean_dir =
                        str_replace(
                            $this->archive->name . '/',
                            '',
                            $dir . '/'
                        );
                    $clean_dir = str_replace( $subdir, '', $clean_dir );
                    $targetPath = $r_path . $clean_dir;
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


    public function prepare_deployment() {
            $this->clear_file_list();
            $this->create_ftp_deployment_list(
                $this->settings['working_directory'] . '/' .
                    $this->archive->name
            );

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


    public function transfer_files( $viaCLI = false ) {
        require_once __DIR__ . '/../FTP/FtpClient.php';
        require_once __DIR__ . '/../FTP/FtpException.php';
        require_once __DIR__ . '/../FTP/FtpWrapper.php';

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
                '/../StaticHtmlOutput/WsLog.php';
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

            if ( $viaCLI ) {
                $this->transfer_files( true );
            }

            echo $filesRemaining;
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

            $ftp->login(
                $this->settings['ftpUsername'],
                $this->settings['ftpPassword']
            );

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
