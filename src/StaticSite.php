<?php
/*
    StaticSite

    The resulting output of crawling the WordPress site

    Site URLs are all made absolute for easier rewriting during deployment
*/

namespace WP2Static;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class StaticSite {

    /**
     * Add crawled resource to static site
     */
    public static function add( string $path, string $contents ) : void {
        // simple file save, Crawler holds logic for what/where to save
        // Crawler has already processed links, etc
        $full_path = self::getPath() . "$path";

        $directory = dirname( $full_path );

        if ( ! is_dir( $directory ) ) {
            if ( ! wp_mkdir_p( $directory ) ) {
                WsLog::l( 'Couldn\t make directory: ' . $directory );
            }
        }

        file_put_contents( $full_path, $contents );
    }

    public static function getPath() : string {
        return SiteInfo::getPath( 'uploads' ) . 'wp2static-crawled-site';
    }

    /**
     * Delete StaticSite files
     */
    public static function delete() : void {
        WsLog::l( 'Deleting StaticSite files' );

        if ( is_dir( self::getPath() ) ) {
            FilesHelper::deleteDirWithFiles( self::getPath() );

            // CrawlCache not useful without StaticSite files
            CrawlCache::truncate();
        }
    }

    /**
     *  Get all paths in StaticSite
     *
     *  @return string[] StaticSite paths
     */
    public static function getPaths() : array {
        global $wpdb;
        $static_site_dir = self::getPath();

        if ( ! is_dir( $static_site_dir ) ) {
            return [];
        }

        $paths = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $static_site_dir,
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        foreach ( $iterator as $filename => $file_object ) {
            $base_name = basename( $filename );
            if ( $base_name != '.' && $base_name != '..' ) {
                $real_filepath = realpath( $filename );

                if ( is_string( $real_filepath ) ) {
                    $paths[] = str_replace( $static_site_dir, '', $real_filepath );
                }
            }
        }

        sort( $paths );

        return $paths;
    }
}

