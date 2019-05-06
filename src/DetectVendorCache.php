<?php

namespace WP2Static;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DetectVendorCache {
    /*
        Autoptimize and other vendors use a cache dir one level above the
        uploads URL

        ie, domain.com/cache/ or domain.com/subdir/cache/

        so, we grab all the files from the its actual cache dir

        then strip the site path and any subdir path (no extra logic needed?)
    */
    public static function detect(
        $cache_dir,
        $path_to_trim,
        $prefix
        ) {

        $files = array();

        $directory = $cache_dir;

        if ( is_dir( $directory ) ) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $directory,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            );

            foreach ( $iterator as $filename => $file_object ) {
                $path_crawlable =
                    FilesHelper::filePathLooksCrawlable( $filename );

                if ( $path_crawlable ) {
                    array_push(
                        $files,
                        $prefix .
                        home_url( str_replace( $path_to_trim, '', $filename ) )
                    );
                }
            }
        }

        return $files;
    }
}
