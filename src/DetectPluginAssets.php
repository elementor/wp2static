<?php

namespace WP2Static;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class DetectPluginAssets {

    public static function detect() {
        $site_info = new SiteInfo();
        $site_info = $site_info->get();

        $files = array();

        /*
            We cannot rely on the plugin's location here, as it will fail for
            a symlinked plugin directory. Our WPSite->plugins_path is more
            reliable.
        */
        $plugins_path = $site_info['plugins_path'];
        $plugins_url = $site_info['plugins_url'];

        $directory = $plugins_path;

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
