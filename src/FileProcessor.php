<?php
/*
    FileProcessor

    Takes a crawled file and processes it
*/

namespace WP2Static;

class FileProcessor {

    /**
     * FileProcessor constructor
     */
    public function __construct() {

    }

    /**
     * Process StaticSite
     *
     * Iterates on each file, not directory
     *
     * @param string $filename File in StaticSite
     */
    public function processFile( string $filename ) : void {
        if ( $filename === ProcessedSite::getPath() . '/robots.txt' ) {
            do_action( 'wp2static_process_robots_txt', $filename );
            return;
        }
        switch ( pathinfo( $filename, PATHINFO_EXTENSION ) ) {
            case 'html':
                do_action( 'wp2static_process_html', $filename );
                do_action( 'wp2static_process_html_complete', $filename );
                break;
            case 'css':
                do_action( 'wp2static_process_css', $filename );
                break;
            case 'js':
                do_action( 'wp2static_process_js', $filename );
                break;
            case 'xml':
                do_action( 'wp2static_process_xml', $filename );
                break;
        }
    }
}

