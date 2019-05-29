<?php

namespace WP2Static;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Exception;

class ArchiveProcessor extends Base {

    public $archive;

    public function __construct() {
        $this->archive = new Archive();

        $this->loadSettings();
    }

    public function renameWPDirectory( $source, $target ) {
        if ( empty( $source ) || empty( $target ) ) {
            WsLog::l(
                'Failed trying to rename: ' .
                'Source: ' . $source .
                ' to: ' . $target
            );
            die();
        }

        $original_dir = $this->archive->path . $source;
        $new_dir = $this->archive->path . $target;

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

    public function recursive_copy( $srcdir, $dstdir ) {
        $dir = opendir( $srcdir );

        if ( ! $dir ) {
            $err = 'Trying to copy non-existent directory: ' . $srcdir;
            WsLog::l( $err );
            throw new Exception( $err );
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

    public function dir_is_empty( $dirname ) {
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

    public function dir_has_safety_file( $dirname ) {
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

    public function put_safety_file( $dirname ) {
        if ( ! is_dir( $dirname ) ) {
            return false;
        }

        $safety_file = $dirname . '/.wp2static_safety';
        $result = file_put_contents( $safety_file, 'wp2static' );

        chmod( $safety_file, 0664 );

        return $result;
    }

    public function copyStaticSiteToPublicFolder() {
        if ( $this->settings['selected_deployment_option'] !== 'folder' ) {
            return;
        }

        $target_folder = trim( $this->settings['targetFolder'] );

        if ( ! $target_folder ) {
            return;
        }

        // instantiate with safe defaults
        $directory_exists = true;
        $directory_empty = false;
        $dir_has_safety_file = false;

        // CHECK #1: directory exists or can be created
        $directory_exists = is_dir( $target_folder );

        if ( $directory_exists ) {
            $directory_empty = $this->dir_is_empty( $target_folder );
        } else {
            if ( wp_mkdir_p( $target_folder ) ) {
                if ( ! $this->put_safety_file( $target_folder ) ) {
                    WsLog::l(
                        'Couldn\'t put safety file in ' .
                        'Target Directory' .
                        $target_folder
                    );

                    die();
                }
            } else {
                WsLog::l(
                    'Couldn\'t create Target Directory: ' .
                    $target_folder
                );

                die();
            }
        }

        // CHECK #2: check directory empty and add safety file
        if ( $directory_empty ) {
            if ( ! $this->put_safety_file( $target_folder ) ) {
                WsLog::l(
                    'Couldn\'t put safety file in ' .
                    'Target Directory' .
                    $target_folder
                );

                die();
            }
        }

        $dir_has_safety_file =
            $this->dir_has_safety_file( $target_folder );

        if ( $directory_empty || $dir_has_safety_file ) {
            $this->recursive_copy(
                $this->archive->path,
                $target_folder
            );

            if ( ! $this->put_safety_file( $target_folder ) ) {
                WsLog::l(
                    'Couldn\'t put safety file in ' .
                    'Target Directory' .
                    $target_folder
                );

                die();
            }
        } else {
            WsLog::l(
                'Target Directory wasn\'t empty ' .
                'or didn\'t contain safety file ' .
                $target_folder
            );

            die();
        }
    }

    // TODO: migrate to add-on
    public function createNetlifySpecialFiles() {
        if ( $this->settings['selected_deployment_option'] !== 'netlify' ) {
            return false;
        }

        if ( isset( $this->settings['netlifyRedirects'] ) ) {
            $redirect_content = $this->settings['netlifyRedirects'];
            $redirect_path = $this->archive->path . '_redirects';
            file_put_contents( $redirect_path, $redirect_content );
            chmod( $redirect_path, 0664 );
        }

        if ( isset( $this->settings['netlifyHeaders'] ) ) {
            $header_content = $this->settings['netlifyHeaders'];
            $header_path = $this->archive->path . '_headers';
            file_put_contents( $header_path, $header_content );
            chmod( $header_path, 0664 );
        }
    }

    public function create_zip() {
        $deployer = $this->settings['selected_deployment_option'];

        if ( ! in_array( $deployer, array( 'zip', 'netlify' ) ) ) {
            return;
        }

        $archive_path = rtrim( $this->archive->path, '/' );
        $temp_zip = $archive_path . '.tmp';

        $zip_archive = new ZipArchive();

        if ( $zip_archive->open( $temp_zip, ZipArchive::CREATE ) !== true ) {
            $err = 'Could not create zip: ' . $temp_zip;
            WsLog::l( $err );
            throw new Exception( $err );
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->archive->path,
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
                    throw new Exception( $err );
                }

                if ( ! $zip_archive->addFile(
                    $real_filepath,
                    str_replace( $this->archive->path, '', $filename )
                )
                ) {
                    $err = 'Could not add file: ' . $filename;
                    WsLog::l( $err );
                    throw new Exception( $err );
                }
            }
        }

        $zip_archive->close();

        $zip_path = SiteInfo::getPath( 'uploads' ) .
            'wp2static-exported-site.zip';

        rename( $temp_zip, $zip_path );
    }

    public function removeWPCruft() {
        if ( file_exists( $this->archive->path . '/xmlrpc.php' ) ) {
            unlink( $this->archive->path . '/xmlrpc.php' );
        }

        if ( file_exists( $this->archive->path . '/wp-login.php' ) ) {
            unlink( $this->archive->path . '/wp-login.php' );
        }

        FilesHelper::delete_dir_with_files(
            $this->archive->path . '/wp-json/'
        );
    }

    /*
        Takes user-defined directory renaming rules, sorts them
        by longest path (mitigating issues with user-input order PR#216)
    */
    public function renameArchiveDirectories() {
        if ( ! isset( $this->settings['rename_rules'] ) ) {
            return;
        }

        $rename_rules = explode(
            "\n",
            str_replace( "\r", '', $this->settings['rename_rules'] )
        );

        $tmp_rules = array();

        foreach ( $rename_rules as $rename_rule_line ) {
            if ( $rename_rule_line ) {
                list($original_dir, $target_dir) =
                    explode( ',', $rename_rule_line );

                $tmp_rules[ $original_dir ] = $target_dir;
            }
        }

        uksort( $tmp_rules, array( $this, 'ruleSort' ) );

        foreach ( $tmp_rules as $original_dir => $target_dir ) {
            $this->renameWPDirectory( $original_dir, $target_dir );
        }
    }
}

