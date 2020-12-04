<?php

namespace WP2Static;

use vipnytt\SitemapParser;
use vipnytt\SitemapParser\Exceptions\SitemapParserException;


class DetectSitemapsURLs {

    /**
     * Detect Authors URLs
     *
     * @return string[] list of URLs
     * @throws WP2StaticException
     */
    public static function detect( string $wp_site_url ) : array {

        $sitemaps_urls = [];
        $parser = new SitemapParser( 'WP2Static.com', [ 'strict' => false ] );
        $request = new Request();
        $response = $request->getResponseCode( $wp_site_url . 'robots.txt' );
        $robots_exits = $response === 200;

        try {
            $sitemaps = [];

            // if robots exists, parse for possible sitemaps
            if ( $robots_exits === true ) {
                $parser->parseRecursive( $wp_site_url . 'robots.txt' );
                $sitemaps = $parser->getSitemaps();
            }

            // if no sitemaps add known sitemaps
            if ( $sitemaps === [] ) {
                $sitemaps = [
                    // we're assigning empty arrays to match sitemaps library
                    $wp_site_url . 'sitemap.xml' => [], // normal sitemap
                    $wp_site_url . 'sitemap_index.xml' => [], // yoast sitemap
                    $wp_site_url . 'wp_sitemap.xml' => [], // wp 5.5 sitemap
                ];
            }

            // TODO: a more elegant mapping
            foreach ( array_keys( $sitemaps ) as $sitemap ) {
                if ( ! is_string( $sitemap ) ) {
                    continue;
                }

                $response = $request->getResponseCode( $sitemap );

                if ( $response === 200 ) {
                    $parser->parse( $sitemap );

                    $sitemaps_urls[] = '/' . str_replace(
                        $wp_site_url,
                        '',
                        $sitemap
                    );

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
        } catch ( SitemapParserException $e ) {
            WsLog::l( $e->getMessage() );
            throw new WP2StaticException( $e->getMessage(), 0, $e );
        }

        return $sitemaps_urls;
    }
}
