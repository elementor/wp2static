<?php
/*
    Crawler

    Crawls URLs in WordPressSite, saving them to StaticSite

*/

namespace WP2Static;

use WP2StaticGuzzleHttp\Client;
use WP2StaticGuzzleHttp\Psr7\Request;
use WP2StaticGuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use WP2StaticGuzzleHttp\Exception\RequestException;
use WP2StaticGuzzleHttp\Exception\TooManyRedirectsException;
use WP2StaticGuzzleHttp\Pool;

define( 'WP2STATIC_REDIRECT_CODES', [ 301, 302, 303, 307, 308 ] );

class Crawler {

    /**
     * @var Client
     */
    private $client;
    /**
     * @var string
     */
    private $site_path;

    /**
     * @var integer
     */
    private $crawled = 0;

    /**
     * @var integer
     */
    private $cache_hits = 0;

    /**
     * Crawler constructor
     */
    public function __construct() {
        $this->site_path = rtrim( SiteInfo::getURL( 'site' ), '/' );

        $port_override = apply_filters(
            'wp2static_curl_port',
            null
        );

        $base_uri = $this->site_path;

        if ( $port_override ) {
            $base_uri = "{$base_uri}:{$port_override}";
        }

        $this->client = new Client(
            [
                'base_uri' => $base_uri,
                'verify' => false,
                'http_errors' => false,
                'allow_redirects' => [
                    'max' => 2,
                    // required to get effective_url
                    'track_redirects' => true,
                ],
                'connect_timeout'  => 0,
                'timeout' => 600,
                'headers' => [
                    'User-Agent' => apply_filters(
                        'wp2static_curl_user_agent',
                        'WP2Static.com',
                    ),
                ],
            ]
        );
    }

    public static function wp2staticCrawl( string $static_site_path, string $crawler_slug ) : void {
        if ( 'wp2static' === $crawler_slug ) {
            $crawler = new Crawler();
            $crawler->crawlSite( $static_site_path );
        }
    }

