<?php

namespace WP2Static;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Exception;

class ArchiveProcessor {

    private $archive_path;
    private $settings;

    public function __construct() {
        $this->archive_path = SiteInfo::getPath( 'uploads' ) .
            'wp2static-exported-site/';

        $plugin = Controller::getInstance();
        $this->settings = $plugin->options->getSettings( true );
    }

    public function renameWPDirectory(
        string $source,
        string $target
    ) :void {
        if ( empty( $source ) || empty( $target ) ) {
            WsLog::l(
                'Failed trying to rename: ' .
                'Source: ' . $source .
                ' to: ' . $target
            );
            die();
        }

        $original_dir = $this->archive_path . $source;
        $new_dir = $this->archive_path . $target;

        if ( is_dir( $original_dir ) ) {
            $this->recursive_copy( $original_dir, $new_dir );

            FilesHelper::delete_dir_with_files(
                $original_dir
            );
        } else {
            WsLog::l(
                'Trying to rename non-existent directory: ' .
                $original_dir
            );
        }
    }

    /**
     *  Recursively copy a directory
     *
     * @throws WP2StaticException
     */
    public function recursive_copy(
        string $srcdir,
        string $dstdir
    ) : void {
        $dir = opendir( $srcdir );

        if ( ! $dir ) {
            $err = 'Trying to copy non-existent directory: ' . $srcdir;
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        if ( ! is_dir( $dstdir ) ) {
            mkdir( $dstdir );
        }

        while ( $file = readdir( $dir ) ) {
            if ( $file != '.' && $file != '..' ) {
                $src = $srcdir . '/' . $file;
                $dst = $dstdir . '/' . $file;
                if ( is_dir( $src ) ) {
                    $this->recursive_copy( $src, $dst );
                } else {
                    copy( $src, $dst );
                }
            }
        }
        closedir( $dir );
    }

    public function dir_is_empty( string $dirname ) : bool {
        if ( ! is_dir( $dirname ) ) {
            return false;
        }

        $dotfiles = array( '.', '..', '/.wp2static_safety' );

        $dir_files = scandir( $dirname );

        if ( ! $dir_files ) {
            return false;
        }

        // FALSE if we have anything besides dot paths or our safety file
        foreach ( $dir_files as $file ) {
            if ( ! in_array( $file, $dotfiles ) ) {
                 return false;
            }
        }

        return true;
    }

    public function dir_has_safety_file( string $dirname ) : bool {
        if ( ! is_dir( $dirname ) ) {
            return false;
        }

        $dir_files = scandir( $dirname );

        if ( ! $dir_files ) {
            return false;
        }

        foreach ( $dir_files as $file ) {
            if ( $file == '.wp2static_safety' ) {
                 return true;
            }
        }

        return false;
    }

    // TODO: migrate to add-on
    public function createNetlifySpecialFiles() : void {
        if ( $this->settings['currentDeploymentMethod'] !== 'netlify' ) {
            return;
        }

        if ( isset( $this->settings['netlifyRedirects'] ) ) {
            $redirect_content = $this->settings['netlifyRedirects'];
            $redirect_path = $this->archive_path . '_redirects';
            file_put_contents( $redirect_path, $redirect_content );
            chmod( $redirect_path, 0664 );
        }

        if ( isset( $this->settings['netlifyHeaders'] ) ) {
            $header_content = $this->settings['netlifyHeaders'];
            $header_path = $this->archive_path . '_headers';
            file_put_contents( $header_path, $header_content );
            chmod( $header_path, 0664 );
        }
    }

    /**
     *  Create ZIP of export dir
     *
     * @throws WP2StaticException
     */
    public function create_zip() : void {
        $deployer = $this->settings['currentDeploymentMethod'];

        if ( ! in_array( $deployer, array( 'zip', 'netlify' ) ) ) {
            return;
        }

        $archive_path = rtrim( $this->archive_path, '/' );
        $temp_zip = $archive_path . '.tmp';

        $zip_archive = new ZipArchive();

        if ( $zip_archive->open( $temp_zip, ZipArchive::CREATE ) !== true ) {
            $err = 'Could not create zip: ' . $temp_zip;
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->archive_path,
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        foreach ( $iterator as $filename => $file_object ) {
            $base_name = basename( $filename );
            if ( $base_name != '.' && $base_name != '..' ) {
                $real_filepath = realpath( $filename );

                if ( ! $real_filepath ) {
                    $err = 'Trying to add unknown file to Zip: ' . $filename;
                    WsLog::l( $err );
                    throw new WP2StaticException( $err );
                }

                // Standardise all paths to use / (Windows support)
                $filename = str_replace( '\\', '/', $filename );

                if ( ! is_string( $filename ) ) {
                    continue;
                }

                if ( ! $zip_archive->addFile(
                    $real_filepath,
                    str_replace( $this->archive_path, '', $filename )
                )
                ) {
                    $err = 'Could not add file: ' . $filename;
                    WsLog::l( $err );
                    throw new WP2StaticException( $err );
                }
            }
        }

        $zip_archive->close();

        $zip_path = SiteInfo::getPath( 'uploads' ) .
            'wp2static-exported-site.zip';

        rename( $temp_zip, $zip_path );
    }

    public function removeWPCruft() : void {
        if ( file_exists( $this->archive_path . '/xmlrpc.php' ) ) {
            unlink( $this->archive_path . '/xmlrpc.php' );
        }

        if ( file_exists( $this->archive_path . '/wp-login.php' ) ) {
            unlink( $this->archive_path . '/wp-login.php' );
        }

        FilesHelper::delete_dir_with_files(
            $this->archive_path . '/wp-json/'
        );
    }

    /*
        Takes user-defined directory renaming rules, sorts them
        by longest path (mitigating issues with user-input order PR#216)
    */
    public function renameArchiveDirectories() : void {
        if ( ! isset( $this->settings['renameRules'] ) ) {
            return;
        }

        $rename_rules = explode(
            "\n",
            str_replace( "\r", '', $this->settings['renameRules'] )
        );

        $tmp_rules = array();

        foreach ( $rename_rules as $rename_rule_line ) {
            if ( $rename_rule_line ) {
                list($original_dir, $target_dir) =
                    explode( ',', $rename_rule_line );

                $tmp_rules[ $original_dir ] = $target_dir;
            }
        }

        uksort(
            $tmp_rules,
            function ( $str1, $str2 ) {
                return 0 - strcmp( $str1, $str2 );
            }
        );

        foreach ( $tmp_rules as $original_dir => $target_dir ) {
            $this->renameWPDirectory( $original_dir, $target_dir );
        }
    }
}

