<?php

class HTMLProcessor extends WP2Static {

    public function __construct() {
        $this->loadSettings(
            array(
                'github',
                'wpenv',
                'processing',
                'advanced',
            )
        );
    }

    public function processHTML( $html_document, $page_url ) {
        if ( $html_document == '' ) {
            return false;
        }

        // instantiate the XML body here
        $this->xml_doc = new DOMDocument();
        $this->raw_html = $html_document;

        // NOTE: set placeholder_url to same protocol as target
        // making it easier to rewrite URLs without considering protocol
        $protocol = $this->getTargetSiteProtocol( $this->settings['baseUrl'] );

        $this->placeholder_url = $protocol . 'PLACEHOLDER.wpsho/';

        // initial rewrite of all site URLs to placeholder URLs
        $this->rewriteSiteURLsToPlaceholder();

        // detect if a base tag exists while in the loop
        // use in later base href creation to decide: append or create
        $this->base_tag_exists = false;

        require_once dirname( __FILE__ ) . '/../URL2/URL2.php';
        $this->page_url = new Net_url2( $page_url );

        $this->detectIfURLsShouldBeHarvested();

        $this->discovered_urls = [];

        // PERF: 70% of function time
        // prevent warnings, via https://stackoverflow.com/a/9149241/1668057
        libxml_use_internal_errors( true );
        $this->xml_doc->loadHTML( $this->raw_html );
        libxml_use_internal_errors( false );

        // start the full iterator here, along with copy of dom
        $elements = iterator_to_array(
            $this->xml_doc->getElementsByTagName( '*' )
        );

        foreach ( $elements as $element ) {
            switch ( $element->tagName ) {
                case 'meta':
                    $this->processMeta( $element );
                    break;
                case 'a':
                    $this->processAnchor( $element );
                    break;
                case 'img':
                    $this->processImage( $element );
                    break;
                case 'head':
                    $this->processHead( $element );
                    break;
                case 'link':
                    // NOTE: not to confuse with anchor element
                    $this->processLink( $element );
                    break;
                case 'script':
                    // can contain src=,
                    // can also contain URLs within scripts
                    // and escaped urls
                    $this->processScript( $element );
                    break;

                    // TODO: how about other places that can contain URLs
                    // data attr, reacty stuff, etc?
            }
        }

        if ( $this->base_tag_exists ) {
            $base_element =
                $this->xml_doc->getElementsByTagName( 'base' )->item( 0 );

            if ( $this->shouldCreateBaseHREF() ) {
                $base_element->setAttribute(
                    'href',
                    $this->settings['baseHREF']
                );
            } else {
                $base_element->parentNode->removeChild( $base_element );
            }
        } elseif ( $this->shouldCreateBaseHREF() ) {
            $base_element = $this->xml_doc->createElement( 'base' );
            $base_element->setAttribute(
                'href',
                $this->settings['baseHREF']
            );
            $head_element =
                $this->xml_doc->getElementsByTagName( 'head' )->item( 0 );
            if ( $head_element ) {
                $first_head_child = $head_element->firstChild;
                $head_element->insertBefore(
                    $base_element,
                    $first_head_child
                );
            } else {
                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/WsLog.php';
                WsLog::l(
                    'WARNING: no valid head elemnent to attach base to: ' .
                        $this->page_url
                );
            }
        }

        // strip comments
        $this->stripHTMLComments();

        $this->writeDiscoveredURLs();

        return true;
    }

    public function detectIfURLsShouldBeHarvested() {
        if ( ! defined( 'WP_CLI' ) ) {
            // @codingStandardsIgnoreStart
            $this->harvest_new_urls = (
                 $_POST['ajax_action'] === 'crawl_site'
            );
            // @codingStandardsIgnoreEnd
        } else {
            // we shouldn't harvest any while we're in the second crawl
            if ( defined( 'CRAWLING_DISCOVERED' ) ) {
                return;
            } else {
                $this->harvest_new_urls = true;
            }
        }
    }

