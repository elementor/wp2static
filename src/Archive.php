<?php

namespace WP2Static;

class Archive extends Base {

    public function __construct() {
        $this->loadSettings(
            array( 'wpenv' )
        );

        $this->path = '';
        $this->name = '';
        $this->crawl_list = '';
        $this->export_log = '';
    }

    public function setToCurrentArchive() {
        $handle = fopen(
            $this->settings['wp_uploads_path'] .
                '/wp2static-working-files/CURRENT-ARCHIVE.txt',
            'r'
        );

        $this->path = stream_get_line( $handle, 0 );
        $this->name = basename( $this->path );
    }

    public function currentArchiveExists() {
        return is_file(
            $this->settings['wp_uploads_path'] .
            '/wp2static-working-files/CURRENT-ARCHIVE.txt'
        );
    }

    public function create() {
        $this->name = $this->settings['wp_uploads_path'] .
            '/wp2static-exported-site';

        $this->path = $this->name . '/';
        $this->name = basename( $this->path );

        if ( wp_mkdir_p( $this->path ) ) {
            $result = file_put_contents(
                $this->settings['wp_uploads_path'] .
                    '/wp2static-working-files/CURRENT-ARCHIVE.txt',
                $this->path
            );

            if ( ! $result ) {
                require_once dirname( __FILE__ ) .
                    '/../WP2Static/WsLog.php';
                WsLog::l( 'USER WORKING DIRECTORY NOT WRITABLE' );
            }

            chmod(
                $this->settings['wp_uploads_path'] .
                    '/wp2static-working-files/CURRENT-ARCHIVE.txt',
                0664
            );
        } else {
            error_log( "Couldn't create archive directory at " . $this->path );
        }
    }
}

