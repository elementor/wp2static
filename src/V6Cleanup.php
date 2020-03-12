<?php

namespace WP2Static;

class V6Cleanup {
    public static function cleanup() : void {
        global $wpdb;

        $deleted_v6_options = delete_option( 'wp2static-options' );

        if ( $deleted_v6_options ) {
            WsLog::l( 'Deleted Version 6 options from DB' );
        }

        $v6_txt_files = [
            'WP-STATIC-2ND-CRAWL-LIST.txt',
            'WP-STATIC-CRAWLED-LINKS.txt',
            'WP-STATIC-DISCOVERED-URLS-LOG.txt',
            'WP-STATIC-DISCOVERED-URLS-TOTAL.txt',
            'WP-STATIC-DISCOVERED-URLS.txt',
            'WP-STATIC-FINAL-2ND-CRAWL-LIST.txt',
            'WP-STATIC-FINAL-CRAWL-LIST.txt',
            'WP-STATIC-INITIAL-CRAWL-LIST.txt',
            'WP-STATIC-INITIAL-CRAWL-TOTAL.txt',
            'WP-STATIC-MODIFIED-CRAWL-LIST.txt',
            'WP-STATIC-PROGRESS.txt',
            'WP2STATIC-CURRENT-ARCHIVE.txt',
        ];

        foreach ( $v6_txt_files as $txt_file ) {
            if ( is_file( SiteInfo::getPath( 'uploads' ) . $txt_file ) ) {
                $deleted_file = unlink( SiteInfo::getPath( 'uploads' ) . $txt_file );

                if ( $deleted_file ) {
                    WsLog::l( 'Deleted Version 6 text file: ' . $txt_file );
                }
            }
        }

        $v6_zip_files = glob( SiteInfo::getPath( 'uploads' ) . 'wp-static*.zip' );

        if ( is_array( $v6_zip_files ) ) {
            foreach ( $v6_zip_files as $v6_zip_file ) {
                $deleted_zip = unlink( $v6_zip_file );

                if ( $deleted_zip ) {
                    WsLog::l( 'Deleted Version 6 zip file: ' . $v6_zip_file );
                }
            }
        }

        $v6_archives = glob( SiteInfo::getPath( 'uploads' ) . 'wp-static*' );

        if ( is_array( $v6_archives ) ) {
            foreach ( $v6_archives as $v6_archive ) {
                if ( is_dir( $v6_archive ) ) {
                    WsLog::l( 'Deleting Version 6 archive: ' . $v6_archive );
                    FilesHelper::delete_dir_with_files( $v6_archive );
                }
            }
        }
    }
}

