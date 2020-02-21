<?php
/*
    TODO: Holding this here for AssetDownloader reference, but now
    use Crawler class
*/


namespace WP2Static;

class SiteCrawler {

    private $asset_downloader;
    private $ch;
    /**
     * Content type.
     *
     * @var string
     */
    private $content_type;
    /**
     * Array of Curl options.
     *
     * @var array<int, mixed>
     */
    private $curl_options;
    private $extension;
    private $file_type;
    private $page_url;
    /**
     * The processed crawled file, before saving.
     *
     * @var string
     */
    private $processed_file;
    private $request;
    private $rewrite_rules;
    private $settings;

    /**
     *  SiteCrawler constructor
     */
    public function __construct(
        AssetDownloader $asset_downloader
    ) {
        $this->asset_downloader = $asset_downloader;

        /*
           Implement crawl-caching, to greatly speed up the process
         *
         * helps to recover from mid-crawl failures. Use export-dir, keep
         * between runs. Load cache when starting a run. Check speed DB vs disk
         *
         * option in UI to delete the cache dir contents, else will
         * always append
         *

         * for saving detected static assets during crawl, check both Crawl
         * Cache and whether file exists within export dir
         *
         */

        $this->processed_file = '';
        $this->file_type = '';
        $this->content_type = '';
        $this->extension = '';
        $this->ch = curl_init();
        $this->request = new Request();

        $this->curl_options = [];

        if ( CoreOptions::getValue( 'crawlPort' ) ) {
            $this->curl_options[ CURLOPT_PORT ] =
                CoreOptions::getValue( 'crawlPort' );
        }

        if ( CoreOptions::getValue( 'crawlUserAgent' ) ) {
            $this->curl_options[ CURLOPT_USERAGENT ] =
                CoreOptions::getValue( 'crawlUserAgent' );
        }

        if ( CoreOptions::getValue( 'useBasicAuth' ) ) {
            $this->curl_options[ CURLOPT_USERPWD ] =
                CoreOptions::getValue( 'basicAuthUser' ) . ':' .
                CoreOptions::getValue( 'basicAuthPassword' );
        }
    }

