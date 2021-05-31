<?php

namespace WP2Static;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class DetectPluginAssets {

    /**
     * Detect Plugin asset URLs
     *
     * @return string[] list of URLs
     */
    public static function detect() : array {
        $files = [];

        $plugins_path = SiteInfo::getPath( 'plugins' );
        $plugins_url = SiteInfo::getUrl( 'plugins' );

        if ( is_dir( $plugins_path ) ) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $plugins_path,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            );

            $active_plugins = get_option( 'active_plugins' );

            if ( is_multisite() ) {
                $active_plugins = array_unique(
                    array_merge(
                        $active_plugins,
                        array_keys( get_site_option( 'active_sitewide_plugins' ) )
                    )
                );
            }

            $active_plugin_dirs = array_map(
                function ( $active_plugin ) {
                    return explode( '/', $active_plugin )[0];
                },
                $active_plugins
            );

            foreach ( $iterator as $filename => $file_object ) {
                $path_crawlable =
                    FilesHelper::filePathLooksCrawlable( $filename );

                if ( ! $path_crawlable ) {
                    continue;
                }

                $matches_active_plugin_dir =
                    ( str_replace( $active_plugin_dirs, '', $filename ) !== $filename );

                if ( ! $matches_active_plugin_dir ) {
                    continue;
                }

                // Standardise all paths to use / (Windows support)
                $filename = str_replace( '\\', '/', $filename );

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

                if ( is_string( $detected_filename ) ) {
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
