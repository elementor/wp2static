<?php
/*
    ProcessedSite

    A processed version of a StaticSite, with URLs rewritten, folders renamed
    and other modifications made to prepare it for a Deployer
*/

namespace WP2Static;

class ProcessedSite {

    public $path;

    /**
     * ProcessedSite constructor
     *
     * @param string $path path to static site directory
     */
    public function __construct(string $path) {
        $this->path = $this->create_directory( $path );
    }

    /**
     * Create  dir
     *
     * @param string $path processed site directory
     * @throws WP2StaticException
     */
    private function create_directory( $path ) : string {
        if ( is_dir( $path ) ) {
            return $path;
        }

        if ( ! mkdir( $path ) ) {
            $err = "Couldn't create ProcessedSite directory:" . $path;
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        return $path;
    }

    /**
     * Add static file to ProcessedSite
     *
     */
    public function add( string $static_file, string $save_path ) {
        $full_path = "$this->path/$save_path";

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
    public function delete() {
        error_log('deleting processed site files');

        if ( is_dir( $this->path ) ) {
            FilesHelper::delete_dir_with_files( $this->path );
        }
    }
}

