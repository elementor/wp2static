<?php
/*
    Generator

    Generates content from URLs in WordPressSite, saving them to StaticSite

    Drop-in replacement for Crawler.php, not requiring a web server

*/

namespace WP2Static;

// TODO: how to capture these when not making a request?
// listen for a WP 404 hook or something in the generated content?
// define( 'WP2STATIC_REDIRECT_CODES', [ 301, 302, 303, 307, 308 ] );

class Generator {

    /**
     * @var string
     */
    private $site_path;

    /**
     * Crawler constructor
     */
    public function __construct() {
        $this->site_path = rtrim( SiteInfo::getURL( 'site' ), '/' );

        $base_uri = $this->site_path;
    }

    // phpcs:ignore Generic.Files.LineLength.TooLong
    public static function wp2staticGenerate( string $static_site_path, string $crawler_slug ) : void {
        // TODO: move into own addon
        // use this generator vs addon.... hmm, should move this into own crawler addon...?
        if ( 'wp2static' === $crawler_slug ) {
            $generator = new Generator();
            $generator->generateSite( $static_site_path );
        }
    }

    /**
     * Generate content from URLs in WordPressSite, saving them to StaticSite
     */
    public function generateSite( string $static_site_path ) : void {
        $generated = 0;
        $cache_hits = 0;

        WsLog::l( 'Starting to generate detected URLs.' );

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
        foreach ( $crawlable_paths as $root_relative_path ) {
            $url = $root_relative_path;

            $buffered_output = $this->generateURLOutput( $url );

            if ( ! is_string( $buffered_output ) ) {
                continue;
            }

            $generated_contents = $buffered_output;
            // TODO: fake it til you understand it
            $status_code = 200;

            $redirect_to = null;

            // TODO: do away with redirect codes from responses
            // manage via reading WP redirection plugins, maybe see Hugo's "Aliases"
            $page_hash = md5( $generated_contents );

            // TODO: as John mentioned, we're only skipping the saving,
            // not crawling here. Let's look at improving that... or speeding
            // up with async requests, at least
            if ( $use_crawl_cache ) {
                // if not already cached
                if ( CrawlCache::getUrl( $root_relative_path, $page_hash ) ) {
                    $cache_hits++;

                    continue;
                }
            }

            $generated++;

            if ( $generated_contents ) {
                // do some magic here - naive: if URL ends in /, save to /index.html
                // TODO: will need love for example, XML files
                // check content type, serve .xml/rss, etc instead
                if ( mb_substr( $root_relative_path, -1 ) === '/' ) {
                    StaticSite::add( $root_relative_path . 'index.html', $generated_contents );
                } else {
                    StaticSite::add( $root_relative_path, $generated_contents );
                }
            }

            CrawlCache::addUrl(
                $root_relative_path,
                $page_hash,
                $status_code,
                $redirect_to
            );

            // incrementally log crawl progress
            if ( $generated % 300 === 0 ) {
                $notice = "Generation progress: $generated generated, " .
                    "$cache_hits skipped (cached).";
                WsLog::l( $notice );
            }
        }

        WsLog::l(
            "Generation complete. $generated generated, $cache_hits skipped (cached)."
        );

        $args = [
            'staticSitePath' => $static_site_path,
            'generated' => $generated,
            'cache_hits' => $cache_hits,
        ];

        do_action( 'wp2static_generation_complete', $args );
    }

    /**
     * Generates content for a given WP URL
     *
     * @return String|bool response object
     */
    public function generateURLOutput( string $url ) {
        ob_start();

        $_SERVER['REQUEST_URI'] = '/author/admin/';
        include get_home_path() . 'index.php';

        return ob_get_clean();
    }
}
