<?php

namespace WP2Static;

/*
 * Processes HTML files while crawling.
 * Works in multiple phases:
 *
 * Parses HTML into a DOMDocument and iterates all elements
 * For each element, it checks the type and handles with specific functions
 *
 * For elements containing links we want to process, we first normalize it:
 * to get all URLs to a uniform structure for later processing, we convert to
 * absolute URLs and rewrite to our own placeholder URL for ease of rewriting
 * we also transform URLs if the user requests document-relative or offline
 * URLs in the final output
 *
 * Before returning the final HTML for saving, we perform bulk transformations
 * such as enforcing UTF-8 encoding, replacing all placeholder links or
 * transforming to site root-relative URLs
 *
 */
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
        $destination_protocol =
            $this->getTargetSiteProtocol( $this->settings['baseUrl'] );

        $this->placeholder_url =
            $this->destination_protocol . 'PLACEHOLDER.wpsho/';

        $site_root = SiteInfo::getUrl( 'site' );

        // capture URL hosts for use in detecting internal links
        $this->site_url_host  = parse_url( $site_root, PHP_URL_HOST );
        $this->placeholder_url_host  =
            parse_url( $this->placeholder_url, PHP_URL_HOST );

        // instantiate the XML body here
        $this->xml_doc = new DOMDocument();

        // PERF: 70% of function time
        // prevent warnings, via https://stackoverflow.com/a/9149241/1668057
        libxml_use_internal_errors( true );
        $this->xml_doc->loadHTML( $html_document );
        libxml_use_internal_errors( false );

        $search_patterns = SiteURLPatterns::getWPSiteURLSearchPatterns(
            $site_root
        );

        $replace_patterns = SiteURLPatterns::getPlaceholderURLReplacementPatterns(
            $this->placeholder_url
        );

        $this->raw_html = RewriteSiteURLsToPlaceholder::rewrite(
            $this->xml_doc,
            $search_patterns,
            $replace_patterns
        );

        // detect if a base tag exists while in the loop
        // use in later base href creation to decide: append or create
        $this->base_tag_exists = false;
        $this->base_element = null;
        $this->head_element = null;

        $this->page_url = $page_url;

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
                    $this->head_element = $element;
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

        // NOTE: $this->base_tag_exists is being set during iteration of
        // elements, this prevents us from needing to do another iteration
        $this->dealWithBaseHREFElement( $this->xml_doc, $this->base_tag_exists);
        $this->stripHTMLComments();

        return true;
    }

    /*
        When we use relative links, we'll need to set the base HREF tag
        if we are exporting for offline usage or have not specific a base HREF
        we will remove any that we find

        HEAD and BASE elements have been looked for during the main DOM
        iteration and recorded if found.
    */
    public function dealWithBaseHREFElement( $xml_doc, $base_tag_exists ) {
        if ( $base_tag_exists ) {
            if ( $this->shouldCreateBaseHREF() ) {
                $this->base_element->setAttribute(
                    'href',
                    $this->settings['baseHREF']
                );
            } else {
                $this->base_element->parentNode->removeChild( $base_element );
            }
        } elseif ( $this->shouldCreateBaseHREF() ) {
            $base_element = $xml_doc->createElement( 'base' );
            $base_element->setAttribute(
                'href',
                $this->settings['baseHREF']
            );

            if ( $this->head_element ) {
                $first_head_child = $this->head_element->firstChild;
                $head_element->insertBefore(
                    $this->base_element,
                    $first_head_child
                );
            } else {
                WsLog::l(
                    'No head element to attach base to: ' . $this->page_url
                );
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

        if ( ! $this->isInternalLink( $url_to_change ) ) {
            return false;
        }

        $offline_url = ConvertToOfflineURL::convert(
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

            if ( strncasecmp( $url, $ignoreable_url, $match_length ) === 0 ) {
                return false;
            }
        }

        return true;
    }

    public function convertRelativeURLToAbsolute( $element, $page_url ) {
        $url_to_change = $this->getURLToChangeBasedOnAttribute( $element );

        if ( $this->isURLDocumentOrSiteRootRelative( $url_to_change ) ) {
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
        $this->processElementURL( $element );

        if ( isset( $this->settings['removeWPLinks'] ) ) {
            RemoveLinkElementsBasedOnRelAttr::remove( $element );
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
                $absolute_url = NormalizeURL::normalize(
                    $url,
                    $this->page_url
                );

                // rm query string
                $url = strtok( $absolute_url, '?' );
                $url = $this->convertToDocumentRelativeURLSrcSetURL( $url );
                $url = $this->convertToOfflineURLSrcSetURL( $url );
            }

            $new_src_set[] = "{$url} {$dimension}";
        }

        $element->setAttribute( 'srcset', implode( ',', $new_src_set ) );
    }

    // TODO: save actual image here as an alternative to getting all uploads
    public function processImage( $element ) {
        $this->processElementURL( $element );
    }

    public function processScript( $element ) {
        $this->processElementURL( $element );
    }

    public function processAnchor( $element ) {
        $this->processElementURL( $element );
    }


    /*
     * Process URL within a DOMElement
     *
     * @param DOMElement $element candidate for URL change
     * @return DOMAttr|false
     */
    public function processElementURL( $element ) {
        // get the URL and Attribute
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

        // quickly abort for invalid URLs
        if ( $url[0] === '#' ) {
            return;
        }

        if ( substr( $url, 0, 7 ) == 'mailto:' ) {
            return;
        }

        // exit if not an internal link
        if ( ! $this->isInternalLink( $url_to_change ) ) {
            return;
        }

        // normalize the URL / make absolute
        $absolute_url = NormalizeURL::normalize(
            $url_to_change,
            $this->page_url
        );

        $url_to_change =
            $this->removeQueryStringFromInternalLink( $url_to_change );

        // convert to Placeholder - just the base URL here? complete rewriting
        // happens later.
        $this->convertNormalizedURLToPlaceholder( $url );

        // optionally transform URL structure here

        /*
         * Note: We want to to perform as many functions on the URL, not have
         * to access the element multiple times. So, once we have it, do all
         * the things to it before sending back/updating the attribute
         */
        $this->convertToDocumentRelativeURL( $element );

        if ( $this->shouldCreateOfflineURLs() ) {
            $this->convertElementAttributeToOfflineURL( $element );
        }

        // true if attribute was able to be set
        return $element->setAttribute( $attribute_to_change, $url_to_change );
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
                    $this->base_element = $node;
                }
            }
        }
    }

    /*
        After we have normalized the element's URL and have an absolute
        Placeholder URL, we can perform transformations, such as making it
        an offline URL or document relative

        site root relative URLs can be bulk rewritten before outputting HTML
        so we don't both doing those here

        We need to do while iterating the URLs, as we cannot accurately
        iterate individual URLs in bulk rewriting mode and each URL
        needs to be rewritten in a different manner for offline mode rewriting

    */
    public function postProcessElementURLSructure( $url, $this->page_url ) {
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
        $this->convertToDocumentRelativeURL( $element );

        if ( $this->shouldCreateOfflineURLs() ) {
            $this->convertElementAttributeToOfflineURL( $element );
        }
    }

    /*
     * Detect if a URL belongs to our WP site
     * We check against known internal prefixes, WP site root and
     * our placeholder, as this check may be taking place before or after
     * the link has been rewritten to the placeholder
     *
     * @param string $link Any potential URL
     * @return boolean true for explicit match
     */
    public function isInternalLink( $link ) {
        // quickly match known internal links   ./   ../   /
        $first_char = $url[0];

        if ( $first_char === '.' || $first_char === '/' ) {
            return true;
        }

        // TODO: check against both site_url and placeholder, as we don't want
        // to do costly extra bulk rewrite in the beginning

        // TODO: apply only to links starting with .,..,/,
        // or any with just a path, like banana.png
        // check link is same host as $this->url and not a subdomain
        $link_host = parse_url( $link, PHP_URL_HOST );

        if (
            $link_host === $this->site_url_host ||
            $link_host === $this->placeholder_url_host ||
        ) {
            return true;
        }

        return false;
    }

    public function removeQueryStringFromInternalLink( $url ) {
        // strip anything from the ? onwards
        $url = strtok( $url, '?' ));
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

    public function getHTML( $xml_doc, $rewrite_unchanged_urls = true ) {
        $processed_html = $xml_doc->saveHtml();

        // NOTE: add to plugin rewrite rules
        // NOTE: fix input HTML, which can have \ slashes modified to %5C
        $processed_html = str_replace(
            '%5C/',
            '\\/',
            $processed_html
        );


        // TODO: add escaped URLs into the search/replace patterns
        // rewriteEscapedURLs

        // TODO: here is where we convertToSiteRelativeURLs, as this can be
        // bulk performed, just stripping the domain when rewriting

        // get user rewrite rules, use regular and escaped versions of them
        $rewrite_rules =
            GenerateRewriteRules::generate( $plugin_rules, $user_rules);



        $processed_html = return ReplaceMultipleStrings:replace(
            $processed_html,
            $search_patterns,
            $replace_patterns
        );

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

    public function convertToDocumentRelativeURLSrcSetURL( $url_to_change ) {
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


    // TODO: This function is to be performed on placeholder URLs
    // allowing the user to convert URLs to document or site relative form
    public function convertToDocumentRelativeURL( $element ) {
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

        if ( ! is_string( $current_page_path ) ) {
            return;
        }

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
}

