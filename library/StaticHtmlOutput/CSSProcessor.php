<?php

class CSSProcessor {

    public function processCSS( $css_document, $page_url ) {
        if ( $css_document == '' ) {
            return false;
        }

        $this->target_settings = array(
            'general',
            'crawling',
            'wpenv',
            'processing',
            'advanced',
        );

        $this->loadSettings();

        $this->discoverNewURLs = (
            isset( $this->settings['discoverNewURLs'] ) &&
             $this->settings['discoverNewURLs'] == 1 &&
             $_POST['ajax_action'] === 'crawl_site'
        );

        // parse CSS into easily modifiable form
        $path = dirname( __FILE__ ) . '/../CSSParser/';
        require_once $path . 'Parser.php';
        require_once $path . 'Settings.php';
        require_once $path . 'Renderable.php';
        require_once $path . 'OutputFormat.php';
        require_once $path . 'Comment/Comment.php';
        require_once $path . 'Comment/Commentable.php';
        require_once $path . 'Parsing/SourceException.php';
        require_once $path . 'Parsing/OutputException.php';
        require_once $path . 'Parsing/UnexpectedTokenException.php';
        require_once $path . 'Property/AtRule.php';
        require_once $path . 'Property/Charset.php';
        require_once $path . 'Property/CSSNamespace.php';
        require_once $path . 'Property/Import.php';
        require_once $path . 'Property/Selector.php';
        require_once $path . 'RuleSet/RuleSet.php';
        require_once $path . 'Rule/Rule.php';
        require_once $path . 'RuleSet/AtRuleSet.php';
        require_once $path . 'RuleSet/DeclarationBlock.php';
        require_once $path . 'Value/Value.php';
        require_once $path . 'Value/ValueList.php';
        require_once $path . 'Value/RuleValueList.php';
        require_once $path . 'Value/CSSFunction.php';
        require_once $path . 'Value/CalcFunction.php';
        require_once $path . 'Value/CalcRuleValueList.php';
        require_once $path . 'Value/PrimitiveValue.php';
        require_once $path . 'Value/Color.php';
        require_once $path . 'Value/CSSString.php';
        require_once $path . 'Value/LineName.php';
        require_once $path . 'Value/Size.php';
        require_once $path . 'Value/URL.php';
        require_once $path . 'CSSList/CSSList.php';
        require_once $path . 'CSSList/CSSBlockList.php';
        require_once $path . 'CSSList/AtRuleBlockList.php';
        require_once $path . 'CSSList/Document.php';
        require_once $path . 'CSSList/KeyFrame.php';

        $this->placeholder_URL = 'https://PLACEHOLDER.wpsho/';
        $this->raw_css = $css_document;
        // initial rewrite of all site URLs to placeholder URLs
        $this->rewriteSiteURLsToPlaceholder();

        $oCssParser = new Sabberworm\CSS\Parser( $this->raw_css );
        $this->css_doc = $oCssParser->parse();

        require_once dirname( __FILE__ ) . '/../URL2/URL2.php';
        $this->page_url = new Net_URL2( $page_url );

        $this->detectIfURLsShouldBeHarvested();

        $this->discovered_urls = [];

        foreach ( $this->css_doc->getAllValues() as $mValue ) {
            if ( $mValue instanceof Sabberworm\CSS\Value\URL ) {
                $original_link = $mValue->getURL();

                // TODO: benchmark trim vs str_replace
                // returned value contains surrounding quotes
                $original_link = trim( trim( $original_link, "'" ), '"' );

                $this->addDiscoveredURL( $original_link );

                if ( $this->isInternalLink( $original_link ) ) {
                    if ( ! isset( $this->settings['rewrite_rules'] ) ) {
                        $this->settings['rewrite_rules'] = '';
                    }

                    // add base URL to rewrite_rules
                    $this->settings['rewrite_rules'] .=
                        PHP_EOL .
                            $this->placeholder_URL . ',' .
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

                    foreach ( $rewrite_rules as $rewrite_rule_line ) {
                        if ( $rewrite_rule_line ) {
                            list($from, $to) =
                                explode( ',', $rewrite_rule_line );

                            $rewrite_from[] = $from;
                            $rewrite_to[] = $to;
                        }
                    }

                    $rewritten_url = str_replace(
                        $rewrite_from,
                        $rewrite_to,
                        $original_link
                    );

                    $rewritten_url = new Sabberworm\CSS\Value\CSSString(
                        $rewritten_url
                    );

                    $mValue->setURL( $rewritten_url );
                }
            }
        }

        $this->writeDiscoveredURLs();

        return true;
    }

    public function loadSettings() {
        if ( isset( $_POST['selected_deployment_option'] ) ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $this->target_settings );
        } else {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/DBSettings.php';
            $this->settings = WPSHO_DBSettings::get( $this->target_settings );
        }
    }

    public function isInternalLink( $link, $domain = false ) {
        if ( ! $domain ) {
            $domain = $this->placeholder_URL;
        }

        // TODO: apply only to links starting with .,..,/,
        // or any with just a path, like banana.png
        // check link is same host as $this->url and not a subdomain
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
                $this->placeholder_URL,
                addcslashes( $this->placeholder_URL, '/' ),
            ),
            $this->raw_css
        );

        $this->raw_css = $rewritten_source;

    }

    public function detectIfURLsShouldBeHarvested() {
        if ( ! defined( 'WP_CLI' ) ) {
            $this->harvest_new_URLs = (
                 $_POST['ajax_action'] === 'crawl_site'
            );
        } else {
            // we shouldn't harvest any while we're in the second crawl
            if ( defined( 'CRAWLING_DISCOVERED' ) ) {
                return;
            } else {
                $this->harvest_new_URLs = true;
            }
        }
    }

    public function addDiscoveredURL( $url ) {
        // trim any query strings or anchors
        $url = strtok( $url, '#' );
        $url = strtok( $url, '?' );

        if ( trim( $url ) === '' ) {
            return;
        }

        if ( isset( $this->harvest_new_URLs ) ) {
            if ( ! $this->isValidURL( $url ) ) {
                return;
            }

            if ( $this->isInternalLink( $url ) ) {
                $discovered_URL_without_site_URL =
                    str_replace(
                        'https://PLACEHOLDER.wpsho',
                        '',
                        $url
                    );

                $this->discovered_urls[] = $discovered_URL_without_site_URL;
            }
        }
    }

    public function writeDiscoveredURLs() {
        if ( isset( $_POST['ajax_action'] ) &&
            $_POST['ajax_action'] === 'crawl_again' ) {
            return;
        }

        if ( defined( 'WP_CLI' ) ) {
            if ( defined( 'CRAWLING_DISCOVERED' ) ) {
                return;
            }
        }

        file_put_contents(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-DISCOVERED-URLS.txt',
            PHP_EOL .
                implode( PHP_EOL, array_unique( $this->discovered_urls ) ),
            FILE_APPEND | LOCK_EX
        );

        chmod(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-DISCOVERED-URLS.txt',
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
}

