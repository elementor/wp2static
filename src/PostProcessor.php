<?php
/*
    PostProcessor

    Processes each file in StaticSite, saving to ProcessedSite
*/

namespace WP2Static;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class PostProcessor {

    /**
     * PostProcessor constructor
     */
    public function __construct() {

    }

    /**
     * Process StaticSite
     *
     * Iterates on each file, not directory
     *
     * @param string $static_site_path Static site path
     * @throws WP2StaticException
     */
    public function processStaticSite(
        string $static_site_path
    ) : void {
        WsLog::l(
            'Processing crawled site.'
        );

        if ( ! is_dir( $static_site_path ) ) {
            WsLog::l(
                'No static site directory to process.'
            );

            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $static_site_path,
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        foreach ( $iterator as $filename => $file_object ) {
            $save_path = str_replace( $static_site_path, '', $filename );

            // copy file to ProcessedSite dir, then process it
            // this allows external processors to have their way with it
            ProcessedSite::add( $filename, $save_path );

            $file_processor = new FileProcessor();

            $file_processor->processFile( ProcessedSite::getPath() . $save_path );
        }

        WsLog::l( 'Finished processing crawled site.' );

        do_action( 'wp2static_post_process_complete', ProcessedSite::getPath() );
    }
}

