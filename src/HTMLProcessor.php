<?php

namespace WP2Static;

use DOMDocument;
use DOMXPath;
use Exception;

/*
 * Processes HTML files while crawling.
 * Works in multiple phases:
 *
 * Parses HTML into a DOMDocument and iterates all elements
 * For each element, it checks the type and handles with specific functions
 *
 * For elements containing links we want to process, we first normalize it:
 * to get all URLs to a uniform structure for later processing, we convert to
 * absolute URLs and rewrite to the Destination URL for ease of rewriting
 * we also transform URLs if the user requests document-relative or offline
 * URLs in the final output
 *
 * Before returning the final HTML for saving, we perform bulk transformations
 * such as enforcing UTF-8 encoding, replacing any site URLs to Destination or
 * transforming to site root-relative URLs
 *
 */
class HTMLProcessor extends Base {

    // TODO: clean up the passed var
    public function __construct(
        $rewrite_rules,
        $site_url_host,
        $destination_url,
        $user_rewrite_rules
    ) {
        $this->loadSettings(
            array(
                'wpenv',
                'processing',
                'advanced',
            )
        );

        $this->rewrite_rules = $rewrite_rules;
        $this->user_rewrite_rules = $user_rewrite_rules;
        $this->site_url_host = $site_url_host;
        $this->destination_url = $destination_url;

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

        // detect if a base tag exists while in the loop
        // use in later base href creation to decide: append or create
        $this->base_tag_exists = false;
        $this->base_element = null;
        $this->head_element = null;

        $this->page_url = $page_url;

        // instantiate the XML body here
        $this->xml_doc = new DOMDocument();

        // PERF: 70% of function time
        // prevent warnings, via https://stackoverflow.com/a/9149241/1668057
        libxml_use_internal_errors( true );
        $this->xml_doc->loadHTML( $html_document );
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

    public function getURLAndTargetAttribute( $element ) {
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

        return [ $url_to_change, $attribute_to_change ];
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
                $absolute_url = NormalizeURL::normalize(
                    $url,
                    $this->page_url
                );

                // rm query string
                $url = strtok( $absolute_url, '?' );
                $url = $this->convertToDocumentRelativeURLSrcSetURL( $url );
                $url = $this->convertToOfflineURLSrcSetURL(
                    $url, $this->destination_url
                );
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
        list( $url_to_change, $attribute_to_change ) =
            $this->getURLAndTargetAttribute( $element );

        if ( ! $url_to_change || ! $attribute_to_change ) {
            return;
        }

        // quickly abort for invalid URLs
        if ( $url_to_change[0] === '#' ) {
            return;
        }

        if ( substr( $url_to_change, 0, 7 ) == 'mailto:' ) {
            return;
        }

        // exit if not an internal link
        if ( ! $this->isInternalLink( $url_to_change ) ) {
            return;
        }

        $site_url = SiteInfo::getUrl('site');

        // replace protocol-relative URLs here to absolute site-url
        if ( $url_to_change[0] === '/' ) {
            if ( $url_to_change[1] === '/' ) {
                $url_to_change = str_replace(
                    URLHelper::getProtocolRelativeURL( $site_url ),
                    $site_url,
                    $url_to_change
                );
            }
        }

        // normalize site root-relative URLs here to absolute site-url
        if ( $url_to_change[0] === '/' ) {
                error_log('POTENTIAL site root relative URL: ' . $url_to_change);
            if ( $url_to_change[1] !== '/' ) {
                error_log('site root relative URL: ' . $url_to_change);
                $url_to_change = $site_url . ltrim( $url_to_change, '/' );
            }
        }

        // TODO: enfore trailing slash

        // TODO: download approved static files
            // defaults (images, fonts, css, js)

            // check for user additions

            // determine save path

            // check for existing image

            // check for cache

        // normalize the URL / make absolute
        $url_to_change = NormalizeURL::normalize(
            $url_to_change,
            $this->page_url
        );


        // after normalizing, we need to rewrite to Destination URL
        $url_to_change = str_replace(
            $this->rewrite_rules['site_url_patterns'],
            $this->rewrite_rules['destination_url_patterns'],
            $url_to_change
        );

        $url_to_change =
            $this->removeQueryStringFromInternalLink( $url_to_change );

        // convert to Placeholder - just the base URL here? complete rewriting
        // happens later.
        // $this->convertNormalizedURLToPlaceholder( $url );

        /*
         * Note: We want to to perform as many functions on the URL, not have
         * to access the element multiple times. So, once we have it, do all
         * the things to it before sending back/updating the attribute
         */
        $url_to_change =
            $this->postProcessElementURLStructure(
                $url_to_change,
                $this->page_url,
                $site_url
            );

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
    public function postProcessElementURLStructure(
        $url,
        $page_url,
        $site_url
    ) {
        if ( $this->shouldUseRelativeURLs() ) {
            $url = $this->convertToDocumentRelativeURL( $url );
        }

        if ( $this->shouldCreateOfflineURLs() ) {
            $url = ConvertToOfflineURL::convert(
                $url,
                $page_url,
                $site_url
            );
        }

        return $url;
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

        $this->processElementURL( $element );
    }

    /*
     * Detect if a URL belongs to our WP site
     * We check against known internal prefixes and WP site host
     *
     * @param string $link Any potential URL
     * @return boolean true for explicit match
     */
    public function isInternalLink( $url ) {
        // quickly match known internal links   ./   ../   /
        $first_char = $url[0];

        if ( $first_char === '.' || $first_char === '/' ) {
            return true;
        }

        $url_host = parse_url( $url, PHP_URL_HOST );

        if ( $url_host === $this->site_url_host) {
            return true;
        }

        return false;
    }

    public function removeQueryStringFromInternalLink( $url ) {
        if ( strpos( $url, '?' ) !== false ) {
            // strip anything from the ? onwards
            $url = strtok( $url, '?' );
        }

        return $url;
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

    public function getHTML( $xml_doc ) {
        $processed_html = $xml_doc->saveHtml();

        // TODO: here is where we convertToSiteRelativeURLs, as this can be
        // bulk performed, just stripping the domain when rewriting


        // TODO: allow for user-defined rewrites to be done after all other
        // rewrites - enables fixes for situations where certain links haven't
        // been rewritten / arbitrary rewriting of any URLs, even external
        // allow filter here, for 3rd party development
        if ( $this->user_rewrite_rules ) {
            $rewrite_rules =
                RewriteRules::getUserRewriteRules( $this->user_rewrite_rules );

            $processed_html = str_replace(
                $rewrite_rules['from'],
                $rewrite_rules['to'],
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

    public function convertToDocumentRelativeURLSrcSetURL( $url_to_change ) {
        if ( ! $this->shouldUseRelativeURLs() ) {
            return $url_to_change;
        }

        $site_root = '';

        $relative_url = str_replace(
            $this->destination_url,
            $site_root,
            $url_to_change
        );

        return $relative_url;
    }


    // TODO: This function is to be performed on site URLs
    // allowing the user to convert URLs to document or site relative form
    public function convertToDocumentRelativeURL( $url ) {
        $site_root = '';

        $rewritten_url = str_replace(
            $this->settings['baseUrl'],
            $site_root,
            $url
        );

        return $rewritten_url;
    }

    public function convertToOfflineURLSrcSetURL( $url_to_change, $destination_url ) {
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
            $destination_url,
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

