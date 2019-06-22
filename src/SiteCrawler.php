<?php

namespace WP2Static;

class SiteCrawler {

    private $allow_offline_usage;
    private $archive_dir;
    private $asset_downloader;
    private $ch;
    private $content_type;
    private $curl_options;
    private $destination_url;
    private $extension;
    private $file_type;
    private $include_discovered_assets;
    private $page_url;
    private $processed_file;
    private $request;
    private $rewrite_rules;
    private $settings;
    private $site_url;
    private $site_url_host;
    private $url;
    private $use_document_relative_urls;
    private $use_site_root_relative_urls;
    private $remove_wp_meta;
    private $remove_conditional_head_comments;
    private $remove_wp_links;
    private $remove_canonical_links;
    private $create_empty_favicon;
    private $remove_html_comments;

    /**
     *  SiteCrawler constructor
     *
     * @param mixed[] $rewrite_rules rewrite rules
     * @param mixed[] $settings all plugin settings
     */
    public function __construct(
        bool $allow_offline_usage,
        bool $remove_wp_meta,
        bool $remove_conditional_head_comments,
        bool $remove_wp_links,
        bool $remove_canonical_links,
        bool $create_empty_favicon,
        bool $remove_html_comments,
        string $site_url,
        string $site_url_host,
        string $destination_url,
        array $rewrite_rules,
        array $settings,
        AssetDownloader $asset_downloader
    ) {
        $this->allow_offline_usage = $allow_offline_usage;
        $this->site_url = $site_url;
        $this->site_url_host = $site_url_host;
        $this->destination_url = $destination_url;
        $this->rewrite_rules = $rewrite_rules;
        $this->settings = $settings;
        $this->asset_downloader = $asset_downloader;
        $this->remove_wp_meta = $remove_wp_meta;
        $this->remove_conditional_head_comments =
            $remove_conditional_head_comments;
        $this->remove_wp_links = $remove_wp_links;
        $this->remove_canonical_links = $remove_canonical_links;
        $this->create_empty_favicon = $create_empty_favicon;
        $this->remove_html_comments = $remove_html_comments;

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
        $this->archive_dir = '';
        $this->rewrite_rules = $rewrite_rules;
        $this->site_url_host = $site_url_host;
        $this->destination_url = $destination_url;
        $this->ch = curl_init();
        $this->request = new Request();

        $this->curl_options = [];

        if ( isset( $this->settings['crawlPort'] ) ) {
            $this->curl_options[ CURLOPT_PORT ] = $this->settings['crawlPort'];
        }

        if ( isset( $this->settings['crawlUserAgent'] ) ) {
            $this->curl_options[ CURLOPT_USERAGENT ] =
                $this->settings['crawlUserAgent'];
        }

        if ( isset( $this->settings['useBasicAuth'] ) ) {
            $this->curl_options[ CURLOPT_USERPWD ] =
                $this->settings['basicAuthUser'] . ':' .
                $this->settings['basicAuthPassword'];
        }
    }

    /**
     *  Initiate crawling
     *
     * @throws WP2StaticException
     */
    public function crawl() : void {
        $urls_to_crawl = UrlQueue::getCrawlableURLs();

        if ( ! $urls_to_crawl ) {
            $err = 'Expected more URLs to crawl, found none';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        $this->archive_dir = SiteInfo::getPath( 'uploads' ) .
            'wp2static-exported-site/';

        $exclusions = array( 'wp-json' );

        foreach ( $urls_to_crawl as $url ) {
            $page_url = SiteInfo::getUrl( 'site' ) . ltrim( $url, '/' );

            if ( ! isset( $this->settings['dontUseCrawlCaching'] ) ) {
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

        $good_response_codes = array( '200', '201', '301', '302', '304' );

        if ( ! in_array( $status_code, $good_response_codes ) ) {
            WsLog::l(
                'BAD RESPONSE STATUS (' . $status_code . '): ' . $page_url
            );
        }

        $base_url = $this->settings['baseUrl'];

        $file_type = $this->detectFileType(
            $page_url,
            $curl_content_type
        );

        $user_rewrite_rules = $this->settings['rewrite_rules'];

        if ( ! $user_rewrite_rules ) {
            $user_rewrite_rules = '';
        }

        switch ( $file_type ) {
            case 'html':
                $processor = new HTMLProcessor(
                    $this->rewrite_rules,
                    $this->site_url_host,
                    $this->destination_url,
                    $user_rewrite_rules,
                    $this->ch
                );

                $dom_iterator = new DOMIterator(
                    $this->site_url,
                    $this->site_url_host,
                    $this->page_url,
                    $this->destination_url,
                    (bool) $this->allow_offline_usage,
                    (bool) $this->use_document_relative_urls,
                    (bool) $this->use_site_root_relative_urls,
                    (bool) $this->remove_wp_meta,
                    (bool) $this->remove_conditional_head_comments,
                    (bool) $this->remove_wp_links,
                    (bool) $this->remove_canonical_links,
                    (bool) $this->create_empty_favicon,
                    (bool) $this->remove_html_comments,
                    $this->rewrite_rules,
                    (bool) $this->include_discovered_assets,
                    $this->asset_downloader
                );

                $xml_doc = $dom_iterator->processHTML(
                    $output,
                    $page_url
                );

                $dom_to_html = new DOMToHTMLGenerator();
                $this->processed_file = $dom_to_html->getHTML(
                    $xml_doc,
                    isset( $this->settings['forceHTTPS'] ),
                    isset( $this->settings['forceRewriteSiteURLs'] )
                );

                break;

            case 'css':
                if ( isset( $this->settings['parse_css'] ) ) {
                    $processor = new CSSProcessor();

                    $this->processed_file = $processor->processCSS(
                        $output,
                        $page_url
                    );

                    if ( $this->processed_file ) {
                        $this->processed_file = $processor->getCSS();
                    }
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
        $file_writer->saveFile( $this->archive_dir );

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
