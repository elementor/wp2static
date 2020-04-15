<?php

namespace WP2Static;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class DetectThemeAssets {

    /**
     * Detect theme public URLs from filesystem
     *
     * @return string[] list of URLs
     */
    public static function detect( string $theme_type ) : array {
        $files = [];
        $template_path = '';
        $template_url = '';
        $site_path = SiteInfo::getPath( 'site' );

        if ( $theme_type === 'parent' ) {
            $template_path = SiteInfo::getPath( 'parent_theme' );
            $template_url = SiteInfo::getUrl( 'parent_theme' );
        } else {
            $template_path = SiteInfo::getPath( 'child_theme' );
            $template_url = SiteInfo::getUrl( 'child_theme' );
        }

        if ( is_dir( $template_path ) ) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $template_path,
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
                        $site_path,
                        '/',
                        $filename
                    );

                if ( $path_crawlable ) {
                    if ( is_string( $detected_filename ) ) {
                        array_push(
                            $files,
                            $detected_filename
                        );
                    }
                }
            }
        }

        return $files;
    }
}
