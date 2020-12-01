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

        // TODO: detect robots.txt, etc before adding
        $arrays_to_merge[] = [
            '/',
            '/robots.txt',
            '/favicon.ico',
            '/sitemap.xml',
        ];

        /*
            TODO: reimplement detection for URLs:
                'detectCommentPagination',
                'detectComments',
                'detectFeedURLs',

        // other options:

         - robots
         - favicon
         - sitemaps

        */

        if ( CoreOptions::getValue( 'detectPosts' ) ) {
            $arrays_to_merge[] = DetectPostURLs::detect();
        }

        if ( CoreOptions::getValue( 'detectPages' ) ) {
            $arrays_to_merge[] = DetectPageURLs::detect();
        }

        if ( CoreOptions::getValue( 'detectCustomPostTypes' ) ) {
            $arrays_to_merge[] = DetectCustomPostTypeURLs::detect();
        }

        if ( CoreOptions::getValue( 'detectUploads' ) ) {
            $arrays_to_merge[] =
                FilesHelper::getListOfLocalFilesByDir( SiteInfo::getPath( 'uploads' ) );
        }

        $detect_sitemaps = apply_filters( 'wp2static_detect_sitemaps', 1 );

        if ( $detect_sitemaps ) {
            $arrays_to_merge[] = DetectSitemapsURLs::detect( SiteInfo::getURL( 'site' ) );
        }

        $detect_parent_theme = apply_filters( 'wp2static_detect_parent_theme', 1 );

        if ( $detect_parent_theme ) {
            $arrays_to_merge[] = DetectThemeAssets::detect( 'parent' );
        }

        $detect_child_theme = apply_filters( 'wp2static_detect_child_theme', 1 );

        if ( $detect_child_theme ) {
            $arrays_to_merge[] = DetectThemeAssets::detect( 'child' );
        }

        $detect_plugin_assets = apply_filters( 'wp2static_detect_plugin_assets', 1 );

        if ( $detect_plugin_assets ) {
            $arrays_to_merge[] = DetectPluginAssets::detect();
        }

        $detect_wpinc_assets = apply_filters( 'wp2static_detect_wpinc_assets', 1 );

        if ( $detect_wpinc_assets ) {
            $arrays_to_merge[] = DetectWPIncludesAssets::detect();
        }

        $detect_vendor_cache = apply_filters( 'wp2static_detect_vendor_cache', 1 );

        if ( $detect_vendor_cache ) {
            $arrays_to_merge[] = DetectVendorFiles::detect( SiteInfo::getURL( 'site' ) );
        }

        $detect_posts_pagination = apply_filters( 'wp2static_detect_posts_pagination', 1 );

        if ( $detect_posts_pagination ) {
            $arrays_to_merge[] = DetectPostsPaginationURLs::detect();
        }

        $detect_archives = apply_filters( 'wp2static_detect_archives', 1 );

        if ( $detect_archives ) {
            $arrays_to_merge[] = DetectArchiveURLs::detect();
        }

        $detect_categories = apply_filters( 'wp2static_detect_categories', 1 );

        if ( $detect_categories ) {
            $arrays_to_merge[] = DetectCategoryURLs::detect();
        }

        $detect_category_pagination = apply_filters( 'wp2static_detect_category_pagination', 1 );

        if ( $detect_category_pagination ) {
            $arrays_to_merge[] = DetectCategoryPaginationURLs::detect();
        }

        $detect_authors = apply_filters( 'wp2static_detect_authors', 1 );

        if ( $detect_authors ) {
            $arrays_to_merge[] = DetectAuthorsURLs::detect();
        }

        $detect_authors_pagination = apply_filters( 'wp2static_detect_authors_pagination', 1 );

        if ( $detect_authors_pagination ) {
            $arrays_to_merge[] = DetectAuthorPaginationURLs::detect( SiteInfo::getUrl( 'site' ) );
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

