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

    public static function getPath() {
        return SiteInfo::getPath( 'uploads') . 'wp2static-processed-site';
    }

    /**
     * Add static file to ProcessedSite
     *
     */
    public static function add( string $static_file, string $save_path ) {
        $full_path = self::getPath() . "/$save_path";

        $directory = dirname( $full_path );

        if ( ! is_dir( $directory ) ) {
            mkdir( $directory, 0755, true );
        }

        copy( $static_file, $full_path );
    }

    /**
     * Delete processed site files
     *
     */
    public static function delete() {
        WsLog::l('Deleting processed site files');

        if ( is_dir( self::getPath() ) ) {
            FilesHelper::delete_dir_with_files( self::getPath() );
        }
    }

    /**
     *  Get all paths in ProcessedSite
     *
     *  @return string[] ProcessedSite paths
     */
    public static function getPaths() : array {
        global $wpdb;

        if ( ! is_dir( self::getPath() ) ) {
            return [];
        }

        $paths = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                self::getPath(),
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        foreach ( $iterator as $filename => $file_object ) {
            $base_name = basename( $filename );
            if ( $base_name != '.' && $base_name != '..' ) {
                $real_filepath = realpath( $filename );

                $paths[] = str_replace( self::getPath(), '', $real_filepath );
            }
        }

        return $paths;
    }
}

