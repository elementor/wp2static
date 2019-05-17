<?php

namespace WP2Static;

use Exception;

class SiteCrawler extends Base {

    public function __construct(
        $rewrite_rules,
        $site_url_host,
        $destination_url
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
    }

    public function crawl() {
        $this->list_of_urls_to_crawl_path =
            SiteInfo::getPath( 'uploads' ) .
            'wp2static-working-files/FINAL-CRAWL-LIST.txt';

        if ( ! is_file( $this->list_of_urls_to_crawl_path ) ) {
            $err = 'ERROR: LIST OF URLS TO CRAWL NOT FOUND AT: ' .
                $this->list_of_urls_to_crawl_path;
            WsLog::l( $err );
            throw new Exception( $err );
        } else {
            if ( filesize( $this->list_of_urls_to_crawl_path ) ) {
                $this->crawlABitMore();
            } else {
                if ( ! defined( 'WP_CLI' ) ) {
                    echo 'SUCCESS';
                }
            }
        }
    }

    public function crawlABitMore() {
        $batch_of_links_to_crawl = array();

        // get urls to crawl (can skip this with targeted query)

        $this->urls_to_crawl = file(
            $this->list_of_urls_to_crawl_path,
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );

        if ( ! $this->urls_to_crawl ) {
            $err = 'Expected more URLs to crawl, found none';
            WsLog::l( $err );
            throw new Exception( $err );
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

    public function checkIfMoreCrawlingNeeded( $urls_to_crawl ) {
        $remaining_urls = count( $urls_to_crawl );
        if ( $remaining_urls > 0 ) {
            if ( ! defined( 'WP_CLI' ) ) {
                echo $remaining_urls;
            } else {
                $this->crawl();
            }
        } else {
            WsLog::l( 'Crawling URLs phase completed' );

            if ( ! defined( 'WP_CLI' ) ) {
                echo 'SUCCESS';
            }
        }
    }

    public function getExtensionFromURL( $url ) {
        $url_path = parse_url( $url, PHP_URL_PATH );

        if ( ! is_string( $url_path ) ) {
            $err = 'Invalid URL encountered when checking extension';
            WsLog::l( $err );
            throw new Exception( $err );
        }

        $extension = pathinfo( $url_path, PATHINFO_EXTENSION );

        if ( ! $extension ) {
            return '';
        }

        return $extension;
    }

    public function detectFileType( $url, $content_type ) {
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

    public function checkForCurlErrors( $response, $curl_handle ) {
        if ( $response === false ) {
            $response = curl_error( $curl_handle );
            WsLog::l(
                'cURL error:' .
                stripslashes( $response )
            );
        }
    }

    public function crawlSingleURL( $url ) {
        $curl_options = [];

        if ( isset( $this->settings['crawlPort'] ) ) {
            $curl_options[ CURLOPT_PORT ] = $this->settings['crawlPort'];
        }

        if ( isset( $this->settings['crawlUserAgent'] ) ) {
            $curl_options[ CURLOPT_USERAGENT ] =
                $this->settings['crawlUserAgent'];
        }

        if ( isset( $this->settings['useBasicAuth'] ) ) {
            $curl_options[ CURLOPT_USERPWD ] =
                $this->settings['basicAuthUser'] . ':' .
                $this->settings['basicAuthPassword'];
        }

        $request = new Request();

        $response = $request->getURL(
            $url,
            $curl_options
        );

        $this->processCrawledURL( $response['ch'], $response['body'] );
    }

    public function processCrawledURL( $curl_handle, $output ) {
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
                    $this->settings['rewrite_rules']
                );

                $this->processed_file = $processor->processHTML(
                    $output,
                    $page_url
                );

                if ( $this->processed_file ) {
                    $this->processed_file = $processor->getHTML(
                        $processor->xml_doc
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

        curl_close( $curl_handle );
    }

    public function saveCrawledURL( $url, $body, $file_type, $content_type ) {
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

    public function getRelativeURLFromFullURL( $page_url ) {
        $site_url = SiteInfo::getUrl( 'site' );

        if ( ! is_string( $site_url ) ) {
            $err = 'Site URL not defined ';
            WsLog::l( $err );
            throw new Exception( $err );
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
