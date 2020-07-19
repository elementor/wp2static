<?php
/*
    URLDetector

    Detects URLs from WordPress DB, filesystem and user input

    Users can control detection levels

    Saves URLs to CrawlQueue

*/

namespace WP2Static;

class URLDetector {

    /**
     * Detect URLs within site
     */
    public static function detectURLs() : string {
        WsLog::l( 'Starting to detect WordPress site URLs.' );

        $arrays_to_merge = [];

        $arrays_to_merge[] = [
            '/',
            '/ads.txt',
            '/favicon.ico',
            '/robots.txt',
            '/sitemap.xml',
        ];

        if ( CoreOptions::getValue( 'detectPosts' ) ) {
            $permalink_structure = get_option( 'permalink_structure' );
            $arrays_to_merge[] = DetectPostURLs::detect( SiteInfo::getPermalinks() );
        }

        if ( CoreOptions::getValue( 'detectPages' ) ) {
            $arrays_to_merge[] = DetectPageURLs::detect();
        }

        if ( CoreOptions::getValue( 'detectCustomPostTypes' ) ) {
            $arrays_to_merge[] = DetectCustomPostTypeURLs::detect();
        }

        // TODO: bring in detectVendorFiles() from V6 (Yoast sitemaps,
        // probably drop custom permalinks plugin support now)

        $detect_archives = apply_filters( 'wp2static_detect_archives', 1 );

        if ( $detect_archives ) {
            $arrays_to_merge[] = DetectArchiveURLs::detect( SiteInfo::getUrl( 'site' ) );
        }

        $detect_categories = apply_filters( 'wp2static_detect_categories', 1 );

        if ( $detect_categories ) {
            $arrays_to_merge[] = DetectCategoryURLs::detect( SiteInfo::getUrl( 'site' ) );
        }

        $url_queue = call_user_func_array( 'array_merge', $arrays_to_merge );

        $url_queue = FilesHelper::cleanDetectedURLs( $url_queue );

        $url_queue = apply_filters(
            'wp2static_modify_initial_crawl_list',
            $url_queue
        );

        $unique_urls = array_unique( $url_queue );

        // No longer truncate before adding
        // addUrls is now doing INSERT IGNORE based on URL hash to be
        // additive and not error on duplicate

        CrawlQueue::addUrls( $unique_urls );

        $total_detected = (string) count( $unique_urls );

        WsLog::l(
            "Detection complete. $total_detected URLs added to Crawl Queue."
        );

        return $total_detected;
    }
}

