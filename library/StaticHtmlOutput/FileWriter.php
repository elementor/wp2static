<?php

class FileWriter {
    public function __construct( $url, $content, $file_type, $content_type ) {
        $this->url = $url;
        $this->content = $content;
        $this->file_type = $file_type;
        $this->content_type = $content_type;
    }

    public function saveFile( $archive_dir ) {
        $url_info = parse_url( $this->url );
        $path_info = array();

        if ( ! isset( $url_info['path'] ) ) {
            return false;
        }

        // set what the new path will be based on the given url
        if ( $url_info['path'] != '/' ) {
            $path_info = pathinfo( $url_info['path'] );
        } else {
            $path_info = pathinfo( 'index.html' );
        }

        $target_settings = array(
            'general',
            'wpenv',
        );

        if ( defined( 'WP_CLI' ) ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/DBSettings.php';

            $this->settings =
                WPSHO_DBSettings::get( $target_settings );
        } else {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/PostSettings.php';

            $this->settings =
                WPSHO_PostSettings::get( $target_settings );
        }

        $directory_in_archive =
            isset( $path_info['dirname'] ) ? $path_info['dirname'] : '';

        if ( ! empty( $this->settings['wp_site_subdir'] ) ) {
            $directory_in_archive = str_replace(
                $this->settings['wp_site_subdir'],
                '',
                $directory_in_archive
            );
        }

        $file_dir = $archive_dir . ltrim( $directory_in_archive, '/' );

        // set filename to index if no extension && base and filename are  same
        if ( empty( $path_info['extension'] ) &&
            $path_info['basename'] === $path_info['filename'] ) {
            $file_dir .= '/' . $path_info['basename'];
            $path_info['filename'] = 'index';
        }

        if ( ! file_exists( $file_dir ) ) {
            wp_mkdir_p( $file_dir );
        }

        $file_extension = '';

        if ( isset( $path_info['extension'] ) ) {
            $file_extension = $path_info['extension'];
        } elseif ( $this->file_type == 'html' ) {
            $file_extension = 'html';
        } elseif ( $this->file_type == 'xml' ) {
            $file_extension = 'html';
        }

        $filename = '';

        // set path for homepage to index.html, else build filename
        if ( $url_info['path'] == '/' ) {
            // TODO: isolate and fix the cause requiring this trim:
            $filename = rtrim( $file_dir, '.' ) . 'index.html';
        } else {
            // TODO: deal with this hard to read, but functioning code
            if ( ! empty( $this->settings['wp_site_subdir'] ) ) {
                $file_dir = str_replace(
                    '/' . $this->settings['wp_site_subdir'],
                    '/',
                    $file_dir
                );
            }

            $filename =
                $file_dir . '/' . $path_info['filename'] .
                '.' . $file_extension;
        }

        $file_contents = $this->content;

        if ( $file_contents ) {
            file_put_contents( $filename, $file_contents );

            chmod( $filename, 0664 );
        } else {
            require_once dirname( __FILE__ ) . '/../StaticHtmlOutput/WsLog.php';
            WsLog::l( 'SAVING URL: FILE IS EMPTY ' . $this->url );
        }
    }
}

