<?php

use GuzzleHttp\Client;

class SiteCrawler {


    public function __construct() {
        $target_settings = array(
            'general',
            'wpenv',
            'crawling',
            'processing',
            'advanced',
        );

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $target_settings );
        } else {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/DBSettings.php';

            $this->settings = WPSHO_DBSettings::get( $target_settings );
        }

        $this->processed_file = '';
        $this->file_type = '';
        $this->response = '';
        $this->content_type = '';
        $this->url = '';
        $this->full_url = '';
        $this->extension = '';
        $this->archive_dir = '';
        $this->list_of_urls_to_crawl_path = '';
        $this->urls_to_crawl = '';

        if ( ! defined( 'WP_CLI' ) ) {
            if ( $_POST['ajax_action'] === 'crawl_again' ) {
                $this->crawl_discovered_links();
            } elseif ( $_POST['ajax_action'] === 'crawl_site' ) {
                $this->crawl_site();
            }
        }
    }

    public function generate_discovered_links_list() {
        $second_crawl_file_path = $this->settings['wp_uploads_path'] .
        '/WP-STATIC-2ND-CRAWL-LIST.txt';

        $already_crawled = file(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-INITIAL-CRAWL-LIST.txt',
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );

        $discovered_links = file(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-DISCOVERED-URLS.txt',
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );

        $unique_discovered_links = array_unique( $discovered_links );
        sort( $unique_discovered_links );

        file_put_contents(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-DISCOVERED-URLS-LOG.txt',
            implode( PHP_EOL, $unique_discovered_links )
        );

        chmod(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-DISCOVERED-URLS-LOG.txt',
            0664
        );

        $discovered_links = array_diff(
            $unique_discovered_links,
            $already_crawled
        );

        file_put_contents(
            $second_crawl_file_path,
            implode( PHP_EOL, $discovered_links )
        );

        chmod( $second_crawl_file_path, 0664 );

        copy(
            $second_crawl_file_path,
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-FINAL-2ND-CRAWL-LIST.txt'
        );

        chmod(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-FINAL-2ND-CRAWL-LIST.txt',
            0664
        );
    }

    public function crawl_discovered_links() {
        if ( defined( 'WP_CLI' ) && ! defined( 'CRAWLING_DISCOVERED' ) ) {
            define( 'CRAWLING_DISCOVERED', true );
        }

        $second_crawl_file_path = $this->settings['wp_uploads_path'] .
        '/WP-STATIC-2ND-CRAWL-LIST.txt';

        // NOTE: the first iteration of the 2nd crawl phase,
        // the list of URLs for 2nd crawl is prepared
        if ( ! is_file( $second_crawl_file_path ) ) {
            $this->generate_discovered_links_list();
        }

        $this->list_of_urls_to_crawl_path =
            $this->settings['wp_uploads_path'] .
            '/WP-STATIC-FINAL-2ND-CRAWL-LIST.txt';

        if ( ! is_file( $this->list_of_urls_to_crawl_path ) ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
            WsLog::l(
                'ERROR: LIST OF URLS TO CRAWL NOT FOUND AT: ' .
                    $this->list_of_urls_to_crawl_path
            );
            die();
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

    public function crawl_site() {
        // crude detection for CLI export to use 2nd crawl phase
        $this->list_of_urls_to_crawl_path =
            $this->settings['wp_uploads_path'] .
            '/WP-STATIC-FINAL-2ND-CRAWL-LIST.txt';

        if ( is_file( $this->list_of_urls_to_crawl_path ) ) {
            $this->crawl_discovered_links();

            return;
        }

        $this->list_of_urls_to_crawl_path =
            $this->settings['wp_uploads_path'] .
            '/WP-STATIC-FINAL-CRAWL-LIST.txt';

        if ( ! is_file( $this->list_of_urls_to_crawl_path ) ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
            WsLog::l(
                'ERROR: LIST OF URLS TO CRAWL NOT FOUND AT: ' .
                    $this->list_of_urls_to_crawl_path
            );
            die();
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

        $this->urls_to_crawl = file(
            $this->list_of_urls_to_crawl_path,
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );

        $total_links = count( $this->urls_to_crawl );

        if ( $total_links < 1 ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
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

        $this->remaining_urls_to_crawl = count( $this->urls_to_crawl );

        // resave crawl list file, minus those from this batch
        file_put_contents(
            $this->list_of_urls_to_crawl_path,
            implode( "\r\n", $this->urls_to_crawl )
        );

        chmod( $this->list_of_urls_to_crawl_path, 0664 );

        // TODO: required in saving/copying, but not here? optimize...
        $handle = fopen(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-CURRENT-ARCHIVE.txt',
            'r'
        );

        $this->archive_dir = stream_get_line( $handle, 0 );

        foreach ( $batch_of_links_to_crawl as $link_to_crawl ) {
            $this->url = $link_to_crawl;
            $this->full_url = $this->settings['wp_site_url'] .
                ltrim( $this->url, '/' );

            if ( ! isset( $this->settings['excludeURLs'] ) ) {
                $this->settings['excludeURLs'] = '';
            }

            // add default exclusions
            $this->settings['excludeURLs'] .=
                PHP_EOL . 'wp-json';

            $exclusions = explode(
                "\n",
                str_replace( "\r", '', $this->settings['excludeURLs'] )
            );

            foreach ( $exclusions as $exclusion ) {
                $exclusion = trim( $exclusion );
                if ( $exclusion != '' ) {
                    if ( strpos( $this->url, $exclusion ) ) {
                        // skip the outer foreach loop
                        continue 2;
                    }
                }
            }

            $this->file_extension = $this->getExtensionFromURL();

            $this->loadFileForProcessing();
            $this->saveFile();
        }

        $this->checkIfMoreCrawlingNeeded();

        // reclaim memory after each crawl
        $urlResponse = null;
        unset( $urlResponse );
    }

    public function loadFileForProcessing() {
        require_once dirname( __FILE__ ) . '/../GuzzleHttp/autoloader.php';

        $client = new \GuzzleHttp\Client();

        $request_options = array(
            'http_errors' => false,
            'verify' => false,
        );

        if ( isset( $this->settings['useBasicAuth'] ) ) {
            $request_options['auth'] = array(
                $this->settings['basicAuthUser'],
                $this->settings['basicAuthPassword'],
            );
        }

        $this->response =
            $client->get(
                $this->full_url,
                $request_options
            );

        // TODO: add options for http digest, not just basic
        $this->crawled_links_file =
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-CRAWLED-LINKS.txt';

        $good_response_codes = array( '200', '201', '301', '302', '304' );
        $status_code = $this->response->getStatusCode();

        if ( ! in_array( $status_code, $good_response_codes ) ) {
            require_once dirname( __FILE__ ) . '/../StaticHtmlOutput/WsLog.php';
            WsLog::l(
                'BAD RESPONSE STATUS (' . $status_code . '): ' . $this->url
            );

            file_put_contents(
                $this->settings['wp_uploads_path'] .
                    '/WP-STATIC-404-LOG.txt',
                $status_code . ':' . $this->url . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );

            chmod(
                $this->settings['wp_uploads_path'] .
                    '/WP-STATIC-404-LOG.txt',
                0664
            );
        } else {
            file_put_contents(
                $this->crawled_links_file,
                $this->url . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );

            chmod( $this->crawled_links_file, 0664 );
        }

        $baseUrl = $this->settings['baseUrl'];

        $this->detectFileType( $this->full_url );

        switch ( $this->file_type ) {
            case 'html':
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/HTMLProcessor.php';

                $processor = new HTMLProcessor();

                $this->processed_file = $processor->processHTML(
                    $this->response->getBody(),
                    $this->full_url
                );

                if ( $this->processed_file ) {
                    $this->processed_file = $processor->getHTML();
                }

                break;

            case 'css':
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/CSSProcessor.php';
                $processor = new CSSProcessor();

                $this->processed_file = $processor->processCSS(
                    $this->response->getBody(),
                    $this->full_url
                );

                if ( $this->processed_file ) {
                    $this->processed_file = $processor->getCSS();
                }

                break;

            case 'js':
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/JSProcessor.php';
                $processor = new JSProcessor();

                $processor->processJS(
                    $this->response->getBody(),
                    $this->full_url
                );

                $this->processed_file = $processor->getJS();

                break;

            case 'txt':
            case 'json':
            case 'xml':
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/TXTProcessor.php';
                $processor = new TXTProcessor();

                $processor->processTXT(
                    $this->response->getBody(),
                    $this->full_url
                );

                $this->processed_file = $processor->getTXT();

                break;

            default:
                $this->processed_file = $this->response->getBody();

                break;
        }
    }

    public function checkIfMoreCrawlingNeeded() {
        if ( $this->remaining_urls_to_crawl > 0 ) {
            if ( ! defined( 'WP_CLI' ) ) {
                echo $this->remaining_urls_to_crawl;
            } else {
                $this->crawl_site();
            }
        } else {
            if ( ! defined( 'WP_CLI' ) ) {
                echo 'SUCCESS';
            }
        }
    }

    public function saveFile() {
        require_once dirname( __FILE__ ) .
            '/../StaticHtmlOutput/FileWriter.php';

        $file_writer = new FileWriter(
            $this->url,
            $this->processed_file,
            $this->file_type,
            $this->content_type
        );

        $file_writer->saveFile( $this->archive_dir );
    }

    public function getExtensionFromURL() {
        $url_path = parse_url( $this->url, PHP_URL_PATH );
        $extension = pathinfo( $url_path, PATHINFO_EXTENSION );

        if ( ! $extension ) {
            return '';
        }

        return $extension;
    }

    public function detectFileType() {
        if ( $this->file_extension ) {
            $this->file_type = $this->file_extension;
        } else {
            $type = $this->content_type =
                $this->response->getHeaderLine( 'content-type' );

            if ( stripos( $type, 'text/html' ) !== false ) {
                $this->file_type = 'html';
            } elseif ( stripos( $type, 'rss+xml' ) !== false ) {
                $this->file_type = 'xml';
            } elseif ( stripos( $type, 'text/xml' ) !== false ) {
                $this->file_type = 'xml';
            } elseif ( stripos( $type, 'application/xml' ) !== false ) {
                $this->file_type = 'xml';
            } elseif ( stripos( $type, 'application/json' ) !== false ) {
                $this->file_type = 'json';
            } else {
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/WsLog.php';
                WsLog::l(
                    'no filetype inferred from content-type: ' .
                    $this->response->getHeaderLine( 'content-type' ) .
                    ' url: ' . $this->url
                );
            }
        }
    }
}

$site_crawler = new SiteCrawler();

