<?php
/*
    Crawler

    Crawls URLs in WordPressSite, saving them to StaticSite 

*/

namespace WP2Static;

class Crawler {

    private $ch;
    private $request;
    private $curl_options;

    /**
     * StaticSite constructor
     *
     * @param string $path path to static site directory
     */
    public function __construct() {
        $this->ch = curl_init();
        $this->request = new Request();
        $this->curl_options = [];

        $portOverride = apply_filters(
            'wp2static_curl_port',
            null);

        // TODO: apply this filter when option is saved
        if ( $portOverride ) {
            $this->curl_options[ CURLOPT_PORT ] = $portOverride;
        }
        
        $this->curl_options[ CURLOPT_USERAGENT ] = apply_filters(
            'wp2static_curl_user_agent',
            'WP2Static.com');

        if ( CoreOptions::getValue( 'useBasicAuth' ) ) {
            $this->curl_options[ CURLOPT_USERPWD ] =
                CoreOptions::getValue( 'basicAuthUser' ) . ':' .
                CoreOptions::getValue( 'basicAuthPassword' );
        }
    }

    /**
     * Crawls URLs in WordPressSite, saving them to StaticSite 
     *
     */
    public function crawlSite( string $static_site_path, bool $progress = false ) {
        $crawled = 0;
        $cache_hits = 0;


        if ( $progress ) {
            $crawl_queue_total = CrawlQueue::getTotal();

            $progress_indicator =
                \WP_CLI\Utils\make_progress_bar(
                    'Crawling site',
                    $crawl_queue_total);
        }


        // TODO: use some Iterable or other performance optimisation here
        //       to help reduce resources for large URL sites
        foreach( CrawlQueue::getCrawlableURLs() as $url ) {
            $url = new URL( SiteInfo::getURL('site') . $url );

            // if not already cached
            if ( ! CoreOptions::getValue( 'dontUseCrawlCaching' ) ) {
                if ( CrawlCache::getUrl( $url->get() ) ) {
                    $cache_hits++;

                    if ( $progress ) {
                        $progress_indicator->tick();
                    }

                    continue;
                }
            }

            $crawled_contents = $this->crawlURL( $url );

            if ( $progress ) {
                $progress_indicator->tick();
            }

            $crawled++;

            $path_in_static_site = str_replace(
                SiteInfo::getUrl( 'site'),
                '',
                $url->get());

            // do some magic here - naive: if URL ends in /, save to /index.html
            // TODO: will need love for example, XML files
            if ( mb_substr( $path_in_static_site, -1 ) === '/' ) {
                $path_in_static_site .= 'index.html';
            }

            if ( $crawled_contents ) {
                StaticSite::add( $path_in_static_site, $crawled_contents );
            }

            if ( ! CoreOptions::getValue( 'dontUseCrawlCaching' ) ) {
                CrawlCache::addUrl( $url->get() );
            }
        }

        if ( $progress ) {
            $progress_indicator->finish();
        }

        error_log('finished crawling all detected URLs');
        error_log(" Crawled: $crawled");
        error_log(" Skipped (cache-hit): $cache_hits");

        $args = [
            'staticSitePath' => $static_site_path,
            'crawled' => $crawled,
            'cache_hits' => $cache_hits,
        ];

        do_action( 'wp2static_crawling_complete', $args );
    }

    /**
     * Crawls a string of full URL within WordPressSite
     *
     */
    public function crawlURL( URL $url ) : string {
        $response = $this->request->getURL(
            $url->get(),
            $this->ch,
            $this->curl_options);

        $crawled_contents = $response['body'];

        return $crawled_contents;
    }
}

