<?php

namespace WP2Static;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class DetectWPIncludesAssets {

    public static function detect() {
        $files = array();

        $directory = SiteInfo::getPath( 'includes' );

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

                $detected_filename =
                    str_replace(
                        SiteInfo::getPath( 'includes' ),
                        SiteInfo::getUrl( 'includes' ),
                        $filename
                    );

                $detected_filename =
                    str_replace(
                        SiteInfo::getUrl( 'home' ),
                        '',
                        $detected_filename
                    );

                if ( $path_crawlable ) {
                    array_push(
                        $files,
                        $detected_filename
                    );
                }
            }
        }

        return $files;
    }
}
