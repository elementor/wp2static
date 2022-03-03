<?php

namespace WP2Static;

use WP2StaticGuzzleHttp\Client;
use WP2StaticGuzzleHttp\Psr7\Request;
use WP2StaticGuzzleHttp\Psr7\Response;

class DetectSitemapsURLs {

    /**
     * Detect Authors URLs
     *
     * @return string[] list of URLs
     * @throws WP2StaticException
     */
    public static function detect( string $wp_site_url ) : array {
        $sitemaps_urls = [];

        $opts = [
            'http_errors' => false,
            'verify' => false,
        ];

        $auth_user = CoreOptions::getValue( 'basicAuthUser' );

        if ( $auth_user ) {
            $auth_password = CoreOptions::getValue( 'basicAuthPassword' );

            if ( $auth_password ) {
                WsLog::l( 'Using basic auth credentials to crawl' );
                $opts['auth'] = [ $auth_user, $auth_password ];
            }
        }

        $parser = new SitemapParser(
            'WP2Static.com',
            [
                'guzzle' => $opts,
                'strict' => false,
            ]
        );

        $site_path = rtrim( SiteInfo::getURL( 'site' ), '/' );

        $port_override = apply_filters(
            'wp2static_curl_port',
            null
        );

        $base_uri = $site_path;

        if ( $port_override ) {
            $base_uri = "{$base_uri}:{$port_override}";
        }

        $client = new Client(
            [
                'verify' => false,
                'http_errors' => false,
                'allow_redirects' => [
                    'max' => 1,
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

        $headers = [];

        $auth_user = CoreOptions::getValue( 'basicAuthUser' );

        if ( $auth_user ) {
            $auth_password = CoreOptions::getValue( 'basicAuthPassword' );

            if ( $auth_password ) {
                $headers['auth'] = [ $auth_user, $auth_password ];
            }
        }

        $request = new Request( 'GET', $base_uri . '/robots.txt', $headers );

        $response = $client->send( $request );

        $robots_exists = $response->getStatusCode() === 200;

        try {
            $sitemaps = [];

            // if robots exists, parse for possible sitemaps
            if ( $robots_exists ) {
                $parser->parseRecursive( $wp_site_url . 'robots.txt' );
                $sitemaps = $parser->getSitemaps();
            }

            // if no sitemaps add known sitemaps
            if ( $sitemaps === [] ) {
                $sitemaps = [
                    // we're assigning empty arrays to match sitemaps library
                    'sitemap.xml' => [], // normal sitemap
                    'sitemap_index.xml' => [], // yoast sitemap
                    'wp-sitemap.xml' => [], // default WordPress sitemap
                ];
            }

            foreach ( array_keys( $sitemaps ) as $sitemap ) {
                if ( ! is_string( $sitemap ) ) {
                    continue;
                }

                $sitemap = '/' . str_replace(
                    $wp_site_url,
                    '',
                    $sitemap
                );

                $request = new Request( 'GET', $base_uri . $sitemap, $headers );

                $response = $client->send( $request );

                $status_code = $response->getStatusCode();

                if ( $status_code === 200 ) {
                    $sitemap_urls[] = $sitemap;

                    $parser->parse( $wp_site_url . $sitemap );

                    $extract_sitemaps = $parser->getSitemaps();

                    foreach ( $extract_sitemaps as $url => $tags ) {
                        $sitemaps_urls[] = '/' . str_replace(
                            $wp_site_url,
                            '',
                            $url
                        );
                    }
                }
            }
        } catch ( WP2StaticException $e ) {
            WsLog::l( $e->getMessage() );
            throw new WP2StaticException( $e->getMessage(), 0, $e );
        }

        return $sitemaps_urls;
    }
}
