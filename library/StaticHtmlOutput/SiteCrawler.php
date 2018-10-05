<?php

use GuzzleHttp\Client;

class SiteCrawler {


    public function __construct() {
        // TODO: security check that this is being called from same server
        // basic auth
        $this->useBasicAuth =
            isset( $_POST['useBasicAuth'] ) ?
            $_POST['useBasicAuth'] :
            false;
        $this->basicAuthUser =
            isset( $_POST['basicAuthUser'] ) ?
            $_POST['basicAuthUser'] :
            false;
        $this->basicAuthPassword =
            isset( $_POST['basicAuthPassword'] ) ?
            $_POST['basicAuthPassword'] :
            false;


        // require a baseUrl if creating an offline ZIP
        if ( isset( $_POST['baseUrl'] )  ) {
            $this->baseUrl = rtrim( $_POST['baseUrl'], "/" ) . '/';
        } else {
            $this->baseUrl = 'http://example.com/';
        }

        $this->wp_site_url = $_POST['wp_site_url'];
        $this->wp_site_path = $_POST['wp_site_path'];
        $this->wp_uploads_path = $_POST['wp_uploads_path'];
        $this->working_directory =
            isset( $_POST['workingDirectory'] ) ?
            $_POST['workingDirectory'] :
            $this->wp_uploads_path;
        $this->wp_uploads_url = $_POST['wp_uploads_url'];

        // processing related settings
        $this->rewriteWPCONTENT = $_POST['rewriteWPCONTENT'];
        $this->rewriteTHEMEROOT = $_POST['rewriteTHEMEROOT'];
        $this->rewriteTHEMEDIR = $_POST['rewriteTHEMEDIR'];
        $this->rewriteUPLOADS = $_POST['rewriteUPLOADS'];
        $this->rewritePLUGINDIR = $_POST['rewritePLUGINDIR'];
        $this->rewriteWPINC = $_POST['rewriteWPINC'];

        $this->allowOfflineUsage =
            isset( $_POST['allowOfflineUsage'] ) ?
            $_POST['allowOfflineUsage'] :
            false;
        $this->useRelativeURLs =
            isset( $_POST['useRelativeURLs'] ) ?
            $_POST['useRelativeURLs'] :
            false;
        $this->useBaseHref =
            isset( $_POST['useBaseHref'] ) ?
            $_POST['useBaseHref'] :
            false;
        $this->crawl_increment = (int) $_POST['crawl_increment'];
        $this->additionalUrls = filter_input( INPUT_POST, 'additionalUrls' );

        // internal pointers
        $this->processed_file = '';
        $this->file_type = '';
        $this->response = '';
        $this->content_type = '';
        $this->url = '';
        $this->extension = '';
        $this->archive_dir = '';
        $this->list_of_urls_to_crawl_path = '';
        $this->urls_to_crawl = '';

        $this->discoverNewURLs =
        isset( $_POST['discoverNewURLs'] ) ?
        $_POST['discoverNewURLs'] :
        false;

        // crawl links discovered in first run
        if ( $_POST['ajax_action'] === 'crawl_again' ) {
            if ( ! $this->discoverNewURLs ) {
                echo 'SUCCESS';
                die();
            }

            $second_crawl_file_path = $this->working_directory .
            '/WP-STATIC-2ND-CRAWL-LIST';

            if ( ! is_file( $second_crawl_file_path ) ) {

                // TODO: read in WP-STATIC-FINAL-CRAWL-LIST clone vs INITIAL
                $already_crawled = file(
                    $this->working_directory . '/WP-STATIC-INITIAL-CRAWL-LIST',
                    FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
                );

                // read WP-STATIC-DISCOVERED-URLS into $discovered_links
                $discovered_links = file(
                    $this->working_directory . '/WP-STATIC-DISCOVERED-URLS',
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
                    $this->working_directory .
                        '/WP-STATIC-FINAL-2ND-CRAWL-LIST'
                );
            }

            $this->list_of_urls_to_crawl_path = $this->working_directory .
            '/WP-STATIC-FINAL-2ND-CRAWL-LIST';
        } else {
            $this->list_of_urls_to_crawl_path = $this->working_directory .
            '/WP-STATIC-FINAL-CRAWL-LIST';
        }

        $this->viaCLI = false;

        $this->crawl_site();
    }

