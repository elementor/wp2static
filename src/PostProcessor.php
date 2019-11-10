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
     *
     */
    public function __construct() {

    }

    /**
     * Process StaticSite
     *
     * Iterates on each file, not directory
     *
     * @param StaticSite $static_site Static site
     * @param ProcessedSite $processed_site Processed site
     * @throws WP2StaticException
     */
    public function processStaticSite(
        StaticSite $static_site,
        ProcessedSite $processed_site
    ) : void {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $static_site->path,
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        foreach ( $iterator as $filename => $file_object ) {
            $save_path = str_replace( $static_site->path, '', $filename );

            // add file to ProcessedSite, then process it
            // this allows external processors to have their way with it
            $processed_site->add( $filename, $save_path ); 

            $file_processor = new FileProcessor();

            $file_processor->processFile( $processed_site->path . $save_path );
        }

        error_log('TODO: FolderProcessor for renaming, etc');
        error_log('finished processing StaticSite');

        do_action( 'wp2static_post_process_complete', $processed_site->path );
    }
}