    /**
     * Crawls URLs in WordPressSite, saving them to StaticSite
     */
    public function crawlSite( string $static_site_path ) : void {
        WsLog::l( 'Starting to crawl detected URLs.' );

        $site_host = parse_url( $this->site_path, PHP_URL_HOST );
        $site_port = parse_url( $this->site_path, PHP_URL_PORT );
        $site_host = $site_port ? $site_host . ":$site_port" : $site_host;
        $site_urls = [ "http://$site_host", "https://$site_host" ];

        $use_crawl_cache = apply_filters(
            'wp2static_use_crawl_cache',
            CoreOptions::getValue( 'useCrawlCaching' )
        );

        WsLog::l( ( $use_crawl_cache ? 'Using' : 'Not using' ) . ' CrawlCache.' );

        // TODO: use some Iterable or other performance optimisation here
        // to help reduce resources for large URL sites

        /**
         * When you call method that executes database query in for loop
         * you are calling method and querying database for every loop iteration.
         * To avoid that you need to assing the result to a variable.
         */

        $crawlable_paths = CrawlQueue::getCrawlablePaths();
        $urls = [];

        foreach ( $crawlable_paths as $root_relative_path ) {
            $absolute_uri = new URL( $this->site_path . $root_relative_path );
            $urls[] = [
                'url' => $absolute_uri->get(),
                'path' => $root_relative_path,
            ];
        }

        $headers = [];

        $auth_user = CoreOptions::getValue( 'basicAuthUser' );

        if ( $auth_user ) {
            $auth_password = CoreOptions::getValue( 'basicAuthPassword' );

            if ( $auth_password ) {
                $headers['auth'] = [ $auth_user, $auth_password ];
            }
        }

        $requests = function ( $urls ) use ( $headers ) {
            foreach ( $urls as $url ) {
                yield new Request( 'GET', $url['url'], $headers );
            }
        };

        $concurrency = intval( CoreOptions::getValue( 'crawlConcurrency' ) );
        $concurrency = apply_filters( 'wp2static_crawl_concurrency', $concurrency );

        $pool = new Pool(
            $this->client,
            $requests( $urls ),
            [
                'concurrency' => $concurrency,
                'fulfilled' => function ( Response $response, $index ) use (
                    $urls, $use_crawl_cache, $site_urls
                ) {
                    $root_relative_path = $urls[ $index ]['path'];
                    $crawled_contents = (string) $response->getBody();
                    $status_code = $response->getStatusCode();

                    if ( $status_code === 404 ) {
                        WsLog::l( '404 for URL ' . $root_relative_path );
                        CrawlCache::rmUrl( $root_relative_path );
                        $crawled_contents = null;
                    } elseif ( in_array( $status_code, WP2STATIC_REDIRECT_CODES ) ) {
                        $crawled_contents = null;
                    }

                    $redirect_to = null;

                    if ( in_array( $status_code, WP2STATIC_REDIRECT_CODES ) ) {
                        $effective_url = $urls[ $index ]['url'];

                        // returns as string
                        $redirect_history =
                            $response->getHeaderLine( 'X-Guzzle-Redirect-History' );

                        if ( $redirect_history ) {
                            $redirects = explode( ', ', $redirect_history );
                            $effective_url = end( $redirects );
                        }

                        $redirect_to =
                            (string) str_replace( $site_urls, '', $effective_url );
                        $page_hash = md5( $status_code . $redirect_to );
                    } elseif ( ! is_null( $crawled_contents ) ) {
                        $page_hash = md5( $crawled_contents );
                    } else {
                        $page_hash = md5( (string) $status_code );
                    }

                    if ( $use_crawl_cache ) {
                        // if not already cached
                        if ( CrawlCache::getUrl( $root_relative_path, $page_hash ) ) {
                            $this->cache_hits++;
                        }
                    }

                    $this->crawled++;

                    if ( $crawled_contents ) {
                        // do some magic here - naive: if URL ends in /, save to /index.html
                        // TODO: will need love for example, XML files
                        // check content type, serve .xml/rss, etc instead
                        if ( mb_substr( $root_relative_path, -1 ) === '/' ) {
                            StaticSite::add(
                                $root_relative_path . 'index.html',
                                $crawled_contents
                            );
                        } else {
                            StaticSite::add( $root_relative_path, $crawled_contents );
                        }
                    }

                    CrawlCache::addUrl(
                        $root_relative_path,
                        $page_hash,
                        $status_code,
                        $redirect_to
                    );

                    // incrementally log crawl progress
                    if ( $this->crawled % 300 === 0 ) {
                        $notice = "Crawling progress: $this->crawled crawled," .
                                  " $this->cache_hits skipped (cached).";
                        WsLog::l( $notice );
                    }
                },
                'rejected' => function ( RequestException $reason, $index ) use ( $urls ) {
                    $root_relative_path = $urls[ $index ]['path'];
                    WsLog::l( 'Failed ' . $root_relative_path );
                },
            ]
        );

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();

        WsLog::l(
            "Crawling complete. $this->crawled crawled, $this->cache_hits skipped (cached)."
        );

        $args = [
            'staticSitePath' => $static_site_path,
            'crawled' => $this->crawled,
            'cache_hits' => $this->cache_hits,
        ];

        do_action( 'wp2static_crawling_complete', $args );
    }

    /**
     * @deprecated
     *
     * Crawls a string of full URL within WordPressSite
     *
     * @return ResponseInterface|null response object
     */
    public function crawlURL( string $url ) : ?ResponseInterface {
        WsLog::w( 'WP2Static Crawler::crawlURL is deprecated.' );

        $headers = [];
        $response = null;

        $auth_user = CoreOptions::getValue( 'basicAuthUser' );

        if ( $auth_user ) {
            $auth_password = CoreOptions::getValue( 'basicAuthPassword' );

            if ( $auth_password ) {
                $headers['auth'] = [ $auth_user, $auth_password ];
            }
        }

        $request = new Request( 'GET', $url, $headers );

        try {
            $response = $this->client->send( $request );
        } catch ( TooManyRedirectsException $e ) {
            WsLog::l( "Too many redirects from $url" );
        }

        return $response;
    }
}