    public function crawl_site( $viaCLI = false ) {
        if ( ! is_file( $this->list_of_urls_to_crawl_path ) ) {
            error_log( 'could not find list of files to crawl' );
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
            error_log(
                'list of URLs to crawl not found at ' .
                $this->list_of_urls_to_crawl_path
            );
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
            WsLog::l(
                'ERROR: LIST OF URLS TO CRAWL NOT FOUND AT: ' .
                $this->list_of_urls_to_crawl_path
            );
            die();
        }

        if ( $this->crawl_increment > $total_links ) {
            $this->crawl_increment = $total_links;
        }

        for ( $i = 0; $i < $this->crawl_increment; $i++ ) {
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
            $this->wp_uploads_path . '/WP-STATIC-CURRENT-ARCHIVE',
            'r'
        );
        $this->archive_dir = stream_get_line( $handle, 0 );

        foreach ( $batch_of_links_to_crawl as $link_to_crawl ) {
            $this->url = $link_to_crawl;

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
        );

        if ( $this->useBasicAuth ) {
            $request_options['auth'] = array(
                $this->basicAuthUser,
                $this->basicAuthPassword,
            );
        }

        $this->response =
            $client->request( 'GET', $this->url, $request_options );
        $this->crawled_links_file =
            $this->working_directory . '/WP-STATIC-CRAWLED-LINKS';

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

        // TODO: what difference between this and $this->baseUrl originally?
        $baseUrl = $this->baseUrl;

        $wp_site_environment = array(
            'wp_inc' => '/' . WPINC,
            // TODO: use reliable method for getting wp-content
            'wp_content' => '/wp-content',
            'wp_uploads' =>
                str_replace( ABSPATH, '/', $this->wp_uploads_path ),
            'wp_plugins' => str_replace( ABSPATH, '/', WP_PLUGIN_DIR ),
            'wp_themes' => str_replace( ABSPATH, '/', get_theme_root() ),
            'wp_active_theme' =>
                str_replace( home_url(), '', get_template_directory_uri() ),
            'site_url' => $this->wp_site_url,
        );

        $new_wp_content = '/' . $this->rewriteWPCONTENT;
        $new_theme_root = $new_wp_content . '/' . $this->rewriteTHEMEROOT;
        $new_theme_dir = $new_theme_root . '/' . $this->rewriteTHEMEDIR;
        $new_uploads_dir = $new_wp_content . '/' . $this->rewriteUPLOADS;
        $new_plugins_dir = $new_wp_content . '/' . $this->rewritePLUGINDIR;

        $overwrite_slug_targets = array(
            'new_wp_content_path' => $new_wp_content,
            'new_themes_path' => $new_theme_root,
            'new_active_theme_path' => $new_theme_dir,
            'new_uploads_path' => $new_uploads_dir,
            'new_plugins_path' => $new_plugins_dir,
            'new_wpinc_path' => '/' . $this->rewriteWPINC,
        );

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
                    $this->url,
                    $wp_site_environment,
                    $overwrite_slug_targets,
                    $this->wp_site_url,
                    $this->baseUrl,
                    $this->allowOfflineUsage,
                    $this->useRelativeURLs,
                    $this->useBaseHref
                );

                $this->processed_file = $processor->getHTML();

                break;

            // TODO: apply other replacement functions to all processors
            case 'css':
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/CSSProcessor.php';
                $processor = new CSSProcessor(
                    $this->response->getBody(),
                    $this->wp_site_url
                );

                $processor->normalizeURLs( $this->url );

                $this->processed_file = $processor->getCSS();

                break;

            case 'js':
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/JSProcessor.php';
                $processor = new JSProcessor(
                    $this->response->getBody(),
                    $this->wp_site_url
                );

                $processor->normalizeURLs( $this->url );

                $this->processed_file = $processor->getJS();

                break;

            case 'txt':
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/TXTProcessor.php';
                $processor = new TXTProcessor(
                    $this->response->getBody(),
                    $this->wp_site_url
                );

                $processor->normalizeURLs( $this->url );

                $this->processed_file = $processor->getTXT();

                break;

            case 'rss':
                error_log( 'no handler for rss without extension yet' );

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
            $this->wp_site_url,
            $this->wp_site_path
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
            'css',
            'js',
            'json',
            'xml',
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
            $this->content_type =
                $this->response->getHeaderLine( 'content-type' );

            if ( stripos( $this->content_type, 'text/html' ) !== false ) {
                $this->file_type = 'html';
            } elseif ( stripos( $this->content_type, 'rss+xml' ) !== false ) {
                $this->file_type = 'rss';
            } else {
                error_log( 'no filetype inferred from content-type:' );
                error_log( $this->response->getHeaderLine( 'content-type' ) );
            }
        }
    }
}

$site_crawler = new SiteCrawler();

