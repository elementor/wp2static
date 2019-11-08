<?php
/*
    WordPressSite

    WP2Static's representation of the WordPress site we are running in

*/

namespace WP2Static;

class WordPressSite {

    /**
     * WordPressSite constructor
     *
     */
    public function __construct() {
    }

    /**
     * Detect URLs within site
     *
     */
    public function detectURLs() : int {
        // load detection options

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

        $url_queue = apply_filters(
            'wp2static_modify_initial_crawl_list',
            $url_queue
        );

        $unique_urls = array_unique( $url_queue );

        // truncate before adding
        CrawlQueue::truncate();

        CrawlQueue::addUrls( $unique_urls );

        // return total detected
        return (string) count( $unique_urls );
        
        return count($urls);
    }

    /**
     * Get URLs
     *
     * We don't store URLs within class, for performance
     *
     */
    public function getURLs() : array {
        $urls = CrawlQueue::getCrawlableURLs();

        return $urls;
    }

    /**
     * Clear detected URLs
     *
     * Reset the CrawlQueue
     *
     */
    public function clearDetectedURLs() : bool {
        CrawlQueue::truncate();

        return true;
    }

    /**
     * Clear CrawlCache
     *
     * Removes all URLs from the CrawlCache
     *
     */
    public function clearCrawlCache() : bool {
        CrawlCache::clear();

        return true;
    }

    /**
     * Create  dir
     *
     * @param string $path static site directory
     * @throws WP2StaticException
     */
    private function create_directory( $path ) : string {
        if ( is_dir( $path ) ) {
            return $path;
        }

        if ( ! mkdir( $path ) ) {
            $err = "Couldn't create archive directory:" . $path;
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        return $path;
    }
}

