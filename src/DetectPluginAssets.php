<?php

namespace WP2Static;

use Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class DetectPluginAssets {

    public static function detect() {
        $files = array();

        $plugins_path = SiteInfo::getPath( 'plugins' );
        $plugins_url = SiteInfo::getUrl( 'plugins' );

        if ( ! is_string( $plugins_path ) || ! is_string( $plugins_url ) ) {
            $err = 'Plugins path not defined ';
            WsLog::l( $err );
            throw new Exception( $err );
        }

        if ( is_dir( $plugins_path ) ) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $plugins_path,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            );

            foreach ( $iterator as $filename => $file_object ) {
                $path_crawlable =
                    FilesHelper::filePathLooksCrawlable( $filename );

                $detected_filename =
                    str_replace(
                        $plugins_path,
                        $plugins_url,
                        $filename
                    );

                $detected_filename =
                    str_replace(
                        get_home_url(),
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
