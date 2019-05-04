<?php

namespace WP2Static;

class HTMLProcessor extends Base {

    public function __construct() {
        $this->loadSettings(
            array(
                'wpenv',
                'processing',
                'advanced',
            )
        );

        $this->processed_urls = array();
    }

    /*
        Processing HTML documents is core to WP2Static's functions, involving:

            - grabbing the whole content body
            - iterating all links to rewrite doc/site relative URLs to
              absolute PLACEHOLDER URLs
            - rewriting all absolute URLs to the original WP site to absolute
              PLACEHOLDER URLs
            - recording all valid links on each page for further crawling
            - performing various transformations of the HTML content/links as
              specified by the user
            - rewriting PLACEHOLDER URLs to the Destination URL as specified 
              by the user
            - saving the resultant processed HTML content to the export dir 
    */
    public function processHTML( $html_document, $page_url ) {
        if ( $html_document == '' ) {
            return false;
        }

        // NOTE: set placeholder_url to same protocol as target
        // making it easier to rewrite URLs without considering protocol
        $this->destination_protocol =
            $this->getTargetSiteProtocol( $this->settings['baseUrl'] );

        $this->placeholder_url =
            $this->destination_protocol . 'PLACEHOLDER.wpsho/';

        $wp_site_root = $this->settings['wp_site_url'];

        // instantiate the XML body here
        $this->xml_doc = new \DOMDocument();

        // PERF: 70% of function time
        // prevent warnings, via https://stackoverflow.com/a/9149241/1668057
        libxml_use_internal_errors( true );
        $this->xml_doc->loadHTML( $html_document );
        libxml_use_internal_errors( false );

        $html_with_absolute_urls = $this->rewriteRelativeWPSiteURLsToAbsolute(
            $this->xml_doc,
            $wp_site_root,
            $this->placeholder_url,
            $page_url
        );

        $search_patterns = $this->getWPSiteURLSearchPatterns(
            $wp_site_root
        );

        $replace_patterns = $this->getPlaceholderURLReplacementPatterns(
            $this->placeholder_url
        );

        require_once 'HTMLProcessingFunctions/' .
            'rewriteSiteURLsToPlaceholder.php';

        $this->raw_html = rewriteSiteURLsToPlaceholder(
            $html_with_absolute_urls,
            $search_patterns,
            $replace_patterns
        );

        // detect if a base tag exists while in the loop
        // use in later base href creation to decide: append or create
        $this->base_tag_exists = false;

        $this->page_url = $page_url;

        $this->harvest_new_urls = $this->detectIfURLsShouldBeHarvested();

        $this->discovered_urls = array();

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
                    $this->processImageSrcSet( $element );
                    break;
                case 'head':
                    $this->processHead( $element );
                    break;
                case 'link':
                    // NOTE: not to confuse with anchor element
                    $this->processLink( $element );
                    break;
                case 'script':
                    /*
                        Script tags may contain src=
                        can also contain URLs within scripts
                        and escaped urls
                    */
                    $this->processScript( $element );
                    break;
            }
        }

        $this->dealWithBaseHREFElement();
        $this->stripHTMLComments();
        $this->writeDiscoveredURLs();

        return true;
    }

    /*
        As a first-pass, we normalize any document or site root-relative URLs

        to make further processing easier

        Takes in an XML DOMDocument and outputs raw HTML document
    */
    public function rewriteRelativeWPSiteURLsToAbsolute(
            $xml_doc,
            $wp_site_root,
            $placeholder_url,
            $page_url
    ) {
        $elements = iterator_to_array(
            $xml_doc->getElementsByTagName( '*' )
        );

        $elements_to_normalize = array();
        $elements_to_normalize['meta'] = 1;
        $elements_to_normalize['a'] = 1;
        $elements_to_normalize['img'] = 1;
        $elements_to_normalize['head'] = 1;
        $elements_to_normalize['link'] = 1;
        $elements_to_normalize['script'] = 1;

        foreach ( $elements as $element ) {
            if ( isset ( $elements_to_normalize[$element->tagName] ) ) {
                $this->convertRelativeURLToAbsolute( $element, $page_url );
            }
        }

        $source_with_absolute_urls = $this->getHTML( $xml_doc, false );

        return $source_with_absolute_urls;
    }

    /*
        When we use relative links, we'll need to set the base HREF tag

        if we are exporting for offline usage or have not specific a base HREF

        we will remove any that we find 
    */
    public function dealWithBaseHREFElement() {
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
                    '/../WP2Static/WsLog.php';
                WsLog::l(
                    'WARNING: no valid head elemnent to attach base to: ' .
                        $this->page_url
                );
            }
        }
    }

    /* 
        Initial phase of crawling is using detected URLs only

        we detect if we're still doing our initial craw, if so

        we keep recording any discovered URLs
    */
    public function detectIfURLsShouldBeHarvested() {
        if ( ! defined( 'WP_CLI' ) ) {
            // @codingStandardsIgnoreStart
            return ( $_POST['ajax_action'] === 'crawl_site' );
            // @codingStandardsIgnoreEnd
        } else {
            // we shouldn't harvest any while we're in the second crawl
            if ( defined( 'CRAWLING_DISCOVERED' ) ) {
                return false;
            } else {
                return true;
            }
        }
    }

    public function getURLToChangeBasedOnAttribute( $element ) {
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

        return $url_to_change;
    }

    public function getAttributeToChange( $element ) {
        if ( $element->hasAttribute( 'href' ) ) {
            $attribute_to_change = 'href';
        } elseif ( $element->hasAttribute( 'src' ) ) {
            $attribute_to_change = 'src';
        } elseif ( $element->hasAttribute( 'content' ) ) {
            $attribute_to_change = 'content';
        } else {
            return false;
        }

        return $attribute_to_change;
    }

    public function convertElementAttributeToOfflineURL( $element ) {
        $url_to_change = $this->getURLToChangeBasedOnAttribute( $element );

        if ( ! $this->isInternalLink( $url_to_change) ) {
            return false;
        }

        require_once 'HTMLProcessingFunctions/' .
            'convertToOfflineURL.php';

        $offline_url = convertToOfflineURL(
            $url_to_change,
            $this->page_url,
            $this->placeholder_url
        );

        $attribute_to_change = $this->getAttributeToChange( $element );

        $element->setAttribute( $attribute_to_change, $offline_url );
    }

    /*
        For initially normalizing all WP Site URLs to be absolute

        we check for any URLs not already absolute, protocol-relative or 

        otherwise known to be ignorable. 

        TODO: extra checking to eliminate false-positives, such as links
        with colons in the path
    */
    public function isURLDocumentOrSiteRootRelative( $url ) {
        // check if start of URL isn't any of 
        $url_protocols_to_ignore = array(
            'http://',
            'https://',
            '//',
            'file://',
            'ftp://',
            'mailto:',
        );

        foreach ( $url_protocols_to_ignore as $ignoreable_url ) {
             $match_length = strlen( $ignoreable_url );

             if ( strncasecmp( $url, $ignoreable_url, $match_length) === 0 ) {
                return false;
            }
        }

        return true;
    }

    public function convertRelativeURLToAbsolute( $element, $page_url ) {
        $url_to_change = $this->getURLToChangeBasedOnAttribute( $element );

        if ( $this->isURLDocumentOrSiteRootRelative( $url_to_change) ) {
            $absolute_url = NormalizeURL::normalize(
                $url_to_change,
                $page_url
            );

            $attribute_to_change = $this->getAttributeToChange( $element );

            if ( $attribute_to_change ) {
                $element->setAttribute( $attribute_to_change, $absolute_url );
            }
        }
    }

    public function processLink( $element ) {
        $original_url = $element->getAttribute( 'html' );

        if ( $this->isInternalLink( $original_url ) ) {
            $absolute_url = NormalizeURL::normalize(
                $original_url,
                $this->page_url
            );

            $element->setAttribute( 'html', $absolute_url );
        }

        $this->removeQueryStringFromInternalLink( $element );
        $this->addDiscoveredURL( $element->getAttribute( 'href' ) );
        $this->rewriteWPPaths( $element );
        $this->rewriteBaseURL( $element );
        $this->convertToRelativeURL( $element );

        if ( $this->shouldCreateOfflineURLs() ) {
            $this->convertElementAttributeToOfflineURL( $element );
        }

        if ( isset( $this->settings['removeWPLinks'] ) ) {
            require_once 'HTMLProcessingFunctions/' .
                'removeLinkElementsBasedOnRelAttr.php';

            removeLinkElementsBasedOnRelAttr( $element );
        }

        if ( isset( $this->settings['removeCanonical'] ) ) {
            $this->removeCanonicalLink( $element );
        }
    }

    public function removeCanonicalLink( $element ) {
        $link_rel = $element->getAttribute( 'rel' );

        if ( strtolower( $link_rel ) == 'canonical' ) {
            $element->parentNode->removeChild( $element );
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

        if ( in_array( $url, $this->processed_urls ) ) {
            return;
        }

        if ( trim( $url ) === '' ) {
            return;
        }

        $this->processed_urls[] = $url;

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

    public function processImageSrcSet( $element ) {
        if ( ! $element->hasAttribute( 'srcset' ) ) {
            return;
        }

        $new_src_set = array();

        $src_set = $element->getAttribute( 'srcset' );

        $src_set_lines = explode( ',', $src_set );

        foreach ( $src_set_lines as $src_set_line ) {
            $all_pieces = explode( ' ', $src_set_line );

            // rm empty elements
            $pieces = array_filter( $all_pieces );
            // reindex array
            $pieces = array_values( $pieces );

            $url = $pieces[0];
            $dimension = $pieces[1];

            // normalize urls
            if ( $this->isInternalLink( $url ) ) {
                // TODO: require and convert to URL2 to use resolve()
                $url = $this->page_url->resolve( $url );

                // rm query string
                $url = strtok( $url, '?' );
                $this->addDiscoveredURL( $url );
                $url = $this->rewriteWPPathsSrcSetURL( $url );
                $url = $this->rewriteBaseURLSrcSetURL( $url );
                $url = $this->convertToRelativeURLSrcSetURL( $url );
                $url = $this->convertToOfflineURLSrcSetURL( $url );
            }

            $new_src_set[] = "{$url} {$dimension}";
        }

        $element->setAttribute( 'srcset', implode( ',', $new_src_set ) );
    }

    public function processImage( $element ) {
        $original_url = $element->getAttribute( 'src' );

        if ( $this->isInternalLink( $original_url ) ) {
            $absolute_url = NormalizeURL::normalize(
                $original_url,
                $this->page_url
            );

            $element->setAttribute( 'src', $absolute_url );
        }

        $this->removeQueryStringFromInternalLink( $element );
        $this->addDiscoveredURL( $element->getAttribute( 'src' ) );
        $this->rewriteWPPaths( $element );
        $this->rewriteBaseURL( $element );
        $this->convertToRelativeURL( $element );

        if ( $this->shouldCreateOfflineURLs() ) {
            $this->convertElementAttributeToOfflineURL( $element );
        }
    }

    public function stripHTMLComments() {
        if ( isset( $this->settings['removeHTMLComments'] ) ) {
            $xpath = new \DOMXPath( $this->xml_doc );

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
            if ( $node instanceof \DOMComment ) {
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
        $original_url = $element->getAttribute( 'src' );

        if ( $this->isInternalLink( $original_url ) ) {
            $absolute_url = NormalizeURL::normalize(
                $original_url,
                $this->page_url
            );

            $element->setAttribute( 'src', $absolute_url );
        }

        $this->removeQueryStringFromInternalLink( $element );
        $this->addDiscoveredURL( $element->getAttribute( 'src' ) );
        $this->rewriteWPPaths( $element );
        $this->rewriteBaseURL( $element );
        $this->convertToRelativeURL( $element );

        if ( $this->shouldCreateOfflineURLs() ) {
            $this->convertElementAttributeToOfflineURL( $element );
        }
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

        $original_url = $element->getAttribute( 'href' );

        if ( $this->isInternalLink( $original_url ) ) {
            $absolute_url = NormalizeURL::normalize(
                $original_url,
                $this->page_url
            );

            $element->setAttribute( 'href', $absolute_url );
        }

        $this->removeQueryStringFromInternalLink( $element );
        $this->addDiscoveredURL( $url );
        $this->rewriteWPPaths( $element );
        $this->rewriteBaseURL( $element );
        $this->convertToRelativeURL( $element );

        if ( $this->shouldCreateOfflineURLs() ) {
            $this->convertElementAttributeToOfflineURL( $element );
        }
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

        $original_url = $element->getAttribute( 'content' );

        if ( $this->isInternalLink( $original_url ) ) {
            $absolute_url = NormalizeURL::normalize(
                $original_url,
                $this->page_url
            );

            $element->setAttribute( 'content', $absolute_url );
        }

        $this->removeQueryStringFromInternalLink( $element );
        $this->addDiscoveredURL( $url );
        $this->rewriteWPPaths( $element );
        $this->rewriteBaseURL( $element );
        $this->convertToRelativeURL( $element );

        if ( $this->shouldCreateOfflineURLs() ) {
            $this->convertElementAttributeToOfflineURL( $element );
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

    public function detectUnchangedPlaceholderURLs( $processed_html ) {
        $placeholder_url = $this->placeholder_url;

        if ( strpos( $processed_html, $placeholder_url ) !== false ) {
            return $this->rewriteUnchangedPlaceholderURLs(
                $processed_html
            );
        }

        return $processed_html;
    }

    public function rewriteUnchangedPlaceholderURLs( $processed_html ) {
        if ( ! isset( $this->settings['rewrite_rules'] ) ) {
            $this->settings['rewrite_rules'] = '';
        }

        $placeholder_url = rtrim( $this->placeholder_url, '/' );
        $destination_url = rtrim(
            $this->settings['baseUrl'],
            '/'
        );

        // add base URL to rewrite_rules
        $this->settings['rewrite_rules'] .=
            PHP_EOL .
                $placeholder_url . ',' .
                $destination_url;

        $rewrite_from = array();
        $rewrite_to = array();

        $rewrite_rules = explode(
            "\n",
            str_replace( "\r", '', $this->settings['rewrite_rules'] )
        );

        $tmp_rules = array();

        foreach ( $rewrite_rules as $rewrite_rule_line ) {
            if ( $rewrite_rule_line ) {
                list($from, $to) = explode( ',', $rewrite_rule_line );
                $tmp_rules[ $from ] = $to;
            }
        }

        uksort( $tmp_rules, array( $this, 'ruleSort' ) );

        foreach ( $tmp_rules as $from => $to ) {
            $rewrite_from[] = $from;
            $rewrite_to[] = $to;
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

        $tmp_rules = array();

        foreach ( $rewrite_rules as $rewrite_rule_line ) {
            if ( $rewrite_rule_line ) {
                list($from, $to) = explode( ',', $rewrite_rule_line );
                $tmp_rules[ $from ] = $to;
            }
        }

        uksort( $tmp_rules, array( $this, 'ruleSort' ) );

        foreach ( $tmp_rules as $from => $to ) {
            $rewrite_from[] = addcslashes( $from, '/' );
            $rewrite_to[] = addcslashes( $to, '/' );
        }

        $rewritten_source = str_replace(
            $rewrite_from,
            $rewrite_to,
            $processed_html
        );

        return $rewritten_source;

    }

    public function rewriteWPPathsSrcSetURL( $url_to_change ) {
        if ( ! isset( $this->settings['rewrite_rules'] ) ) {
            return $url_to_change;
        }

        $rewrite_from = array();
        $rewrite_to = array();

        $rewrite_rules = explode(
            "\n",
            str_replace( "\r", '', $this->settings['rewrite_rules'] )
        );

        $tmp_rules = array();

        foreach ( $rewrite_rules as $rewrite_rule_line ) {
            if ( $rewrite_rule_line ) {
                list($from, $to) = explode( ',', $rewrite_rule_line );
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
            $url_to_change
        );

        return $rewritten_url;
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

        $tmp_rules = array();

        foreach ( $rewrite_rules as $rewrite_rule_line ) {
            if ( $rewrite_rule_line ) {
                list($from, $to) = explode( ',', $rewrite_rule_line );
                $tmp_rules[ $from ] = $to;
            }
        }

        uksort( $tmp_rules, array( $this, 'ruleSort' ) );

        foreach ( $tmp_rules as $from => $to ) {
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

    public function getHTML( $xml_doc, $rewrite_unchanged_urls = true ) {
        $processed_html = $xml_doc->saveHtml();

        /*
            Last chance to catch any URLS not already rewritten correctly

            TODO: sharing this function in 2 places, split out for separation

            of responsibilities
        */
        if ( $rewrite_unchanged_urls ) {
            // process the resulting HTML as text
            $processed_html = $this->detectEscapedSiteURLs( $processed_html );
            $processed_html = $this->detectUnchangedPlaceholderURLs(
                $processed_html
            );
        }

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

    public function convertToRelativeURLSrcSetURL( $url_to_change ) {
        if ( ! $this->shouldUseRelativeURLs() ) {
            return $url_to_change;
        }

        $site_root = '';

        $relative_url = str_replace(
            $this->settings['baseUrl'],
            $site_root,
            $url_to_change
        );

        return $relative_url;
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

    public function convertToOfflineURLSrcSetURL( $url_to_change ) {
        if ( ! $this->shouldCreateOfflineURLs() ) {
            return $url_to_change;
        }

        $current_page_path_to_root = '';
        $current_page_path = parse_url( $this->page_url, PHP_URL_PATH );
        $number_of_segments_in_path = explode( '/', $current_page_path );
        $num_dots_to_root = count( $number_of_segments_in_path ) - 2;

        for ( $i = 0; $i < $num_dots_to_root; $i++ ) {
            $current_page_path_to_root .= '../';
        }

        if ( ! $this->isInternalLink(
            $url_to_change
        ) ) {
            return false;
        }

        $rewritten_url = str_replace(
            $this->placeholder_url,
            '',
            $url_to_change
        );

        $offline_url = $current_page_path_to_root . $rewritten_url;

        // add index.html if no extension
        if ( substr( $offline_url, -1 ) === '/' ) {
            // TODO: check XML/RSS case
            $offline_url .= 'index.html';
        }

        return $offline_url;
    }

    // TODO: move some of these URLs into settings to avoid extra calls
    public function getProtocolRelativeURL( $url ) {
        $this->destination_protocol_relative_url = str_replace(
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

        return $this->destination_protocol_relative_url;
    }

    public function rewriteBaseURLSrcSetURL( $url_to_change ) {
        $rewritten_url = str_replace(
            $this->getBaseURLRewritePatterns(),
            $this->getBaseURLRewritePatterns(),
            $url_to_change
        );

        return $rewritten_url;
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
                $this->getBaseURLRewritePatterns(),
                $this->getBaseURLRewritePatterns(),
                $url_to_change
            );

            $element->setAttribute( $attribute_to_change, $rewritten_url );
        }
    }

    public function getTargetSiteProtocol( $url ) {
        $destination_protocol = '//';

        if ( strpos( $url, 'https://' ) !== false ) {
            $destination_protocol = 'https://';
        } elseif ( strpos( $url, 'http://' ) !== false ) {
            $destination_protocol = 'http://';
        } else {
            $destination_protocol = '//';
        }

        return $destination_protocol;
    }

    public function shouldUseRelativeURLs() {
        if ( ! isset( $this->settings['useRelativeURLs'] ) ) {
            return false;
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

    public function shouldCreateOfflineURLs() {
        if ( ! isset( $this->settings['allowOfflineUsage'] ) ) {
            return false;
        }

        if ( $this->settings['selected_deployment_option'] != 'zip' ) {
            return false;
        }

        return true;
    }

    // TODO: move this up to WPSite level or higher, log only once
    public function getBaseURLRewritePatterns() {
        $patterns = array(
            $this->placeholder_url,
            addcslashes( $this->placeholder_url, '/' ),
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
        );

        return $patterns;
    }

    public function getBaseURLRewriteReplacements() {
        $replacements = array(
            $this->settings['baseUrl'],
            addcslashes( $this->settings['baseUrl'], '/' ),
            $this->getProtocolRelativeURL(
                $this->settings['baseUrl']
            ),
            $this->getProtocolRelativeURL(
                rtrim( $this->settings['baseUrl'], '/' )
            ),
            $this->getProtocolRelativeURL(
                $this->settings['baseUrl'] . '//'
            ),
            $this->getProtocolRelativeURL(
                addcslashes( $this->settings['baseUrl'], '/' )
            ),
        );

        return $replacements;
    }

    public function getWPSiteURLSearchPatterns( $wp_site_url ) {
        $wp_site_url = rtrim( $wp_site_url, '/' );

        $wp_site_url_with_cslashes = addcslashes( $wp_site_url, '/' );

        $protocol_relative_wp_site_url = $this->getProtocolRelativeURL(
            $wp_site_url
        );
            
        $protocol_relative_wp_site_url_with_extra_2_slashes =
            $this->getProtocolRelativeURL( $wp_site_url . '//' );

        $protocol_relative_wp_site_url_with_cslashes =
            $this->getProtocolRelativeURL( addcslashes( $wp_site_url, '/' ) );

        $search_patterns = array(
            $wp_site_url,
            $wp_site_url_with_cslashes,
            $protocol_relative_wp_site_url,
            $protocol_relative_wp_site_url_with_extra_2_slashes,
            $protocol_relative_wp_site_url_with_cslashes,
        );

        return $search_patterns;
    }

    public function getPlaceholderURLReplacementPatterns( $placeholder_url ) {
        $placeholder_url = rtrim( $placeholder_url, '/' );
        $placeholder_url_with_cslashes = addcslashes( $placeholder_url, '/' );

        $protocol_relative_placeholder =
            $this->getProtocolRelativeURL( $placeholder_url );

        $protocol_relative_placeholder_with_extra_slash =
            $this->getProtocolRelativeURL( $placeholder_url . '/' );

        $protocol_relative_placeholder_with_cslashes =
            $this->getProtocolRelativeURL(
                addcslashes( $placeholder_url, '/' )
            );

        $replace_patterns = array(
            $placeholder_url,
            $placeholder_url_with_cslashes,
            $protocol_relative_placeholder,
            $protocol_relative_placeholder_with_extra_slash,
            $protocol_relative_placeholder_with_cslashes,
        );

        return $replace_patterns;
    }

    public function logAction( $action ) {
        if ( ! isset( $this->settings['debug_mode'] ) ) {
            return;
        }

        require_once dirname( __FILE__ ) .
            '/../WP2Static/WsLog.php';
        WsLog::l( $action );
    }
}

