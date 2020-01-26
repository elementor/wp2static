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
     *
     */
    public static function detectURLs() : int {
        // do detection 
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
        // if ( ExportSettings::get('detectAttachments') ) {
        //     $arrays_to_merge[] = DetectAttachmentURLs::detect();
        // }

        if ( ExportSettings::get('detectPosts') ) {
            $permalink_structure = get_option( 'permalink_structure' );
            $arrays_to_merge[] = DetectPostURLs::detect( SiteInfo::getPermalinks() );
        }

        if ( ExportSettings::get('detectPages') ) {
            $arrays_to_merge[] = DetectPageURLs::detect();
        }

        if ( ExportSettings::get('detectCustomPostTypes') ) {
            $arrays_to_merge[] = DetectCustomPostTypeURLs::detect();
        }

        if ( ExportSettings::get('detectUploads') ) {
            $arrays_to_merge[] =
                FilesHelper::getListOfLocalFilesByDir( SiteInfo::getPath( 'uploads' ) );
        }

        if ( ExportSettings::get('detectParentTheme') ) {
            $arrays_to_merge[] = DetectThemeAssets::detect( 'parent' );
        }

        if ( ExportSettings::get('detectChildTheme') ) {
            $arrays_to_merge[] = DetectThemeAssets::detect( 'child' );
        }

        if ( ExportSettings::get('detectPluginAssets') ) {
            $arrays_to_merge[] = DetectPluginAssets::detect();
        }

        if ( ExportSettings::get('detectWPIncludesAssets') ) {
            $arrays_to_merge[] = DetectWPIncludesAssets::detect();
        }

        if ( ExportSettings::get('detectVendorCacheDirs') ) {
            $arrays_to_merge[] =
                DetectVendorFiles::detect( SiteInfo::getURL( 'site' ) );
        }

        if ( ExportSettings::get('detectPostPagination') ) {
            $arrays_to_merge[] = DetectPostsPaginationURLs::detect();
        }

        if ( ExportSettings::get('detectArchives') ) {
            $arrays_to_merge[] =
                DetectArchiveURLs::detect( SiteInfo::getUrl( 'site' ) );
        }

        if ( ExportSettings::get('detectCategoryPagination') ) {
            $arrays_to_merge[] =
                DetectCategoryPaginationURLs::detect(
                    SiteInfo::getUrl( 'site' )
                );
        }

        $url_queue = call_user_func_array( 'array_merge', $arrays_to_merge );

        $url_queue = FilesHelper::cleanDetectedURLs( $url_queue );

        error_log(count( $url_queue ));

        $url_queue = apply_filters(
            'wp2static_modify_initial_crawl_list',
            $url_queue
        );

        error_log(count( $url_queue ));

        $unique_urls = array_unique( $url_queue );

        // truncate before adding
        // TODO: use inert unique instead here, allowing to skip heavy detection between runs without blowing away previously detected URLs...
        CrawlQueue::truncate();

        CrawlQueue::addUrls( $unique_urls );

        // return total detected
        return (string) count( $unique_urls );
        
        return count($urls);
    }
}

