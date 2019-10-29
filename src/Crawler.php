<?php
/*
    Crawler

    Crawls URLs in WordPressSite, saving them to StaticSite 

*/

namespace WP2Static;

class Crawler {

    /**
     * StaticSite constructor
     *
     * @param string $path path to static site directory
     */
    public function __construct() {
        
    }

    /**
     * Crawls URLs in WordPressSite, saving them to StaticSite 
     *
     */
    public function crawlSite(
        WordPressSite $wordpress_site, StaticSite $static_site
    ) {
        foreach( $wordpress_site->getURLs() as $url ) {
            // if not already cached
            $crawled_url = $this->crawlURL($url);

            if ( $crawled_url ) {
                list( $path, $contents ) = $crawled_url;

                $static_site->add( $path, $contents );
            }
        }
    }

    /**
     * Crawls a URL 
     *
     */
    public function crawlURL( URL $url ) {
    
        foreach( $wordpress_site->getURLs() as $url ) {
            // if not already cached
            $crawled_url = $this->crawlURL($url);

            if ( $crawled_url ) {
                list( $path, $contents ) = $crawled_url;

                $static_site->add( $path, $contents );
            }
        }
    }
}

