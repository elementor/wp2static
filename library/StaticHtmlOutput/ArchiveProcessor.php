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
        // TODO: put safeguard here to avoid rm'ing core dirs
        // in the case that other variables are empty
        if ( isset( $this->settings['rewriteWPPaths'] ) ) {
            $this->recursive_copy( $source, $target );

            StaticHtmlOutput_FilesHelper::delete_dir_with_files( $source );
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


    public function copyStaticSiteToPublicFolder() {
        if ( $this->settings['selected_deployment_option'] === 'folder' ) {
            $target_folder = trim( $this->settings['targetFolder'] );

            // safeguards

            // allow creating the dir if it doesn't exist
                // put a file "wp2static_safety" in it

            // disallow dir exists and doesn't contain "wp2static_safety"

            if ( ! empty( $target_folder ) ) {
                // if dir isn't empty and current deployment option is "folder"
                $target_folder = ABSPATH . $target_folder;

                // mkdir for the new dir
                if ( ! file_exists( $target_folder ) ) {
                    if ( wp_mkdir_p( $target_folder ) ) {
                        // file permissions for public viewing of files within
                        chmod( $target_folder, 0755 );

                        // copy contents of current archive to the targetFolder
                        $this->recursive_copy(
                            $this->archive->path,
                            $target_folder
                        );

                    } else {
                        error_log( 'Target folder could not be made ERR#A5' );
                    }
                } else {
                    $this->recursive_copy(
                        $this->archive->path,
                        $target_folder
                    );
                }
            }
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

    public function renameWPDirectories() {
        // rename dirs (in reverse order than when doing in responsebody)
        // rewrite wp-content  dir
        $wp_content_dir = WP_CONTENT_DIR;
        $wp_content_dir_name = str_replace( ABSPATH, '', $wp_content_dir );

        $original_wp_content = $this->archive->path .
            '/' . $wp_content_dir_name;

        // rename the theme theme root before the nested theme dir
        // rename the theme directory
        $new_wp_content = $this->archive->path . '/' .
            $this->settings['rewriteWPCONTENT'];
        $new_theme_root = $new_wp_content . '/' .
            $this->settings['rewriteTHEMEROOT'];
        $new_theme_dir = $new_theme_root . '/' .
            $this->settings['rewriteTHEMEDIR'];

        // rewrite uploads dir
        $default_upload_dir = wp_upload_dir(); // need to store as var first
        $updated_uploads_dir = str_replace(
            WP_CONTENT_DIR,
            '',
            $default_upload_dir['basedir']
        );

        $updated_uploads_dir = $new_wp_content . '/' . $updated_uploads_dir;
        $new_uploads_dir = $new_wp_content . '/' .
            $this->settings['rewriteUPLOADS'];

        // TODO: get these WP vars out from here
        $updated_theme_root = str_replace( ABSPATH, '/', get_theme_root() );
        $updated_theme_root = $new_wp_content .
            str_replace( 'wp-content', '/', $updated_theme_root );

        $updated_theme_dir = $new_theme_root .
            '/' . basename( get_template_directory_uri() );

        $updated_theme_dir = str_replace( '\/\/', '', $updated_theme_dir );

        // rewrite plugins dir
        $updated_plugins_dir = str_replace( ABSPATH, '/', WP_PLUGIN_DIR );
        $updated_plugins_dir = str_replace(
            'wp-content/',
            '',
            $updated_plugins_dir
        );

        $updated_plugins_dir = $new_wp_content . $updated_plugins_dir;
        $new_plugins_dir = $new_wp_content . '/' .
            $this->settings['rewritePLUGINDIR'];

        // rewrite wp-includes  dir
        $original_wp_includes = $this->archive->path . WPINC;
        $new_wp_includes = $this->archive->path .
            $this->settings['rewriteWPINC'];

        if ( file_exists( $original_wp_content ) ) {
            $this->renameWPDirectory( $original_wp_content, $new_wp_content );
        }

        if ( file_exists( $updated_uploads_dir ) ) {
            $this->renameWPDirectory( $updated_uploads_dir, $new_uploads_dir );
        }

        if ( file_exists( $updated_theme_root ) ) {
            $this->renameWPDirectory( $updated_theme_root, $new_theme_root );
        }

        if ( file_exists( $updated_theme_dir ) ) {
            $this->renameWPDirectory( $updated_theme_dir, $new_theme_dir );
        }

        if ( file_exists( $updated_plugins_dir ) ) {
            $this->renameWPDirectory( $updated_plugins_dir, $new_plugins_dir );

        }

        if ( file_exists( $original_wp_includes ) ) {
            $this->renameWPDirectory(
                $original_wp_includes,
                $new_wp_includes
            );

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

