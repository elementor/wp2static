<?php

class FileWriter {
    public function __construct( $url, $content, $file_type, $content_type ) {
        $this->url = $url;
        $this->content = $content;
        $this->file_type = $file_type;
        $this->content_type = $content_type;
    }

    public function saveFile( $archive_dir ) {
        $urlInfo = parse_url( $this->url );
        $pathInfo = array();

        if ( ! isset( $urlInfo['path'] ) ) {
            return false;
        }

        // set what the new path will be based on the given url
        if ( $urlInfo['path'] != '/' ) {
            $pathInfo = pathinfo( $urlInfo['path'] );
        } else {
            $pathInfo = pathinfo( 'index.html' );
        }

        $directory_in_archive =
            isset( $pathInfo['dirname'] ) ? $pathInfo['dirname'] : '';

        if ( isset( $_POST['subdirectory'] ) ) {
            $directory_in_archive = str_replace(
                $_POST['subdirectory'],
                '',
                $directory_in_archive
            );
        }

        $fileDir = $archive_dir . ltrim( $directory_in_archive, '/' );

        // set filename to index if no extension && base and filename are  same
        if ( empty( $pathInfo['extension'] ) &&
            $pathInfo['basename'] === $pathInfo['filename'] ) {
            $fileDir .= '/' . $pathInfo['basename'];
            $pathInfo['filename'] = 'index';
        }

        if ( ! file_exists( $fileDir ) ) {
            wp_mkdir_p( $fileDir );
        }

        $fileExtension = '';

        if ( isset( $pathInfo['extension'] ) ) {
            $fileExtension = $pathInfo['extension'];
        } elseif ( $this->file_type == 'html' ) {
            $fileExtension = 'html';
        } elseif ( $this->file_type == 'xml' ) {
            $fileExtension = 'html';
        }

        $fileName = '';

        // set path for homepage to index.html, else build filename
        if ( $urlInfo['path'] == '/' ) {
            // TODO: isolate and fix the cause requiring this trim:
            $fileName = rtrim( $fileDir, '.' ) . 'index.html';
        } else {
            // TODO: deal with this hard to read, but functioning code
            if ( isset( $_POST['subdirectory'] ) ) {
                $fileDir = str_replace(
                    '/' . $_POST['subdirectory'],
                    '/',
                    $fileDir
                );
            }

            $fileName =
                $fileDir . '/' . $pathInfo['filename'] . '.' . $fileExtension;
        }

        $fileContents = $this->content;

        if ( $fileContents ) {
            file_put_contents( $fileName, $fileContents );

            chmod( $fileName, 0664 );
        } else {
            require_once dirname( __FILE__ ) . '/../StaticHtmlOutput/WsLog.php';
            WsLog::l( 'SAVING URL: FILE IS EMPTY ' . $this->url );
        }
    }
}

