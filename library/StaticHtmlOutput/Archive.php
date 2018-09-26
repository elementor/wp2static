<?php

class Archive {

    public function __construct() {
        $this->path = '';
        $this->crawl_list = '';
        $this->export_log = '';
        $this->uploads_path = isset( $_POST['wp_uploads_path'] ) ? $_POST['wp_uploads_path'] : '';
        $this->working_directory = isset( $_POST['workingDirectory'] ) ? $_POST['workingDirectory'] : $this->uploads_path;
    }

    public function addFiles() {
        // TODO: use within SiteCrawler
    }

    public function setToCurrentArchive() {
        // TODO: someting like setScopeToCurrentArchive?
        // refreshes the instance properties in case called out of context
        $handle = fopen( $this->uploads_path . '/WP-STATIC-CURRENT-ARCHIVE', 'r' );
        $this->path = stream_get_line( $handle, 0 );
    }

    public function currentArchiveExists() {
        return is_file( $this->uploads_path . '/WP-STATIC-CURRENT-ARCHIVE' );
    }

    public function create() {
        $this->name = $this->working_directory . '/wp-static-html-output-' . time();

        $this->path = $this->name . '/';

        if ( wp_mkdir_p( $this->path ) ) {
            // saving the current archive name to file to persist across requests / functions
            file_put_contents( $this->uploads_path . '/WP-STATIC-CURRENT-ARCHIVE', $this->path );
        } else {
            error_log( "Couldn't create the archive directory at " . $this->path );
        }
    }
}
