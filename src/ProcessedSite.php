<?php
/*
    ProcessedSite

    A processed version of a StaticSite, with URLs rewritten, folders renamed
    and other modifications made to prepare it for a Deployer
*/

namespace WP2Static;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class ProcessedSite {

    public static function getPath() : string {
        return apply_filters(
            'wp2static_processed_site_path',
            SiteInfo::getPath( 'uploads' ) . 'wp2static-processed-site'
        );
    }

    /**
     * Add static file to ProcessedSite
     */
    public static function add( string $static_file, string $save_path ) : void {
        $full_path = self::getPath() . "/$save_path";

        $directory = dirname( $full_path );

        if ( ! is_dir( $directory ) ) {
            if ( ! wp_mkdir_p( $directory ) ) {
                WsLog::l( 'Couldn\t make directory: ' . $directory );
            }
        }

        copy( $static_file, $full_path );
    }

    /**
     * Delete processed site files
     */
    public static function delete() : void {
        WsLog::l( 'Deleting ProcessedSite files' );

        if ( is_dir( self::getPath() ) ) {
            FilesHelper::deleteDirWithFiles( self::getPath() );
        }
    }

    /**
     *  Get all paths in ProcessedSite
     *
     *  @return string[] ProcessedSite paths
     */
    public static function getPaths() : array {
        $processed_site_dir = self::getPath();

        if ( ! is_dir( $processed_site_dir ) ) {
            return [];
        }

        $paths = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $processed_site_dir,
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        foreach ( $iterator as $filename => $file_object ) {
            $base_name = basename( $filename );
            if ( $base_name != '.' && $base_name != '..' ) {
                $real_filepath = realpath( $filename );

                if ( is_string( $real_filepath ) ) {
                    $paths[] = str_replace( $processed_site_dir, '', $real_filepath );
                }
            }
        }

        sort( $paths );

        return $paths;
    }
}

