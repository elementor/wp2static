<?php

use GuzzleHttp\Client;

class SiteCrawler {


    public function __construct() {
        // TODO: security check that this is being called from same server
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
            error_log( 'TODO: load settings from DB' );
        }

        $this->processed_file = '';
        $this->file_type = '';
        $this->response = '';
        $this->content_type = '';
        $this->url = '';
        $this->extension = '';
        $this->archive_dir = '';
        $this->list_of_urls_to_crawl_path = '';
        $this->urls_to_crawl = '';

        // crawl links discovered in first run
        if ( $_POST['ajax_action'] === 'crawl_again' ) {
            if ( ! isset( $this->settings['discoverNewURLs'] ) ) {
                echo 'SUCCESS';
                die();
            }

            $second_crawl_file_path = $this->settings['working_directory'] .
            '/WP-STATIC-2ND-CRAWL-LIST.txt';

            // generate the 2nd crawl list on the first request
            if ( ! is_file( $second_crawl_file_path ) ) {
                // TODO: read in WP-STATIC-FINAL-CRAWL-LIST clone vs INITIAL
                $already_crawled = file(
                    $this->settings['working_directory'] .
                        '/WP-STATIC-INITIAL-CRAWL-LIST.txt',
                    FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
                );

                // read WP-STATIC-DISCOVERED-URLS into $discovered_links
                $discovered_links = file(
                    $this->settings['working_directory'] .
                        '/WP-STATIC-DISCOVERED-URLS',
                    FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
                );

                $unique_discovered_links = array_unique( $discovered_links );

                $discovered_links = array_diff(
                    $unique_discovered_links,
                    $already_crawled
                );

                file_put_contents(
                    $second_crawl_file_path,
                    implode( PHP_EOL, $discovered_links )
                );

                copy(
                    $second_crawl_file_path,
                    $this->settings['working_directory'] .
                        '/WP-STATIC-FINAL-2ND-CRAWL-LIST.txt'
                );
            }

            $this->list_of_urls_to_crawl_path =
                $this->settings['working_directory'] .
                '/WP-STATIC-FINAL-2ND-CRAWL-LIST.txt';
        } else {
            $this->list_of_urls_to_crawl_path =
                $this->settings['working_directory'] .
                '/WP-STATIC-FINAL-CRAWL-LIST.txt';
        }

        $this->viaCLI = false;

