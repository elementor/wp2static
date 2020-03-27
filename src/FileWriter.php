<?php

namespace WP2Static;

class FileWriter {

    /**
     * @var string | bool
     */
    public $backup_locale_ctype;
    /**
     * @var string
     */
    public $content;
    /**
     * @var string
     */
    public $content_type;
    /**
     * @var string
     */
    public $file_type;
    /**
     * @var string
     */
    public $url;

    public function __construct(
        string $url,
        string $content,
        string $file_type,
        string $content_type
    ) {
        $this->url = $url;
        $this->content = $content;
        $this->file_type = $file_type;
        $this->content_type = $content_type;
        $this->backup_locale_ctype = setlocale( LC_CTYPE, null );
    }

    public function saveFile( string $archive_dir ) : void {
        // Fix pathinfo failing with non-latin characters
        setlocale( LC_CTYPE, 'en_US.UTF-8' );

        $url_info = parse_url( $this->url );

        if ( ! is_array( $url_info ) ) {
            return;
        }

        $path_info = [];

        if ( ! array_key_exists( 'path', $url_info ) ) {
            return;
        }

        // set what the new path will be based on the given url
        if ( $url_info['path'] != '/' ) {
            $path_info = pathinfo( $url_info['path'] );
        } else {
            $path_info = pathinfo( 'index.html' );
        }

        $directory_in_archive =
            $path_info['dirname'] ? $path_info['dirname'] : '';

        $file_dir = $archive_dir . ltrim( $directory_in_archive, '/' );

        // set filename to index if no extension && base and filename are  same
        if ( empty( $path_info['extension'] ) &&
            $path_info['basename'] === $path_info['filename'] ) {
            $file_dir .= '/' . $path_info['basename'];
            $path_info['filename'] = 'index';
        }

        if ( ! is_dir( $file_dir ) ) {
            if ( ! wp_mkdir_p( $file_dir ) ) {
                WsLog::l( 'Couldn\t make directory: ' . $file_dir );
            }
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
            $filename =
                $file_dir . '/' . $path_info['filename'] .
                '.' . $file_extension;
        }

        $file_contents = $this->content;

        if ( $file_contents ) {
            file_put_contents( $filename, $file_contents );
        } else {
            WsLog::l( 'NOT SAVING EMTPY FILE ' . $this->url );
        }

        // Reset locale to ensure normal WordPress operations
        setlocale( LC_CTYPE, (string) $this->backup_locale_ctype );
    }
}

