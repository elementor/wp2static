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
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/DBSettings.php';

            $this->settings = WPSHO_DBSettings::get( $target_settings );
        }
    }

    public function pre_export_cleanup() {
        $files_to_clean = array(
            'WP-STATIC-2ND-CRAWL-LIST.txt',
            'WP-STATIC-404-LOG.txt',
            'WP-STATIC-CRAWLED-LINKS.txt',
            'WP-STATIC-DISCOVERED-URLS-LOG.txt',
            'WP-STATIC-DISCOVERED-URLS.txt',
            'WP-STATIC-EXPORT-BITBUCKET-FILES-TO-EXPORT.txt',
            'WP-STATIC-EXPORT-BUNNYCDN-FILES-TO-EXPORT.txt',
            'WP-STATIC-EXPORT-DROPBOX-FILES-TO-EXPORT.txt',
            'WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT.txt',
            'WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT.txt',
            'WP-STATIC-EXPORT-LOG.txt',
            'WP-STATIC-EXPORT-S3-FILES-TO-EXPORT.txt',
            'WP-STATIC-FINAL-2ND-CRAWL-LIST.txt',
            'WP-STATIC-FINAL-CRAWL-LIST.txt',
        );

        foreach ( $files_to_clean as $file_to_clean ) {
            if ( file_exists(
                $this->settings['wp_uploads_path'] . '/' . $file_to_clean
            ) ) {
                unlink(
                    $this->settings['wp_uploads_path'] . '/' .
                        $file_to_clean
                );
            }
        }
    }

    public function cleanup_working_files() {
        // keep log files here for debugging
        // skip first export state
        if ( is_file(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-CURRENT-ARCHIVE.txt'
        ) ) {

            $handle = fopen(
                $this->settings['wp_uploads_path'] .
                    '/WP-STATIC-CURRENT-ARCHIVE.txt',
                'r'
            );
            $this->settings['archive_dir'] = stream_get_line( $handle, 0 );

            $src_dir =
                $this->settings['wp_uploads_path'] . '/previous-export';

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
            '/WP-STATIC-2ND-CRAWL-LIST.txt',
            '/WP-STATIC-CRAWLED-LINKS.txt',
            '/WP-STATIC-DISCOVERED-URLS.txt',
            '/WP-STATIC-EXPORT-BITBUCKET-FILES-TO-EXPORT.txt',
            '/WP-STATIC-EXPORT-BUNNYCDN-FILES-TO-EXPORT.txt',
            '/WP-STATIC-EXPORT-DROPBOX-FILES-TO-EXPORT.txt',
            '/WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT.txt',
            '/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT.txt',
            '/WP-STATIC-EXPORT-S3-FILES-TO-EXPORT.txt',
            '/WP-STATIC-FINAL-2ND-CRAWL-LIST.txt',
            '/WP-STATIC-FINAL-CRAWL-LIST.txt',
        );

        foreach ( $files_to_clean as $file_to_clean ) {
            if ( file_exists(
                $this->settings['wp_uploads_path'] . '/' . $file_to_clean
            ) ) {
                unlink(
                    $this->settings['wp_uploads_path'] . '/' . $file_to_clean
                );
            }
        }
    }

    public function initialize_cache_files() {
        // TODO: is this still necessary?
        $crawled_links_file =
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-CRAWLED-LINKS.txt';

        $resource = fopen( $crawled_links_file, 'w' );
        fwrite( $resource, '' );
        fclose( $resource );
    }

    public function cleanup_leftover_archives() {
        $leftover_files =
            preg_grep(
                '/^([^.])/',
                scandir( $this->settings['wp_uploads_path'] )
            );

        foreach ( $leftover_files as $fileName ) {
            if ( strpos( $fileName, 'wp-static-html-output-' ) !== false ) {
                $deletion_target = $this->settings['wp_uploads_path'] .
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

        if ( ! defined( 'WP_CLI' ) ) {
            echo 'SUCCESS';
        }
    }

    public function generateModifiedFileList() {
        // preserve the initial crawl list, to be used in debugging + more
        copy(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-INITIAL-CRAWL-LIST.txt',
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-MODIFIED-CRAWL-LIST.txt'
        );

        chmod(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-MODIFIED-CRAWL-LIST.txt',
            0664
        );

        // if no excludes or includes, just copy to new target
        if ( ! isset( $this->settings['excludeURLs'] ) &&
            ! isset( $this->settings['additionalUrls'] ) ) {
            copy(
                $this->settings['wp_uploads_path'] .
                    '/WP-STATIC-INITIAL-CRAWL-LIST.txt',
                $this->settings['wp_uploads_path'] .
                    '/WP-STATIC-FINAL-CRAWL-LIST.txt'
            );

            return;
        }

        // TODO: applying exlusions & inclusions against modified crawl list
        $modified_crawl_list = array();

        // load crawl list into array
        $crawl_list = file(
            $this->settings['wp_uploads_path'] .
            '/WP-STATIC-MODIFIED-CRAWL-LIST.txt'
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
        } else {
            // TODO: clone vs link to array
            $modified_crawl_list = $crawl_list;
        }

        // apply inclusions
        if ( isset( $this->settings['additionalUrls'] ) ) {
            $inclusions = explode(
                "\n",
                str_replace( "\r", '', $this->settings['additionalUrls'] )
            );

            foreach ( $inclusions as $inclusion ) {
                $inclusion = trim( $inclusion );
                $inclusion = ltrim( $inclusion, '/' );
                $inclusion = $this->settings['wp_site_url'] . $inclusion;

                $modified_crawl_list[] = $inclusion;
            }
        }

        // remove duplicates
        $modified_crawl_list = array_unique( $modified_crawl_list );

        $str = implode( PHP_EOL, $modified_crawl_list );

        file_put_contents(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-FINAL-CRAWL-LIST.txt',
            $str
        );

        chmod(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-FINAL-CRAWL-LIST.txt',
            0664
        );
    }
}

