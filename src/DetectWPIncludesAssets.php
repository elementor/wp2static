<?php

namespace WP2Static;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Exception;

class DetectWPIncludesAssets {

    public static function detect() {
        $files = array();

        $includes_path = SiteInfo::getPath( 'includes' );
        $includes_url = SiteInfo::getUrl( 'includes' );
        $home_url = SiteInfo::getUrl( 'home' );

        if (
             ! is_string( $includes_path ) ||
             ! is_string( $includes_url ) ||
             ! is_string( $home_url )
            ) {
            $err = 'WP URLs not defined ';
            WsLog::l( $err );
            throw new Exception( $err );
        }

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
