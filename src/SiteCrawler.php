<?php

namespace WP2Static;

use Exception;

class SiteCrawler extends Base {

    public $processed_file;
    public $file_type;
    public $content_type;
    public $extension;
    public $archive_dir;
    public $list_of_urls_to_crawl_path;
    public $urls_to_crawl;
    public $rewrite_rules;
    public $site_url_host;
    public $destination_url;
    public $ch;
    public $request;
    public $url;
    public $page_url;
    public $curl_options;

    /**
     *  SiteCrawler constructor
     *
     * @param mixed[] $rewrite_rules rewrite rules
     */
    public function __construct(
        array $rewrite_rules,
        string $site_url_host,
        string $destination_url
    ) {
        $this->loadSettings();

        /*
         TODO: implement crawl-caching, to greatly speed up the process
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
        $this->list_of_urls_to_crawl_path = '';
        $this->urls_to_crawl = '';
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
        $this->list_of_urls_to_crawl_path =
            SiteInfo::getPath( 'uploads' ) .
            'wp2static-working-files/FINAL-CRAWL-LIST.txt';

        if ( ! is_file( $this->list_of_urls_to_crawl_path ) ) {
            $err = 'ERROR: LIST OF URLS TO CRAWL NOT FOUND AT: ' .
                $this->list_of_urls_to_crawl_path;
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        } else {
            if ( filesize( $this->list_of_urls_to_crawl_path ) ) {
                $this->crawlABitMore();
            } else {
                $via_ui = filter_input( INPUT_POST, 'ajax_action' );

                if ( is_string( $via_ui ) ) {
                    echo 'SUCCESS';
                }
            }
        }
    }

    /**
     *  Crawl more URLs
     *
     * @throws WP2StaticException
     */
    public function crawlABitMore() : void {
        $batch_of_links_to_crawl = array();

        // get urls to crawl (can skip this with targeted query)

        $this->urls_to_crawl = file(
            $this->list_of_urls_to_crawl_path,
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );

        if ( ! $this->urls_to_crawl ) {
            $err = 'Expected more URLs to crawl, found none';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        $total_links = count( $this->urls_to_crawl );

        if ( $total_links < 1 ) {
            WsLog::l(
                'ERROR: LIST OF URLS TO CRAWL NOT FOUND AT: ' .
                $this->list_of_urls_to_crawl_path
            );
            die();
        }

        if ( $this->settings['crawl_increment'] > $total_links ) {
            $this->settings['crawl_increment'] = $total_links;
        }

        for ( $i = 0; $i < $this->settings['crawl_increment']; $i++ ) {
            $link_from_crawl_list = array_shift( $this->urls_to_crawl );

            if ( $link_from_crawl_list ) {
                $batch_of_links_to_crawl[] = $link_from_crawl_list;
            }
        }

        // resave crawl list file, minus those from this batch
        file_put_contents(
            $this->list_of_urls_to_crawl_path,
            implode( "\r\n", $this->urls_to_crawl )
        );

        chmod( $this->list_of_urls_to_crawl_path, 0664 );

        $this->archive_dir = SiteInfo::getPath( 'uploads' ) .
            '/wp2static-exported-site/';

        $exclusions = array( 'wp-json' );

        if ( isset( $this->settings['excludeURLs'] ) ) {
            $user_exclusions = explode(
                "\n",
                str_replace( "\r", '', $this->settings['excludeURLs'] )
            );

            $exclusions = array_merge(
                $exclusions,
                $user_exclusions
            );
        }

        foreach ( $batch_of_links_to_crawl as $link_to_crawl ) {
            $url = $link_to_crawl;

            $page_url = SiteInfo::getUrl( 'site' ) . ltrim( $url, '/' );

            foreach ( $exclusions as $exclusion ) {

                $exclusion = trim( $exclusion );

                if ( $exclusion != '' ) {
                    if ( false !== strpos( $url, $exclusion ) ) {
                        WsLog::l(
                            'Excluding ' . $url .
                            ' because of rule ' . $exclusion
                        );

                        continue 2;
                    }
                }

                if ( ! isset( $this->settings['dontUseCrawlCaching'] ) ) {
                    if ( CrawlCache::getUrl( $url ) ) {
                        continue 2;
                    }
                }
            }

            $this->crawlSingleURL( $page_url );
        }

        $this->checkIfMoreCrawlingNeeded( $this->urls_to_crawl );

        // reclaim memory after each crawl
        $url_reponse = null;
        unset( $url_reponse );
    }

    /**
     *  Check if more crawling is required
     *
     *  @param string[] $urls_to_crawl Remaining URLs to crawl in batch
     */
    public function checkIfMoreCrawlingNeeded( array $urls_to_crawl ) : void {
        $remaining_urls = count( $urls_to_crawl );
        $via_ui = filter_input( INPUT_POST, 'ajax_action' );

        if ( $remaining_urls > 0 ) {
            if ( is_string( $via_ui ) ) {
                echo $remaining_urls;
            } else {
                $this->crawl();
            }
        } else {
            WsLog::l( 'Crawling URLs phase completed' );

            if ( is_string( $via_ui ) ) {
                echo 'SUCCESS';
            }
        }
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

        switch ( $file_type ) {
            case 'html':
                $processor = new HTMLProcessor(
                    $this->rewrite_rules,
                    $this->site_url_host,
                    $this->destination_url,
                    $this->settings['rewrite_rules'],
                    $this->ch
                );

                $this->processed_file = $processor->processHTML(
                    $output,
                    $page_url
                );

                if ( $this->processed_file ) {
                    $this->processed_file = $processor->getHTML(
                        $processor->xml_doc,
                        $this->settings['forceHTTPS'],
                        $this->settings['forceRewriteSiteURLs']
                    );
                }

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

        CrawlCache::addUrl( $url );
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