        $this->crawl_site();
    }

    public function crawl_site( $viaCLI = false ) {
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
                $this->crawlABitMore( $this->viaCLI );
            } else {
                // TODO: added to handle case where 2nd crawl list is empty
                echo 'SUCCESS';
            }
        }
    }

    public function crawlABitMore( $viaCLI = false ) {
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

        // TODO: required in saving/copying, but not here? optimize...
        $handle = fopen(
            $this->settings['wp_uploads_path'] . '/WP-STATIC-CURRENT-ARCHIVE',
            'r'
        );
        $this->archive_dir = stream_get_line( $handle, 0 );

        foreach ( $batch_of_links_to_crawl as $link_to_crawl ) {
            $this->url = $link_to_crawl;

            error_log($this->url);

            if ( isset( $this->settings['excludeURLs'] ) ) {
                // TODO: check for exclusions
                $exclusions = explode(
                    "\n",
                    str_replace( "\r", '', $this->settings['excludeURLs'] )
                );

                foreach ( $exclusions as $exclusion ) {
                    $exclusion = trim( $exclusion );
                    if ( $exclusion != '' ) {
                        if ( strpos( $this->url, $exclusion ) ) {
                            $this->checkIfMoreCrawlingNeeded();
                            return;
                        }
                    }
                }
            }

            $this->file_extension = $this->getExtensionFromURL();

            if ( $this->canFileBeCopiedWithoutProcessing() ) {
                $this->copyFile();
            } else {
                $this->loadFileForProcessing();
                $this->saveFile();
            }
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
            $client->request( 'GET', $this->url, $request_options );
        $this->crawled_links_file =
            $this->settings['working_directory'] . '/WP-STATIC-CRAWLED-LINKS';

        $good_response_codes = array( '200', '201', '301', '302', '304' );
        $status_code = $this->response->getStatusCode();

        if ( ! in_array( $status_code, $good_response_codes ) ) {
            require_once dirname( __FILE__ ) . '/../StaticHtmlOutput/WsLog.php';
            WsLog::l(
                'BAD RESPONSE STATUS (' . $status_code . '): ' . $this->url
            );
        } else {
            file_put_contents(
                $this->crawled_links_file,
                $this->url . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        }

        // TODO: remove/rename
        $baseUrl = $this->settings['baseUrl'];

        $this->detectFileType( $this->url );

        switch ( $this->file_type ) {
            case 'html':
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/HTMLProcessor.php';

                $processor = new HTMLProcessor();

                // TODO: if not reusing the instance, switch to
                // static functions for performance
                $this->processed_file = $processor->processHTML(
                    $this->response->getBody(),
                    $this->url
                );

                if ( $this->processed_file ) {
                    $this->processed_file = $processor->getHTML();
                }

                break;

            // TODO: apply other replacement functions to all processors
            case 'css':
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/CSSProcessor.php';
                $processor = new CSSProcessor();

                $this->processed_file = $processor->processCSS(
                    $this->response->getBody(),
                    $this->url
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
                    $this->url
                );

                $this->processed_file = $processor->getJS();

                break;

            case 'txt':
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/TXTProcessor.php';
                $processor = new TXTProcessor(
                    $this->response->getBody(),
                    $this->settings['wp_site_url']
                );

                $processor->normalizeURLs( $this->url );

                $this->processed_file = $processor->getTXT();

                break;

            case 'xml':
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/XMLProcessor.php';

                $processor = new XMLProcessor();

                $this->processed_file = $processor->processXML(
                    $this->response->getBody(),
                    $this->url
                );

                if ( $this->processed_file ) {
                    $this->processed_file = $processor->getXML();
                }

                break;

            case 'json':
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/WsLog.php';
                WsLog::l(
                    'no handler for json without extension yet'
                );

                break;

            default:
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/WsLog.php';
                WsLog::l( 'WARNING: NO PROCESSOR FOR FILE: ' . $this->url );
                break;
        }
    }

    public function checkIfMoreCrawlingNeeded() {
        if ( $this->remaining_urls_to_crawl > 0 ) {
            echo $this->remaining_urls_to_crawl;
        } else {
            echo 'SUCCESS';
        }

        // if being called via the CLI, just keep crawling (TODO: until when?)
        if ( $this->viaCLI ) {
            $this->crawl_site( $this->viaCLI );
        }
    }

    public function saveFile() {
        // response body processing is complete, now time to save the file
        // contents to the archive
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

    public function copyFile() {
        require_once dirname( __FILE__ ) .
            '/../StaticHtmlOutput/FileCopier.php';

        $file_copier = new FileCopier(
            $this->url,
            $this->settings['wp_site_url'],
            $this->settings['wp_site_path']
        );

        $file_copier->copyFile( $this->archive_dir );
    }

    public function getExtensionFromURL() {
        $url_path = parse_url( $this->url, PHP_URL_PATH );
        $extension = pathinfo( $url_path, PATHINFO_EXTENSION );

        if ( ! $extension ) {
            return '';
        }

        return $extension;
    }

    public function canFileBeCopiedWithoutProcessing() {
        if ( ! $this->file_extension ) {
            return false;
        }

        $extensions_to_process = array(
            'html',
            'shtml',
            'css',
            'js',
            'json',
            'xml',
            'rss',
            'txt',
        );

        if ( ! in_array( $this->file_extension, $extensions_to_process ) ) {
            return true;
        }

        return false;
    }

    public function detectFileType() {
        if ( $this->file_extension ) {
            $this->file_type = $this->file_extension;
        } else {
            // further detect type based on content type
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

