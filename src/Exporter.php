<?php

namespace WP2Static;

class Exporter extends Base {

    public function __construct() {
        $this->loadSettings(
            array(
                'wpenv',
                'crawling',
                'advanced',
            )
        );
    }

    public function pre_export_cleanup() {
        $files_to_clean = array(
            '2ND-CRAWL-LIST.txt',
            'DISCOVERED-URLS-LOG.txt',
            'DISCOVERED-URLS.txt',
            'FILES-TO-DEPLOY.txt',
            'EXPORT-LOG.txt',
            'FINAL-2ND-CRAWL-LIST.txt',
            'FINAL-CRAWL-LIST.txt',
            'GITLAB-FILES-IN-REPO.txt',
        );

        foreach ( $files_to_clean as $file_to_clean ) {
            if ( file_exists(
                $this->settings['wp_uploads_path'] .
                    '/wp2static-working-files/' . $file_to_clean
            ) ) {
                unlink(
                    $this->settings['wp_uploads_path'] . '/' .
                        '/wp2static-working-files/' . $file_to_clean
                );
            }
        }
    }

    public function cleanup_working_files() {
        $files_to_clean = array(
            '2ND-CRAWL-LIST.txt',
            'DISCOVERED-URLS.txt',
            'FILES-TO-DEPLOY.txt',
            'FINAL-2ND-CRAWL-LIST.txt',
            'FINAL-CRAWL-LIST.txt',
            'GITLAB-FILES-IN-REPO.txt',
        );

        foreach ( $files_to_clean as $file_to_clean ) {
            if ( file_exists(
                $this->settings['wp_uploads_path'] . '/wp2static-working-files/' . $file_to_clean
            ) ) {
                unlink(
                    $this->settings['wp_uploads_path'] . '/wp2static-working-files/' . $file_to_clean
                );
            }
        }
    }

    public function cleanup_leftover_archives() {
        $leftover_files =
            preg_grep(
                '/^([^.])/',
                scandir( $this->settings['wp_uploads_path'] )
            );

        foreach ( $leftover_files as $filename ) {
            // Note: cleanup legacy export dirs
            if (
                strpos( $filename, 'wp-static-html-output-' ) !== false ||
                strpos( $filename, 'wp2static-exported-site' ) !== false
            ) {
                $deletion_target = $this->settings['wp_uploads_path'] .
                    '/' . $filename;
                if ( is_dir( $deletion_target ) ) {
                    FilesHelper::delete_dir_with_files(
                        $deletion_target
                    );
                } else {
                    unlink( $deletion_target );
                }
            }
        }
    }

    public function generateModifiedFileList() {
        // preserve the initial crawl list, to be used in debugging + more
        copy(
            $this->settings['wp_uploads_path'] .
                '/wp2static-working-files/INITIAL-CRAWL-LIST.txt',
            $this->settings['wp_uploads_path'] .
                '/wp2static-working-files/MODIFIED-CRAWL-LIST.txt'
        );

        chmod(
            $this->settings['wp_uploads_path'] .
                '/wp2static-working-files/MODIFIED-CRAWL-LIST.txt',
            0664
        );

        // if no excludes or includes, just copy to new target
        if ( ! isset( $this->settings['excludeURLs'] ) &&
            ! isset( $this->settings['additionalUrls'] ) ) {
            copy(
                $this->settings['wp_uploads_path'] .
                    '/wp2static-working-files/INITIAL-CRAWL-LIST.txt',
                $this->settings['wp_uploads_path'] .
                    '/wp2static-working-files/FINAL-CRAWL-LIST.txt'
            );

            return;
        }

        $modified_crawl_list = array();

        // load crawl list into array
        $crawl_list = file(
            $this->settings['wp_uploads_path'] .
            '/wp2static-working-files/MODIFIED-CRAWL-LIST.txt'
        );

        // applying exclusions before inclusions
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
                            $this->logAction(
                                'Excluding ' . $url_to_crawl .
                                ' because of rule ' . $exclusion
                            );

                            $match = true;
                        }
                    }

                    if ( ! $match ) {
                        $modified_crawl_list[] = $url_to_crawl;
                    }
                }
            }
        } else {
            $modified_crawl_list = $crawl_list;
        }

        if ( isset( $this->settings['additionalUrls'] ) ) {
            $inclusions = explode(
                "\n",
                str_replace( "\r", '', $this->settings['additionalUrls'] )
            );

            foreach ( $inclusions as $inclusion ) {
                $inclusion = trim( $inclusion );
                $inclusion = $inclusion;

                $modified_crawl_list[] = $inclusion;
            }
        }

        $modified_crawl_list = array_unique( $modified_crawl_list );

        $str = implode( PHP_EOL, $modified_crawl_list );

        file_put_contents(
            $this->settings['wp_uploads_path'] .
                '/wp2static-working-files/FINAL-CRAWL-LIST.txt',
            $str
        );

        chmod(
            $this->settings['wp_uploads_path'] .
                '/wp2static-working-files/FINAL-CRAWL-LIST.txt',
            0664
        );
    }

    public function logAction( $action ) {
        if ( ! isset( $this->settings['debug_mode'] ) ) {
            return;
        }

        require_once dirname( __FILE__ ) .
            '/../WP2Static/WsLog.php';
        WsLog::l( $action );
    }
}

