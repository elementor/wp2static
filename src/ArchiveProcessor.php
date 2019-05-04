<?php

namespace WP2Static;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class ArchiveProcessor extends Base {

    public function __construct() {
        $this->archive = new Archive();
        $this->archive->setToCurrentArchive();

        $this->loadSettings(
            array(
                'wpenv',
                'crawling',
                'advanced',
                'processing',
                'netlify',
                'zip',
                'folder',
            )
        );
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
        WsLog::l(
            'Recursively copying: ' . $srcdir . ' to ' . $dstdir
        );

        $dir = opendir( $srcdir );

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

        foreach ( scandir( $dirname ) as $file ) {
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

        foreach ( scandir( $dirname ) as $file ) {
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
        if ( $this->settings['selected_deployment_option'] === 'folder' ) {
            $target_folder = trim( $this->settings['targetFolder'] );
            $this->target_folder = $target_folder;

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
                    $this->target_folder
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
    }

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
            return new WP_Error( 'Could not create archive' );
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $this->archive->path )
        );

        foreach ( $iterator as $filename => $file_object ) {
            $base_name = basename( $filename );
            if ( $base_name != '.' && $base_name != '..' ) {
                if ( ! $zip_archive->addFile(
                    realpath( $filename ),
                    str_replace( $this->archive->path, '', $filename )
                )
                ) {
                    return new WP_Error( 'Could not add file: ' . $filename );
                }
            }
        }

        $zip_archive->close();

        $zip_path = $this->settings['wp_uploads_path'] . '/' .
            $this->archive->name . '.zip';

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

