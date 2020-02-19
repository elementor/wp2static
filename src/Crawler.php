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
     * Crawler constructor
     */
    public function __construct() {
        $this->ch = curl_init();
        $this->request = new Request();

        $portOverride = apply_filters(
            'wp2static_curl_port',
            null
        );

        // TODO: apply this filter when option is saved
        if ( $portOverride ) {
            curl_setopt( $this->ch, CURLOPT_PORT, $portOverride );
        }

        curl_setopt(
            $this->ch,
            CURLOPT_USERAGENT,
            apply_filters( 'wp2static_curl_user_agent', 'WP2Static.com' )
        );

        if ( CoreOptions::getValue( 'useBasicAuth' ) ) {
            curl_setopt(
                $this->ch,
                CURLOPT_USERPWD,
                CoreOptions::getValue( 'basicAuthUser' ) . ':' .
                CoreOptions::getValue( 'basicAuthPassword' )
            );
        }
    }

    /**
     * Crawls URLs in WordPressSite, saving them to StaticSite
     */
    public function crawlSite( string $static_site_path, bool $progress = false ) : void {
        $crawled = 0;
        $cache_hits = 0;
        $progress_indicator = false;

        if ( $progress ) {
            $crawl_queue_total = CrawlQueue::getTotal();

            $progress_indicator =
                \WP_CLI\Utils\make_progress_bar(
                    'Crawling site',
                    $crawl_queue_total
                );
        }

        // TODO: use some Iterable or other performance optimisation here
        // to help reduce resources for large URL sites
        foreach ( CrawlQueue::getCrawlableURLs() as $url ) {
            $url = new URL( SiteInfo::getURL( 'site' ) . $url );

            // if not already cached
            if ( ! CoreOptions::getValue( 'dontUseCrawlCaching' ) ) {
                if ( CrawlCache::getUrl( $url->get() ) ) {
                    $cache_hits++;

                    if ( $progress_indicator ) {
                        $progress_indicator->tick();
                    }

                    continue;
                }
            }

            $crawled_contents = $this->crawlURL( $url );

            if ( $progress_indicator ) {
                $progress_indicator->tick();
            }

            $crawled++;

            $path_in_static_site = str_replace(
                SiteInfo::getUrl( 'site' ),
                '',
                $url->get()
            );

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

        if ( $progress_indicator ) {
            $progress_indicator->finish();
        }

        WsLog::l( 'Finished crawling all detected URLs' );
        WsLog::l( "Crawled: $crawled" );
        WsLog::l( "Skipped (cache-hit): $cache_hits" );

        $args = [
            'staticSitePath' => $static_site_path,
            'crawled' => $crawled,
            'cache_hits' => $cache_hits,
        ];

        do_action( 'wp2static_crawling_complete', $args );
    }

    /**
     * Crawls a string of full URL within WordPressSite
     */
    public function crawlURL( URL $url ) : string {
        $response = $this->request->getURL( $url->get(), $this->ch );

        $crawled_contents = $response['body'];

        return $crawled_contents;
    }
}