    /**
     *  Initiate crawling
     *
     * @throws WP2StaticException
     */
    public function crawl() : void {
        error_log( 'crawling with SiteCrawler' );
        $urls_to_crawl = CrawlQueue::getCrawlableURLs();

        if ( ! $urls_to_crawl ) {
            $err = 'Expected more URLs to crawl, found none';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        $exclusions = [ 'wp-json' ];

        foreach ( $urls_to_crawl as $url ) {
            $page_url = SiteInfo::getUrl( 'site' ) . ltrim( $url, '/' );

            if ( CoreOptions::getValue( 'dontUseCrawlCaching' ) ) {
                if ( CrawlCache::getUrl( $url ) ) {
                    continue;
                }
            }

            $this->crawlSingleURL( $page_url );
        }

        $via_ui = filter_input( INPUT_POST, 'ajax_action' );

        if ( is_string( $via_ui ) ) {
            echo 'SUCCESS';
        }
    }

    /**
     *  Crawl more URLs
     *
     * @throws WP2StaticException
     */
    public function crawlABitMore() : void {

        // reclaim memory after each crawl
        $url_reponse = null;
        unset( $url_reponse );
    }

    /**
     *  Get extension from URL
     *
     * @throws WP2StaticException
     */
    public function getExtensionFromURL( string $url ) : string {
        $url_path = parse_url( $url, PHP_URL_PATH );

        if ( ! is_string( $url_path ) ) {
            $err = 'Invalid URL encountered when checking extension';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        $extension = pathinfo( $url_path, PATHINFO_EXTENSION );

        if ( ! $extension ) {
            return '';
        }

        return $extension;
    }

    public function detectFileType(
        string $url,
        string $content_type
    ) : string {
        // TODO: this needs to go after the crawling...
        $file_extension = $this->getExtensionFromURL( $url );

        $file_type = '';

        if ( $file_extension ) {
            $file_type = $file_extension;
        } else {
            $type = $this->content_type =
                $content_type;

            if ( stripos( $type, 'text/html' ) !== false ) {
                $file_type = 'html';
            } elseif ( stripos( $type, 'rss+xml' ) !== false ) {
                $file_type = 'xml';
            } elseif ( stripos( $type, 'text/xml' ) !== false ) {
                $file_type = 'xml';
            } elseif ( stripos( $type, 'application/xml' ) !== false ) {
                $file_type = 'xml';
            } elseif ( stripos( $type, 'application/json' ) !== false ) {
                $file_type = 'json';
            } else {
                WsLog::l(
                    'no filetype inferred from content-type: ' .
                    $type .
                    ' url: ' . $url
                );
            }
        }

        return $file_type;
    }

    /**
     * Check for cURL errors
     *
     * @param string $response response body
     * @param resource $curl_handle cURL handle
     */
    public function checkForCurlErrors(
        string $response,
        $curl_handle
    ) : void {
        if ( ! $response ) {
            $response = curl_error( $curl_handle );
            WsLog::l(
                'cURL error:' .
                stripslashes( $response )
            );
        }
    }

    public function crawlSingleURL( string $url ) : void {
        $response = $this->request->getURL(
            $url,
            $this->ch,
            $this->curl_options
        );

        $this->processCrawledURL( $response['ch'], $response['body'] );
    }

    /**
     * Process crawled URL
     *
     * @param resource $curl_handle curl handle resource
     * @param string $output response body
     */
    public function processCrawledURL(
            $curl_handle,
            string $output
    ) : void {
        $curl_info = curl_getinfo( $curl_handle );

        $this->checkForCurlErrors( $output, $curl_handle );

        $status_code = $curl_info['http_code'];

        $curl_content_type = isset( $curl_info['content_type'] ) ?
            $curl_info['content_type'] : '';

        $page_url = $curl_info['url'];

        $url = $this->getRelativeURLFromFullURL( $page_url );

        $good_response_codes = [ '200', '201', '301', '302', '304' ];

        if ( ! in_array( $status_code, $good_response_codes ) ) {
            WsLog::l(
                'BAD RESPONSE STATUS (' . $status_code . '): ' . $page_url
            );
        }

        $file_type = $this->detectFileType(
            $page_url,
            $curl_content_type
        );

        switch ( $file_type ) {
            case 'html':
                $dom_iterator = new DOMIterator(
                    $this->page_url,
                    $this->asset_downloader
                );

                $xml_doc = $dom_iterator->processHTML( $output );

                $dom_to_html = new DOMToHTMLGenerator();
                $this->processed_file = $dom_to_html->getHTML( $xml_doc );

                break;

            case 'css':
                if ( isset( $this->settings['parseCSS'] ) ) {
                    $processor = new CSSProcessor();

                    $this->processed_file = $processor->processCSS(
                        $output,
                        $page_url
                    );
                } else {
                    $this->processed_file = str_replace(
                        $this->rewrite_rules['site_url_patterns'],
                        $this->rewrite_rules['destination_url_patterns'],
                        $output
                    );
                }

                break;

            case 'txt':
            case 'js':
            case 'json':
            case 'xml':
                $this->processed_file = str_replace(
                    $this->rewrite_rules['site_url_patterns'],
                    $this->rewrite_rules['destination_url_patterns'],
                    $output
                );

                break;

            default:
                $this->processed_file = $output;

                break;
        }

        if ( ! is_string( $this->processed_file ) ) {
            return;
        }

        // need to make sure we've aborted before here if we shouldn't save
        $this->saveCrawledURL(
            $url,
            $this->processed_file,
            $file_type,
            $curl_content_type
        );
    }

    public function saveCrawledURL(
        string $url,
        string $body,
        string $file_type,
        string $content_type
    ) : void {
        $file_writer = new FileWriter(
            $url,
            $body,
            $file_type,
            $content_type
        );

        // TODO: better validate save success
        $file_writer->saveFile(
            SiteInfo::getPath( 'uploads' ) . 'wp2static-crawled-site/'
        );

        if ( ! isset( $this->settings['dontUseCrawlCaching'] ) ) {
            CrawlCache::addUrl( $url );
        }
    }

    /**
     * Get relative URL from absolute URL
     *
     * @throws WP2StaticException
     */
    public function getRelativeURLFromFullURL( string $page_url ) : string {
        $site_url = SiteInfo::getUrl( 'site' );

        if ( ! is_string( $site_url ) ) {
            $err = 'Site URL not defined ';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        $this->page_url = $site_url .
            ltrim( $this->url, '/' );

        $relative_url = str_replace(
            $site_url,
            '',
            $page_url
        );

        // ensure consistency with leading slash
        $relative_url = ltrim( $relative_url, '/' );

        return '/' . $relative_url;
    }
}
