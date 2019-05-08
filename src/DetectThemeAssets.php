<?php

namespace WP2Static;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class DetectThemeAssets {

    public static function detect( $theme_type ) {
        $files = array();
        $template_path = '';
        $template_url = '';

        if ( $theme_type === 'parent' ) {
            $template_path = SiteInfo::getPath('parent_theme');
            $template_url = SiteInfo::getUrl('parent_theme');
        } else {
            $template_path = SiteInfo::getPath('child_theme');
            $template_url = SiteInfo::getUrl('child_theme');
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

                $detected_filename =
                    str_replace(
                        $template_path,
                        $template_url,
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
