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
        // TODO: use some Iterable or other performance optimisation here
        //       to help reduce resources for large URL sites
        foreach( $wordpress_site->getURLs() as $url ) {
            // if not already cached
            $crawled_contents = $this->crawlURL( $url );

            $path_in_static_site = str_replace(
                SiteInfo::getUrl( 'site'),
                '',
                $url);

            // do some magic here - naive: if URL ends in /, save to /index.html
            // TODO: will need love for example, XML files
            if ( mb_substr( $path_in_static_site, -1 ) === '/' ) {
                $path_in_static_site .= 'index.html';
            }

            if ( $crawled_contents ) {
                $static_site->add( $path_in_static_site, $crawled_contents );
            }
        }
    }

    /**
     * Crawls a string of full URL within WordPressSite
     *
     */
    public function crawlURL( string $url ) : string {
        $crawled_contents = 'test contents';

        return $crawled_contents;
    }
}