    public function processLink( $element ) {
        $this->normalizeURL( $element, 'href' );
        $this->removeQueryStringFromInternalLink( $element );
        $this->addDiscoveredURL( $element->getAttribute( 'href' ) );
        $this->rewriteWPPaths( $element );
        $this->rewriteBaseURL( $element );
        $this->convertToRelativeURL( $element );
        $this->convertToOfflineURL( $element );

        if ( isset( $this->settings['removeWPLinks'] ) ) {
            $relative_links_to_rm = array(
                'shortlink',
                'canonical',
                'pingback',
                'alternate',
                'EditURI',
                'wlwmanifest',
                'index',
                'profile',
                'prev',
                'next',
                'wlwmanifest',
            );

            $link_rel = $element->getAttribute( 'rel' );

            if ( in_array( $link_rel, $relative_links_to_rm ) ) {
                $element->parentNode->removeChild( $element );
            } elseif ( strpos( $link_rel, '.w.org' ) !== false ) {
                $element->parentNode->removeChild( $element );
            }
        }
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

    public function addDiscoveredURL( $url ) {
        // trim any query strings or anchors
        $url = strtok( $url, '#' );
        $url = strtok( $url, '?' );

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

    public function processImage( $element ) {
        $this->normalizeURL( $element, 'src' );
        $this->removeQueryStringFromInternalLink( $element );
        $this->addDiscoveredURL( $element->getAttribute( 'src' ) );
        $this->rewriteWPPaths( $element );
        $this->rewriteBaseURL( $element );
        $this->convertToRelativeURL( $element );
        $this->convertToOfflineURL( $element );
    }

    public function stripHTMLComments() {
        if ( isset( $this->settings['removeHTMLComments'] ) ) {
            $xpath = new DOMXPath( $this->xml_doc );

            foreach ( $xpath->query( '//comment()' ) as $comment ) {
                $comment->parentNode->removeChild( $comment );
            }
        }
    }

    public function processHead( $element ) {
        $head_elements = iterator_to_array(
            $element->childNodes
        );

        foreach ( $head_elements as $node ) {
            if ( $node instanceof DOMComment ) {
                if (
                    isset( $this->settings['removeConditionalHeadComments'] )
                ) {
                    $node->parentNode->removeChild( $node );
                }
            } elseif ( isset( $node->tagName ) ) {
                if ( $node->tagName === 'base' ) {
                    // as smaller iteration to run conditional
                    // against here
                    $this->base_tag_exists = true;
                }
            }
        }
    }

    public function processScript( $element ) {
        $this->normalizeURL( $element, 'src' );
        $this->removeQueryStringFromInternalLink( $element );
        $this->addDiscoveredURL( $element->getAttribute( 'src' ) );
        $this->rewriteWPPaths( $element );
        $this->rewriteBaseURL( $element );
        $this->convertToRelativeURL( $element );
        $this->convertToOfflineURL( $element );
    }

    public function processAnchor( $element ) {
        $url = $element->getAttribute( 'href' );

        // TODO: DRY this up/move to higher exec position
        // early abort invalid links as early as possible
        // to save overhead/potential errors
        // apply to other functions
        if ( $url[0] === '#' ) {
            return;
        }

        if ( substr( $url, 0, 7 ) == 'mailto:' ) {
            return;
        }

        if ( ! $this->isInternalLink( $url ) ) {
            return;
        }

        $this->normalizeURL( $element, 'href' );
        $this->removeQueryStringFromInternalLink( $element );
        $this->addDiscoveredURL( $url );
        $this->rewriteWPPaths( $element );
        $this->rewriteBaseURL( $element );
        $this->convertToRelativeURL( $element );
        $this->convertToOfflineURL( $element );
    }

    public function processMeta( $element ) {
        // TODO: detect meta redirects here + build list for rewriting
        if ( isset( $this->settings['removeWPMeta'] ) ) {
            $meta_name = $element->getAttribute( 'name' );

            if ( strpos( $meta_name, 'generator' ) !== false ) {
                $element->parentNode->removeChild( $element );

                return;
            }

            if ( strpos( $meta_name, 'robots' ) !== false ) {
                $content = $element->getAttribute( 'content' );

                if ( strpos( $content, 'noindex' ) !== false ) {
                    $element->parentNode->removeChild( $element );
                }
            }
        }

        $url = $element->getAttribute( 'content' );
        $this->normalizeURL( $element, 'content' );
        $this->removeQueryStringFromInternalLink( $element );
        $this->addDiscoveredURL( $url );
        $this->rewriteWPPaths( $element );
        $this->rewriteBaseURL( $element );
        $this->convertToRelativeURL( $element );
        $this->convertToOfflineURL( $element );
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

    // make link absolute, using current page to determine full path
    public function normalizeURL( $element, $attribute ) {
        $original_link = $element->getAttribute( $attribute );

        if ( $this->isInternalLink( $original_link ) ) {
            $abs = $this->page_url->resolve( $original_link );
            $element->setAttribute( $attribute, $abs );
        }

    }

    public function isInternalLink( $link, $domain = false ) {
        if ( ! $domain ) {
            $domain = $this->placeholder_url;
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

    public function removeQueryStringFromInternalLink( $element ) {
        $attribute_to_change = '';
        $url_to_change = '';

        if ( $element->hasAttribute( 'href' ) ) {
            $attribute_to_change = 'href';
        } elseif ( $element->hasAttribute( 'src' ) ) {
            $attribute_to_change = 'src';
        } elseif ( $element->hasAttribute( 'content' ) ) {
            $attribute_to_change = 'content';
        } else {
            return;
        }

        $url_to_change = $element->getAttribute( $attribute_to_change );

        if ( $this->isInternalLink( $url_to_change ) ) {
            // strip anything from the ? onwards
            // https://stackoverflow.com/a/42476194/1668057
            $element->setAttribute(
                $attribute_to_change,
                strtok( $url_to_change, '?' )
            );
        }
    }

    public function detectEscapedSiteURLs( $processed_html ) {
        // NOTE: this does return the expected http:\/\/172.18.0.3
        // but your error log may escape again and
        // show http:\\/\\/172.18.0.3
        $escaped_site_url = addcslashes( $this->placeholder_url, '/' );

        if ( strpos( $processed_html, $escaped_site_url ) !== false ) {
            return $this->rewriteEscapedURLs( $processed_html );
        }

        return $processed_html;
    }

    public function detectUnchangedURLs( $processed_html ) {
        $site_url = $this->placeholder_url;

        if ( strpos( $processed_html, $site_url ) !== false ) {
            return $this->rewriteUnchangedURLs( $processed_html );
        }

        return $processed_html;
    }

    public function rewriteUnchangedURLs( $processed_html ) {
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
            str_replace( "\r", '', $this->settings['rewrite_rules'] )
        );

        foreach ( $rewrite_rules as $rewrite_rule_line ) {
            if ( $rewrite_rule_line ) {
                list($from, $to) = explode( ',', $rewrite_rule_line );

                $rewrite_from[] = $from;
                $rewrite_to[] = $to;
            }
        }

        $rewritten_source = str_replace(
            $rewrite_from,
            $rewrite_to,
            $processed_html
        );

        return $rewritten_source;
    }

    public function rewriteEscapedURLs( $processed_html ) {
        // NOTE: fix input HTML, which can have \ slashes modified to %5C
        $processed_html = str_replace(
            '%5C/',
            '\\/',
            $processed_html
        );

        /*
        This function will be a bit more costly. To cover bases like:

         data-images="[&quot;https:\/\/mysite.example.com\/wp...
        from the onepress(?) theme, for example

        */
        $site_url = addcslashes( $this->placeholder_url, '/' );
        $destination_url = addcslashes( $this->settings['baseUrl'], '/' );

        if ( ! isset( $this->settings['rewrite_rules'] ) ) {
            $this->settings['rewrite_rules'] = '';
        }

        // add base URL to rewrite_rules
        $this->settings['rewrite_rules'] .=
            PHP_EOL .
                $site_url . ',' .
                $destination_url;

        $rewrite_from = array();
        $rewrite_to = array();

        $rewrite_rules = explode(
            "\n",
            str_replace( "\r", '', $this->settings['rewrite_rules'] )
        );

        foreach ( $rewrite_rules as $rewrite_rule_line ) {
            if ( $rewrite_rule_line ) {
                list($from, $to) = explode( ',', $rewrite_rule_line );

                $rewrite_from[] = addcslashes( $from, '/' );
                $rewrite_to[] = addcslashes( $to, '/' );
            }
        }

        $rewritten_source = str_replace(
            $rewrite_from,
            $rewrite_to,
            $processed_html
        );

        return $rewritten_source;

    }

    public function rewriteWPPaths( $element ) {
        if ( ! isset( $this->settings['rewrite_rules'] ) ) {
            return;
        }

        $rewrite_from = array();
        $rewrite_to = array();

        $rewrite_rules = explode(
            "\n",
            str_replace( "\r", '', $this->settings['rewrite_rules'] )
        );

        foreach ( $rewrite_rules as $rewrite_rule_line ) {
            list($from, $to) = explode( ',', $rewrite_rule_line );

            $rewrite_from[] = $from;
            $rewrite_to[] = $to;
        }

        // array of: wp-content/themes/twentyseventeen/,contents/ui/theme/
        // for each of these, addd the rewrite_from and rewrite_to to their
        // respective arrays
        $attribute_to_change = '';
        $url_to_change = '';

        if ( $element->hasAttribute( 'href' ) ) {
            $attribute_to_change = 'href';
        } elseif ( $element->hasAttribute( 'src' ) ) {
            $attribute_to_change = 'src';
        } elseif ( $element->hasAttribute( 'content' ) ) {
            $attribute_to_change = 'content';
        } else {
            return;
        }

        $url_to_change = $element->getAttribute( $attribute_to_change );

        if ( $this->isInternalLink( $url_to_change ) ) {
            // rewrite URLs, starting with longest paths down to shortest
            // TODO: is the internal link check needed here or these
            // arr values are already normalized?
            $rewritten_url = str_replace(
                $rewrite_from,
                $rewrite_to,
                $url_to_change
            );

            $element->setAttribute( $attribute_to_change, $rewritten_url );
        }
    }

    public function getHTML() {
        $processed_html = $this->xml_doc->saveHtml();

        // process the resulting HTML as text
        $processed_html = $this->detectEscapedSiteURLs( $processed_html );
        $processed_html = $this->detectUnchangedURLs( $processed_html );

        $processed_html = html_entity_decode(
            $processed_html,
            ENT_QUOTES,
            'UTF-8'
        );

        // Note: double-decoding to be safe
        $processed_html = html_entity_decode(
            $processed_html,
            ENT_QUOTES,
            'UTF-8'
        );

        return $processed_html;
    }

    public function convertToRelativeURL( $element ) {
        if ( ! $this->shouldUseRelativeURLs() ) {
            return;
        }

        if ( $element->hasAttribute( 'href' ) ) {
            $attribute_to_change = 'href';
        } elseif ( $element->hasAttribute( 'src' ) ) {
            $attribute_to_change = 'src';
        } elseif ( $element->hasAttribute( 'content' ) ) {
            $attribute_to_change = 'content';
        } else {
            return;
        }

        $url_to_change = $element->getAttribute( $attribute_to_change );

        $site_root = '';

        // check it actually needs to be changed
        if ( $this->isInternalLink(
            $url_to_change,
            $this->settings['baseUrl']
        ) ) {
            $rewritten_url = str_replace(
                $this->settings['baseUrl'],
                $site_root,
                $url_to_change
            );

            $element->setAttribute( $attribute_to_change, $rewritten_url );
        }
    }

    public function getDotsBackToRoot( $url ) {
        $dots_path = '';

        return $dots_path;
    }

    public function convertToOfflineURL( $element ) {
        if ( ! isset( $this->settings['allowOfflineUsage'] ) ) {
            return;
        }

        if ( $element->hasAttribute( 'href' ) ) {
            $attribute_to_change = 'href';
        } elseif ( $element->hasAttribute( 'src' ) ) {
            $attribute_to_change = 'src';
        } elseif ( $element->hasAttribute( 'content' ) ) {
            $attribute_to_change = 'content';
        } else {
            return;
        }

        $url_to_change = $element->getAttribute( $attribute_to_change );
        $current_page_path_to_root = '';
        $current_page_path = parse_url( $this->page_url, PHP_URL_PATH );
        $number_of_segments_in_path = explode( '/', $current_page_path );
        $num_dots_to_root = count( $number_of_segments_in_path ) - 2;

        for ( $i = 0; $i < $num_dots_to_root; $i++ ) {
            $current_page_path_to_root .= '../';
        }

        if ( $this->isInternalLink(
            $url_to_change,
            $this->settings['baseUrl']
        ) ) {
            $rewritten_url = str_replace(
                $this->settings['baseUrl'],
                '',
                $url_to_change
            );

            $offline_url = $current_page_path_to_root . $rewritten_url;

            // add index.html if no extension
            if ( substr( $offline_url, -1 ) === '/' ) {
                // TODO: check XML/RSS case
                $offline_url .= 'index.html';
            }

            $element->setAttribute( $attribute_to_change, $offline_url );
        }
    }

    // TODO: move some of these URLs into settings to avoid extra calls
    public function getProtocolRelativeURL( $url ) {
        $protocol_relative_url = str_replace(
            array(
                'https:',
                'http:',
            ),
            array(
                '',
                '',
            ),
            $url
        );

        return $protocol_relative_url;
    }

    public function rewriteBaseURL( $element ) {
        if ( $element->hasAttribute( 'href' ) ) {
            $attribute_to_change = 'href';
        } elseif ( $element->hasAttribute( 'src' ) ) {
            $attribute_to_change = 'src';
        } elseif ( $element->hasAttribute( 'content' ) ) {
            $attribute_to_change = 'content';
        } else {
            return;
        }

        $url_to_change = $element->getAttribute( $attribute_to_change );

        // check it actually needs to be changed
        if ( $this->isInternalLink( $url_to_change ) ) {
            $rewritten_url = str_replace(
                // TODO: test this won't touch subdomains, shouldn't
                $this->getProtocolRelativeURL( $this->placeholder_url ),
                $this->getProtocolRelativeURL( $this->settings['baseUrl'] ),
                $url_to_change
            );

            $element->setAttribute( $attribute_to_change, $rewritten_url );
        }
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

    public function rewriteSiteURLsToPlaceholder() {
        $rewritten_source = str_replace(
            array(
                $this->getProtocolRelativeURL(
                    $this->settings['wp_site_url']
                ),
                $this->getProtocolRelativeURL(
                    rtrim( $this->settings['wp_site_url'], '/' )
                ),
                $this->getProtocolRelativeURL(
                    $this->placeholder_url . '//'
                ),
                $this->getProtocolRelativeURL(
                    addcslashes( $this->settings['wp_site_url'], '/' )
                ),
            ),
            array(
                $this->getProtocolRelativeURL(
                    $this->placeholder_url
                ),
                $this->getProtocolRelativeURL(
                    $this->placeholder_url
                ),
                $this->getProtocolRelativeURL(
                    $this->placeholder_url . '/'
                ),
                $this->getProtocolRelativeURL(
                    addcslashes( $this->placeholder_url, '/' )
                ),
            ),
            $this->raw_html
        );

        $this->raw_html = $rewritten_source;

    }

    public function shouldUseRelativeURLs() {
        if ( ! isset( $this->settings['useRelativeURLs'] ) ) {
            return;
        }

        // NOTE: relative URLs should not be used when creating an offline ZIP
        if ( isset( $this->settings['allowOfflineUsage'] ) ) {
            return false;
        }
    }

    public function shouldCreateBaseHREF() {
        if ( empty( $this->settings['baseHREF'] ) ) {
            return false;
        }

        // NOTE: base HREF should not be set when creating an offline ZIP
        if ( isset( $this->settings['allowOfflineUsage'] ) ) {
            return false;
        }

        return true;
    }
}

