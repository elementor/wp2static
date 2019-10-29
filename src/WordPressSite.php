<?php
/*
    WordPressSite

    WP2Static's representation of the WordPress site we are running in

*/

namespace WP2Static;

class WordPressSite {

    /**
     * WordPressSite constructor
     *
     */
    public function __construct() {
    }

    /**
     * Detect URLs within site
     *
     */
    public function detectURLs() {
       // do detection 
    }

    /**
     * Get URLs
     *
     * We don't store URLs within class, for performance
     *
     */
    public function getURLs() : array {
        $urls = CrawlQueue::getCrawlableURLs();

        return $urls;
    }

    /**
     * Create  dir
     *
     * @param string $path static site directory
     * @throws WP2StaticException
     */
    private function create_directory( $path ) : string {
        if ( is_dir( $path ) ) {
            return $path;
        }

        if ( ! mkdir( $path ) ) {
            $err = "Couldn't create archive directory:" . $path;
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        return $path;
    }
}

