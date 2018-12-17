<?php

class ArchiveProcessor {

    public function __construct() {
        require_once dirname( __FILE__ ) . '/../StaticHtmlOutput/Archive.php';
        $this->archive = new Archive();
        $this->archive->setToCurrentArchive();
        $target_settings = array(
            'general',
            'wpenv',
            'crawling',
            'advanced',
            'processing',
            'netlify',
            'zip',
            'folder',
        );

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/PostSettings.php';
            $this->settings = WPSHO_PostSettings::get( $target_settings );
        } else {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/DBSettings.php';

            $this->settings = WPSHO_DBSettings::get( $target_settings );
        }
    }

    public function create_symlink_to_latest_archive() {
        $this->archive->setToCurrentArchive();

        if ( is_dir( $this->archive->path ) ) {
            $this->remove_symlink_to_latest_archive();
            symlink(
                $this->archive->path,
                $this->settings['wp_uploads_path'] .
                '/latest-export'
            );
        } else {
            error_log( 'failed to symlink latest export directory' );
        }
    }

    public function remove_symlink_to_latest_archive() {
        if (
            is_link( $this->settings['wp_uploads_path'] . '/latest-export' )
            ) {
            unlink( $this->settings['wp_uploads_path'] . '/latest-export' );
        }
    }

    public function remove_files_idential_to_previous_export() {
        $dir_to_diff_against = $this->settings['wp_uploads_path'] .
            '/previous-export';

        // iterate each file in current export, check the size and contents in
        // previous, delete if match
        $objects = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->archive->path,
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        foreach ( $objects as $current_file => $object ) {
            if ( is_file( $current_file ) ) {
                // get relative filename
                $filename = str_replace(
                    $this->archive->path,
                    '',
                    $current_file
                );

                $previously_exported_file =
                    $dir_to_diff_against . '/' . $filename;

                // if file doesn't exist at all in previous export:
                if ( is_file( $previously_exported_file ) ) {
                    if ( $this->files_are_equal(
                        $current_file,
                        $previously_exported_file
                    ) ) {
                        unlink( $current_file );
                    }
                }
            }
        }

        // TODO: cleanup empty archiveDirs to prevent them exporting
        // TODO: replace `exec` calls with native PHP
        // phpcs:disable
        $files_in_previous_export = exec(
            "find $dir_to_diff_against -type f | wc -l"
        );

        $files_to_be_deployed = exec(
            "find $this->archive->path -type f | wc -l"
        );

        // phpcs:enable
        // copy the newly changed files back into the previous export dir,
        // else will never capture changes
        // TODO: this works the first time, but will fail the diff on
        // subsequent runs, alternating each time`
    }

    // default rename in PHP throws warnings if dir is populated
    public function renameWPDirectory( $source, $target ) {
        if ( empty( $source ) || empty ( $target ) ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
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

            StaticHtmlOutput_FilesHelper::delete_dir_with_files(
                $original_dir
            );
        } else {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
            WsLog::l(
                'Trying to rename non-existent directory: ' .
                $original_dir 
            );
        }

        if ( isset( $this->settings['rewriteWPPaths'] ) ) {
        }
    }


    public function files_are_equal( $a, $b ) {
        // if image, use sha, if html, use something else
        $pathinfo = pathinfo( $a );
        if ( isset( $pathinfo['extension'] ) &&
            in_array(
                $pathinfo['extension'],
                array( 'jpg', 'png', 'gif', 'jpeg' )
            ) ) {
            return sha1_file( $a ) === sha1_file( $b );
        }

        // TODO: replace with native calls
        // phpcs:disable
        $diff = exec( "diff $a $b" );
        // phpcs:enable
        $result = $diff === '';

        return $result;
    }


    public function recursive_copy( $srcdir, $dstdir ) {
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
        if ( ! is_dir( $dirname ) ) return false;

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
        if ( ! $this->settings['selected_deployment_option'] === 'folder' ) {
            return;
        }

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
                    require_once dirname( __FILE__ ) .
                        '/../StaticHtmlOutput/WsLog.php';
                    WsLog::l(
                        'Couldn\'t put safety file in ' .
                        'Target Directory' .
                        $target_folder 
                    );

                    die();
                }
            } else {
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/WsLog.php';
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
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/WsLog.php';
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
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/WsLog.php';
                WsLog::l(
                    'Couldn\'t put safety file in ' .
                    'Target Directory' .
                    $target_folder 
                );

                die();
            }
        } else {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
            WsLog::l(
                'Target Directory wasn\t empty ' .
                'or didn\'t contain safety file ' .
                $target_folder 
            );

            die();
        }
    }

    public function createNetlifySpecialFiles() {
        if ( $this->settings['selected_deployment_option'] !== 'netlify' ) {
            return false;
        }

        $redirect_content = $this->settings['netlifyRedirects'];
        $header_content = $this->settings['netlifyHeaders'];

        $redirect_path = $this->archive->path . '_redirects';
        $header_path = $this->archive->path . '_headers';

        file_put_contents( $redirect_path, $redirect_content );
        file_put_contents( $header_path, $header_content );

        chmod( $redirect_path, 0664 );
        chmod( $header_path, 0664 );
    }

    public function create_zip() {
        $deployer = $this->settings['selected_deployment_option'];

        if ( ! in_array( $deployer, array( 'zip', 'netlify' ) ) ) {
            return;
        }

        $archivePath = rtrim( $this->archive->path, '/' );
        $tempZip = $archivePath . '.tmp';

        $zipArchive = new ZipArchive();

        if ( $zipArchive->open( $tempZip, ZIPARCHIVE::CREATE ) !== true ) {
            return new WP_Error( 'Could not create archive' );
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $this->archive->path )
        );

        foreach ( $iterator as $fileName => $fileObject ) {
            $baseName = basename( $fileName );
            if ( $baseName != '.' && $baseName != '..' ) {
                if ( ! $zipArchive->addFile(
                    realpath( $fileName ),
                    str_replace( $this->archive->path, '', $fileName )
                )
                ) {
                    return new WP_Error( 'Could not add file: ' . $fileName );
                }
            }
        }

        $zipArchive->close();

        $zipPath = $this->settings['wp_uploads_path'] . '/' .
            $this->archive->name . '.zip';

        rename( $tempZip, $zipPath );
    }

    public function renameArchiveDirectories() {
        if ( ! isset( $this->settings['rename_rules'] ) ) {
            if ( ! defined( 'WP_CLI' ) ) {
                echo 'SUCCESS';
            }
            return;
        }

        $rename_rules = explode(
            "\n",
            str_replace( "\r", '', $this->settings['rename_rules'] )
        );

        foreach( $rename_rules as $rename_rule_line ) {
            list($original_dir, $target_dir) = explode(',', $rename_rule_line);

            $this->renameWPDirectory( $original_dir, $target_dir );
        }

        // TODO: add to options
        // rm other left over WP identifying files
        if ( file_exists( $this->archive->path . '/xmlrpc.php' ) ) {
            unlink( $this->archive->path . '/xmlrpc.php' );
        }

        if ( file_exists( $this->archive->path . '/wp-login.php' ) ) {
            unlink( $this->archive->path . '/wp-login.php' );
        }

        StaticHtmlOutput_FilesHelper::delete_dir_with_files(
            $this->archive->path . '/wp-json/'
        );

        if ( isset( $this->settings['diffBasedDeploys'] ) ) {
            $this->remove_files_idential_to_previous_export();
        }

        $this->copyStaticSiteToPublicFolder();

        

        if ( ! defined( 'WP_CLI' ) ) {
            echo 'SUCCESS';
        }
    }
}

