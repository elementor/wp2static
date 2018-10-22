<?php

class Exporter {

    public function __construct() {
        $target_settings = array(
            'general',
            'wpenv',
            'crawling',
            'advanced',
        );

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $target_settings );

        } else {
            error_log( 'TODO: load settings from DB' );
        }
    }

    public function capture_last_deployment() {
        require_once dirname( __FILE__ ) . '/../StaticHtmlOutput/Archive.php';
        $archive = new Archive();

        if ( ! $archive->currentArchiveExists() ) {
            return;
        }

        // TODO: big cleanup required here, very iffy code
        // skip for first export state
        if ( is_file( $archive->path ) ) {
            $archiveDir = file_get_contents(
                $this->settings['working_directory'] .
                    '/WP-STATIC-CURRENT-ARCHIVE'
            );
            $previous_export = $archiveDir;
            $dir_to_diff_against = $this->settings['wp_uploads_path'] .
                '/previous-export';

            if ( $this->settings['diffBasedDeploys'] ) {
                $archiveDir = file_get_contents(
                    $this->settings['working_directory'] .
                        '/WP-STATIC-CURRENT-ARCHIVE'
                );

                $previous_export = $archiveDir;
                $dir_to_diff_against =
                    $this->settings['wp_uploads_path'] . '/previous-export';

                if ( is_dir( $previous_export ) ) {
                    // TODO: replace shell calles with native
                    // phpcs:disable
                    shell_exec(
                        "rm -Rf $dir_to_diff_against && mkdir -p " .
                        "$dir_to_diff_against && cp -r $previous_export/* " .
                        "$dir_to_diff_against"
                    );
                    // phpcs:enable
                }
            } else {
                if ( is_dir( $dir_to_diff_against ) ) {
                    StaticHtmlOutput_FilesHelper::delete_dir_with_files(
                        $dir_to_diff_against
                    );
                    StaticHtmlOutput_FilesHelper::delete_dir_with_files(
                        $archiveDir
                    );
                }
            }
        }
    }

    public function pre_export_cleanup() {
        $files_to_clean = array(
            '/WP-STATIC-EXPORT-S3-FILES-TO-EXPORT',
            '/WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT',
            '/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT',
            '/WP-STATIC-EXPORT-BITBUCKET-FILES-TO-EXPORT',
            '/WP-STATIC-EXPORT-DROPBOX-FILES-TO-EXPORT',
            '/WP-STATIC-EXPORT-BUNNYCDN-FILES-TO-EXPORT',
            '/WP-STATIC-CRAWLED-LINKS',
            '/WP-STATIC-DISCOVERED-URLS',
            '/WP-STATIC-FINAL-CRAWL-LIST',
            '/WP-STATIC-2ND-CRAWL-LIST',
            '/WP-STATIC-FINAL-2ND-CRAWL-LIST',
            'WP-STATIC-EXPORT-LOG',
        );

        foreach ( $files_to_clean as $file_to_clean ) {
            if ( file_exists(
                $this->settings['working_directory'] . '/' . $file_to_clean
            ) ) {
                unlink(
                    $this->settings['working_directory'] . '/' .
                        $file_to_clean
                );
            }
        }
    }

    public function cleanup_working_files() {
        error_log( 'cleanup_working_files()' );
        // skip first explort state
        if ( is_file(
            $this->settings['working_directory'] . '/WP-STATIC-CURRENT-ARCHIVE'
        ) ) {

            $handle = fopen(
                $this->settings['working_directory'] .
                    '/WP-STATIC-CURRENT-ARCHIVE',
                'r'
            );
            $this->settings['archive_dir'] = stream_get_line( $handle, 0 );

            $src_dir =
                $this->settings['working_directory'] . '/previous-export';

            if ( is_dir( $src_dir ) ) {
                // TODO: rewrite to php native in case of shared hosting
                // delete archivedir and then recursively copy
                // phpcs:disable
                shell_exec(
                    "cp -r $src_dir/* $this->settings['archiveDir']/"
                );
                // phpcs:enable
            }
        }

        $files_to_clean = array(
            '/WP-STATIC-EXPORT-S3-FILES-TO-EXPORT',
            '/WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT',
            '/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT',
            '/WP-STATIC-EXPORT-BITBUCKET-FILES-TO-EXPORT',
            '/WP-STATIC-EXPORT-DROPBOX-FILES-TO-EXPORT',
            '/WP-STATIC-EXPORT-BUNNYCDN-FILES-TO-EXPORT',
            '/WP-STATIC-CRAWLED-LINKS',
            '/WP-STATIC-DISCOVERED-URLS',
            '/WP-STATIC-FINAL-CRAWL-LIST',
            '/WP-STATIC-2ND-CRAWL-LIST',
            '/WP-STATIC-FINAL-2ND-CRAWL-LIST',
        );

        foreach ( $files_to_clean as $file_to_clean ) {
            if ( file_exists(
                $this->settings['working_directory'] . '/' . $file_to_clean
            ) ) {
                unlink(
                    $this->settings['working_directory'] . '/' . $file_to_clean
                );
            }
        }
    }

    public function initialize_cache_files() {
        $crawled_links_file =
            $this->settings['working_directory'] . '/WP-STATIC-CRAWLED-LINKS';

        $resource = fopen( $crawled_links_file, 'w' );
        fwrite( $resource, '' );
        fclose( $resource );
    }

    public function cleanup_leftover_archives() {
        $leftover_files =
            preg_grep(
                '/^([^.])/',
                scandir( $this->settings['working_directory'] )
            );

        foreach ( $leftover_files as $fileName ) {
            if ( strpos( $fileName, 'wp-static-html-output-' ) !== false ) {
                $deletion_target = $this->settings['working_directory'] .
                    '/' . $fileName;
                if ( is_dir( $deletion_target ) ) {
                    StaticHtmlOutput_FilesHelper::delete_dir_with_files(
                        $deletion_target
                    );
                } else {
                    unlink( $deletion_target );
                }
            }
        }

        echo 'SUCCESS';
    }

    public function generateModifiedFileList() {
        // preserve the initial crawl list, to be used in debugging + more
        copy(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-INITIAL-CRAWL-LIST',
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-MODIFIED-CRAWL-LIST'
        );

        // if no excludes or includes, just copy to new targey
        if ( ! isset( $this->settings['excludeURLs'] ) &&
            ! isset( $this->settings['additionalUrls'] ) ) {
            copy(
                $this->settings['wp_uploads_path'] .
                    '/WP-STATIC-INITIAL-CRAWL-LIST',
                $this->settings['wp_uploads_path'] .
                    '/WP-STATIC-FINAL-CRAWL-LIST'
            );

            return;
        }

        // TODO: applying exlusions & inclusions against modified crawl list
        $modified_crawl_list = array();

        // load crawl list into array
        $crawl_list = file(
            $this->settings['wp_uploads_path'] .
            '/WP-STATIC-MODIFIED-CRAWL-LIST'
        );

        // applying exclusions first
        if ( isset( $this->settings['excludeURLs'] ) ) {
            $exclusions = explode(
                "\n",
                str_replace( "\r", '', $this->settings['excludeURLs'] )
            );

            // iterate through crawl list and add any that aren't excluded
            foreach ( $crawl_list as $url_to_crawl ) {
                $url_to_crawl = trim( $url_to_crawl );
                $match = false;

                foreach ( $exclusions as $exclusion ) {
                    $exclusion = trim( $exclusion );

                    if ( $exclusion != '' ) {
                        if ( strpos( $url_to_crawl, $exclusion ) !== false ) {
                            $match = true;
                        }
                    }

                    if ( ! $match ) {
                        $modified_crawl_list[] = $url_to_crawl;
                    }
                }
            }
        }

        // apply inclusions
        if ( isset( $this->settings['additionalUrls'] ) ) {
            $inclusions = explode(
                "\n",
                str_replace( "\r", '', $this->settings['additionalUrls'] )
            );

            foreach ( $inclusions as $inclusion ) {
                $inclusion = trim( $inclusion );

                $modified_crawl_list[] = $inclusion;
            }
        }

        // remove duplicates
        $modified_crawl_list = array_unique( $modified_crawl_list );

        $str = implode( PHP_EOL, $modified_crawl_list );
        file_put_contents(
            $this->settings['working_directory'] .
                '/WP-STATIC-FINAL-CRAWL-LIST',
            $str
        );
    }
}

