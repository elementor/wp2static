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
            error_log('TODO: load settings from DB');
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
            $dir_to_diff_against = $this->settings['wp_uploads_path'] . '/previous-export';

            if ( $this->settings['diffBasedDeploys'] ) {
                $archiveDir = file_get_contents(
                    $this->settings['working_directory'] . '/WP-STATIC-CURRENT-ARCHIVE'
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
                unlink( $this->settings['working_directory'] . '/' . $file_to_clean );
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
                $this->settings['working_directory'] . '/WP-STATIC-CURRENT-ARCHIVE',
                'r'
            );
            $this->settings['archive_dir'] = stream_get_line( $handle, 0 );

            $dir_to_diff_against =
                $this->settings['working_directory'] . '/previous-export';

            if ( is_dir( $dir_to_diff_against ) ) {
                // TODO: rewrite to php native in case of shared hosting
                // delete archivedir and then recursively copy
                // phpcs:disable
                shell_exec(
                    "cp -r $dir_to_diff_against/* $this->settings['archiveDir']/"
                );
                // phpcs:enable
            }
        }

        $files_to_clean = array(
            '/WP-STATIC-EXPORT-S3-FILES-TO-EXPORT',
            '/WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT',
            '/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT',
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
                unlink( $this->settings['working_directory'] . '/' . $file_to_clean );
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
            preg_grep( '/^([^.])/', scandir( $this->settings['working_directory'] ) );

        foreach ( $leftover_files as $fileName ) {
            if ( strpos( $fileName, 'wp-static-html-output-' ) !== false ) {
                if ( is_dir( $this->settings['working_directory'] . '/' . $fileName ) ) {
                    StaticHtmlOutput_FilesHelper::delete_dir_with_files(
                        $this->settings['working_directory'] . '/' . $fileName
                    );
                } else {
                    unlink( $this->settings['working_directory'] . '/' . $fileName );
                }
            }
        }

        echo 'SUCCESS';
    }

    public function generateModifiedFileList() {
        // copy the preview crawl list within uploads dir to "modified list"
        copy(
            $this->settings['wp_uploads_path'] . '/WP-STATIC-INITIAL-CRAWL-LIST',
            $this->settings['wp_uploads_path'] . '/WP-STATIC-MODIFIED-CRAWL-LIST'
        );

        // process  modified list and make available for previewing from UI
        // $initial_file_list_count = StaticHtmlOutput_FilesHelper::buildFina..
        // $viaCLI,
        // $this->settings['additionalUrls'],
        // $this->getWorkingDirectory(),
        // $this->settings['uploadsURL'],
        // $this->getWorkingDirectory(),
        // self::HOOK
        // );
        // copy the modified list to the working dir "finalized crawl list"
        copy(
            $this->settings['wp_uploads_path'] . '/WP-STATIC-MODIFIED-CRAWL-LIST',
            $this->settings['working_directory'] . '/WP-STATIC-FINAL-CRAWL-LIST'
        );

        // use finalized crawl list from working dir to start the export
        // if list has been (re)generated in the frontend, use it, else
        // generate again at export time
    }
}

