<?php

namespace WP2Static;

use Exception;

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
        // TODO: filter here for add-on generated files
        $files_to_clean = array(
            'FILES-TO-DEPLOY.txt',
            'EXPORT-LOG.txt',
            'FINAL-CRAWL-LIST.txt',
            'GITLAB-FILES-IN-REPO.txt',
        );

        foreach ( $files_to_clean as $file_to_clean ) {
            if ( file_exists(
                SiteInfo::getPath( 'uploads' ) .
                    'wp2static-working-files/' . $file_to_clean
            ) ) {
                unlink(
                    SiteInfo::getPath( 'uploads' ) . '/' .
                        'wp2static-working-files/' . $file_to_clean
                );
            }
        }
    }

    public function cleanup_working_files() {
        // TODO: filter here for add-on generated files
        $files_to_clean = array(
            'FILES-TO-DEPLOY.txt',
            'FINAL-CRAWL-LIST.txt',
            'GITLAB-FILES-IN-REPO.txt',
        );

        foreach ( $files_to_clean as $file_to_clean ) {
            if ( file_exists(
                SiteInfo::getPath( 'uploads' ) .
                    'wp2static-working-files/' . $file_to_clean
            ) ) {
                unlink(
                    SiteInfo::getPath( 'uploads' ) .
                        'wp2static-working-files/' . $file_to_clean
                );
            }
        }
    }

    public function cleanup_leftover_archives() {
        $files_in_uploads_dir = scandir( SiteInfo::getPath( 'uploads' ) );

        if ( ! $files_in_uploads_dir ) {
            return;
        }

        $leftover_files =
            preg_grep(
                '/^([^.])/',
                $files_in_uploads_dir
            );

        foreach ( $leftover_files as $filename ) {
            // Note: cleanup legacy export dirs
            if (
                strpos( $filename, 'wp-static-html-output-' ) !== false ||
                strpos( $filename, 'wp2static-exported-site' ) !== false
            ) {
                $deletion_target = SiteInfo::getPath( 'uploads' ) .
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
            SiteInfo::getPath( 'uploads' ) .
                'wp2static-working-files/INITIAL-CRAWL-LIST.txt',
            SiteInfo::getPath( 'uploads' ) .
                'wp2static-working-files/MODIFIED-CRAWL-LIST.txt'
        );

        chmod(
            SiteInfo::getPath( 'uploads' ) .
                'wp2static-working-files/MODIFIED-CRAWL-LIST.txt',
            0664
        );

        // if no excludes or includes, just copy to new target
        if ( ! isset( $this->settings['excludeURLs'] ) &&
            ! isset( $this->settings['additionalUrls'] ) ) {
            copy(
                SiteInfo::getPath( 'uploads' ) .
                    'wp2static-working-files/INITIAL-CRAWL-LIST.txt',
                SiteInfo::getPath( 'uploads' ) .
                    'wp2static-working-files/FINAL-CRAWL-LIST.txt'
            );

            return;
        }

        $modified_crawl_list = array();

        // load crawl list into array
        $crawl_list = file(
            SiteInfo::getPath( 'uploads' ) .
            'wp2static-working-files/MODIFIED-CRAWL-LIST.txt'
        );

        if ( ! $crawl_list ) {
            $err = 'Unable to load crawl list';
            WsLog::l( $err );
            throw new Exception( $err );
        }

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
                            $msg = 'Excluding ' . $url_to_crawl .
                                ' because of rule ' . $exclusion;
                            WsLog::l( $msg );

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
            SiteInfo::getPath( 'uploads' ) .
                'wp2static-working-files/FINAL-CRAWL-LIST.txt',
            $str
        );

        chmod(
            SiteInfo::getPath( 'uploads' ) .
                'wp2static-working-files/FINAL-CRAWL-LIST.txt',
            0664
        );
    }
}

