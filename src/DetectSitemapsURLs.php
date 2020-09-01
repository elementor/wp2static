<?php

namespace WP2Static;

use vipnytt\SitemapParser;
use vipnytt\SitemapParser\Exceptions\SitemapParserException;


class DetectSiemapsURLs {

    /**
     * Detect Authors URLs
     *
     * @return string[] list of URLs
     */
    public static function detect( string $wp_site_url ) : array {

        try {
            $parser = new SitemapParser('WP2StaticAgent', ['strict' => false]);
            $parser->parse('https://www.ebavs.net/urllist.txt');
            foreach ($parser->getSitemaps() as $url => $tags) {
                echo $url . '<br>';
            }
            foreach ($parser->getURLs() as $url => $tags) {
                echo $url . '<br>';
            }
        } catch (SitemapParserException $e) {
            echo $e->getMessage();
        }

    }
}
