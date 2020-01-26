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

        if ( ExportSettings::get( 'crawlPort' ) ) {
            $this->curl_options[ CURLOPT_PORT ] =
                ExportSettings::get( 'crawlPort' );
        }

        if ( ExportSettings::get( 'crawlUserAgent' ) ) {
            $this->curl_options[ CURLOPT_USERAGENT ] =
                ExportSettings::get( 'crawlUserAgent' );
        }

        if ( ExportSettings::get( 'useBasicAuth' ) ) {
            $this->curl_options[ CURLOPT_USERPWD ] =
                ExportSettings::get( 'basicAuthUser' ) . ':' .
                ExportSettings::get( 'basicAuthPassword' );
        }
    }

    /**
     * Crawls URLs in WordPressSite, saving them to StaticSite 
     *
     */
    public function crawlSite(string $static_site_path) {
        // TODO: use some Iterable or other performance optimisation here
        //       to help reduce resources for large URL sites
        foreach( WordPressSite::getURLs() as $url ) {
            $url = new URL( SiteInfo::getURL('site') . $url );

            // if not already cached
            if ( ! ExportSettings::get( 'dontUseCrawlCaching' ) ) {
                if ( CrawlCache::getUrl( $url->get() ) ) {
                    continue;
                }
            }

            $crawled_contents = $this->crawlURL( $url );

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

            if ( ! ExportSettings::get( 'dontUseCrawlCaching' ) ) {
                CrawlCache::addUrl( $url->get() );
            }
        }

        error_log('finished crawling all detected URLs');

        $args = [
            'staticSitePath' => $static_site_path];

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

