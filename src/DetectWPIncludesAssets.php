<?php

namespace WP2Static;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class DetectWPIncludesAssets {

    /**
     * Detect assets within wp-includes path
     *
     * @return string[] list of URLs
     * @throw WP2StaticException
     */
    public static function detect() : array {
        $files = [];

        $includes_path = SiteInfo::getPath( 'includes' );
        $includes_url = SiteInfo::getUrl( 'includes' );
        $home_url = SiteInfo::getUrl( 'home' );

        if ( is_dir( $includes_path ) ) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $includes_path,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            );

            foreach ( $iterator as $filename => $file_object ) {
                $path_crawlable =
                    FilesHelper::filePathLooksCrawlable( $filename );

                // Standardise all paths to use / (Windows support)
                $filename = str_replace( '\\', '/', $filename );

                $detected_filename =
                    str_replace(
                        $includes_path,
                        $includes_url,
                        $filename
                    );

                $detected_filename =
                    str_replace(
                        $home_url,
                        '',
                        $detected_filename
                    );

                if ( ! is_string( $detected_filename ) ) {
                    continue;
                }

                if ( $path_crawlable ) {
                    array_push(
                        $files,
                        '/' . $detected_filename
                    );
                }
            }
        }

        return $files;
    }
}
