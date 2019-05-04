<?php

namespace WP2Static;

use Exception;

class CSSProcessor extends Base {

    public function __construct() {
        $this->loadSettings(
            array(
                'crawling',
                'wpenv',
                'processing',
                'advanced',
            )
        );
    }

    public function processCSS( $css_document, $page_url ) {
        if ( $css_document == '' ) {
            return false;
        }

        $protocol = $this->getTargetSiteProtocol( $this->settings['baseUrl'] );

        $this->placeholder_url = $protocol . 'PLACEHOLDER.wpsho/';

        $this->raw_css = $css_document;
        // initial rewrite of all site URLs to placeholder URLs
        $this->rewriteSiteURLsToPlaceholder();

        $css_parser = new \Sabberworm\CSS\Parser( $this->raw_css );
        $this->css_doc = $css_parser->parse();

        $this->page_url = new \Net_URL2( $page_url );

        $this->detectIfURLsShouldBeHarvested();

        $this->discovered_urls = array();

        foreach ( $this->css_doc->getAllValues() as $node_value ) {
            if ( $node_value instanceof \Sabberworm\CSS\Value\URL ) {
                $original_link = $node_value->getURL();

                $original_link = trim( trim( $original_link, "'" ), '"' );

                $inline_img =
                    strpos( $original_link, 'data:image' );

                if ( $inline_img !== false ) {
                    continue;
                }

                $this->addDiscoveredURL( $original_link );

                if ( $this->isInternalLink( $original_link ) ) {
                    if ( ! isset( $this->settings['rewrite_rules'] ) ) {
                        $this->settings['rewrite_rules'] = '';
                    }

                    // add base URL to rewrite_rules
                    $this->settings['rewrite_rules'] .=
                        PHP_EOL .
                            $this->placeholder_url . ',' .
                            $this->settings['baseUrl'];

                    $rewrite_from = array();
                    $rewrite_to = array();

                    $rewrite_rules = explode(
                        "\n",
                        str_replace(
                            "\r",
                            '',
                            $this->settings['rewrite_rules']
                        )
                    );

                    $tmp_rules = array();

                    foreach ( $rewrite_rules as $rewrite_rule_line ) {
                        if ( $rewrite_rule_line ) {
                            list($from, $to) =
                                explode( ',', $rewrite_rule_line );

                            $tmp_rules[ $from ] = $to;
                        }
                    }

                    uksort( $tmp_rules, array( $this, 'ruleSort' ) );

                    foreach ( $tmp_rules as $from => $to ) {
                        $rewrite_from[] = $from;
                        $rewrite_to[] = $to;
                    }

                    $rewritten_url = str_replace(
                        $rewrite_from,
                        $rewrite_to,
                        $original_link
                    );

                    $rewritten_url = new \Sabberworm\CSS\Value\CSSString(
                        $rewritten_url
                    );

                    $node_value->setURL( $rewritten_url );
                }
            }
        }

        $this->writeDiscoveredURLs();

        return true;
    }

    public function isInternalLink( $link, $domain = false ) {
        if ( ! $domain ) {
            $domain = $this->placeholder_url;
        }

        $is_internal_link = parse_url( $link, PHP_URL_HOST ) === parse_url(
            $domain,
            PHP_URL_HOST
        );

        return $is_internal_link;
    }

    public function getCSS() {
        return $this->css_doc->render();
    }

    public function rewriteSiteURLsToPlaceholder() {
        $rewritten_source = str_replace(
            array(
                $this->settings['wp_site_url'],
                addcslashes( $this->settings['wp_site_url'], '/' ),
            ),
            array(
                $this->placeholder_url,
                addcslashes( $this->placeholder_url, '/' ),
            ),
            $this->raw_css
        );

        $this->raw_css = $rewritten_source;
    }

    public function detectIfURLsShouldBeHarvested() {
        if ( defined( 'WP_CLI' ) ) {
            if ( defined( 'CRAWLING_DISCOVERED' ) ) {
                return;
            } else {
                $this->harvest_new_urls = true;
            }
        } else {
            // @codingStandardsIgnoreStart
            $this->harvest_new_urls = (
                 $_POST['ajax_action'] === 'crawl_site'
            );
            // @codingStandardsIgnoreEnd
        }
    }

    public function addDiscoveredURL( $url ) {
        if ( ! $url ) { return; }

        // trim any query strings or anchors
        $url = strtok( $url, '#' );

        if ( ! $url ) { return; }

        $url = strtok( $url, '?' );

        if ( ! $url ) { return; }

        if ( trim( $url ) === '' ) {
            return;
        }

        if ( isset( $this->harvest_new_urls ) ) {
            if ( ! $this->isValidURL( $url ) ) {
                return;
            }

            if ( $this->isInternalLink( $url ) ) {
                $discovered_url_without_site_url =
                    str_replace(
                        rtrim( $this->placeholder_url, '/' ),
                        '',
                        $url
                    );

                $this->discovered_urls[] = $discovered_url_without_site_url;
            }
        }
    }

    public function writeDiscoveredURLs() {
        // @codingStandardsIgnoreStart
        if ( isset( $_POST['ajax_action'] ) &&
            $_POST['ajax_action'] === 'crawl_again' ) {
            return;
        }
        // @codingStandardsIgnoreEnd

        if ( defined( 'WP_CLI' ) ) {
            if ( defined( 'CRAWLING_DISCOVERED' ) ) {
                return;
            }
        }

        file_put_contents(
            $this->settings['wp_uploads_path'] .
                '/wp2static-working-files/DISCOVERED-URLS.txt',
            PHP_EOL .
                implode( PHP_EOL, array_unique( $this->discovered_urls ) ),
            FILE_APPEND | LOCK_EX
        );

        chmod(
            $this->settings['wp_uploads_path'] .
                '/wp2static-working-files/DISCOVERED-URLS.txt',
            0664
        );
    }

    public function isValidURL( $url ) {
        // NOTE: not using native URL filter as it won't accept
        // non-ASCII URLs, which we want to support
        $url = trim( $url );

        if ( $url == '' ) {
            return false;
        }

        if ( strpos( $url, '.php' ) !== false ) {
            return false;
        }

        if ( strpos( $url, ' ' ) !== false ) {
            return false;
        }

        if ( $url[0] == '#' ) {
            return false;
        }

        return true;
    }

    public function getTargetSiteProtocol( $url ) {
        $protocol = '//';

        if ( strpos( $url, 'https://' ) !== false ) {
            $protocol = 'https://';
        } elseif ( strpos( $url, 'http://' ) !== false ) {
            $protocol = 'http://';
        } else {
            $protocol = '//';
        }

        return $protocol;
    }
}

