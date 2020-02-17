<?php
/*
    ProcessedSite

    A processed version of a StaticSite, with URLs rewritten, folders renamed
    and other modifications made to prepare it for a Deployer
*/

namespace WP2Static;

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
}

