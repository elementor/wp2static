<?php
/*
    Crawler

    Crawls URLs in WordPressSite, saving them to StaticSite

*/

namespace WP2Static;

class Crawler {

    /**
     * @var resource | bool
     */
    private $ch;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var mixed[]
     */
    private $curl_options;

    /**
     * Crawler constructor
     */
    public function __construct() {
        $this->ch = curl_init();
        $this->request = new Request();

        $port_override = apply_filters(
            'wp2static_curl_port',
            null
        );

        // TODO: apply this filter when option is saved
        if ( $port_override ) {
            curl_setopt( $this->ch, CURLOPT_PORT, $port_override );
        }

        curl_setopt(
            $this->ch,
            CURLOPT_USERAGENT,
            apply_filters( 'wp2static_curl_user_agent', 'WP2Static.com' )
        );

        $auth_user = CoreOptions::getValue( 'basicAuthUser' );
        $auth_password = CoreOptions::getValue( 'basicAuthPassword' );

        if ( $auth_user || $auth_password ) {
            curl_setopt(
                $this->ch,
                CURLOPT_USERPWD,
                $auth_user . ':' . $auth_password
            );
        }
    }

    /**
     * Crawls URLs in WordPressSite, saving them to StaticSite
     */
    public function crawlSite( string $static_site_path ) : void {
        $crawled = 0;
        $cache_hits = 0;

        \WP2Static\WsLog::l( 'Starting to crawl detected URLs.' );

        // TODO: use some Iterable or other performance optimisation here
        // to help reduce resources for large URL sites
        foreach ( CrawlQueue::getCrawlableURLs() as $url ) {
            $url = new URL( SiteInfo::getURL( 'site' ) . $url );

            // if not already cached
            if ( ! CoreOptions::getValue( 'dontUseCrawlCaching' ) ) {
                if ( CrawlCache::getUrl( $url->get() ) ) {
                    $cache_hits++;

                    continue;
                }
            }

            $crawled_contents = $this->crawlURL( $url );

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

        \WP2Static\WsLog::l(
            "Crawling complete. $crawled crawled, $cache_hits skipped (cached)."
        );

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
        $handle = $this->ch;

        if ( ! is_resource( $handle ) ) {
            return '';
        }

        $response = $this->request->getURL( $url->get(), $handle );

        $crawled_contents = $response['body'];

        return $crawled_contents;
    }
}

