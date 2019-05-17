<?php

namespace WP2Static;

use DOMDocument;
use DOMComment;
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
        $this->loadSettings();

        $this->rewrite_rules = $rewrite_rules;
        $this->user_rewrite_rules = $user_rewrite_rules;
        $this->site_url_host = $site_url_host;
        $this->destination_url = $destination_url;
        $this->site_url = SiteInfo::getUrl( 'site' );

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
                    $this->processElementURL( $element );
                    break;
                case 'img':
                    $this->processElementURL( $element );
                    $this->processImageSrcSet( $element );
                    break;
                case 'head':
                    $this->head_element = $element;
                    $this->processHead( $element );
                    break;
                case 'link':
                    // NOTE: not to confuse with anchor element
                    $this->processElementURL( $element );

                    if ( isset( $this->settings['removeWPLinks'] ) ) {
                        RemoveLinkElementsBasedOnRelAttr::remove( $element );
                    }

                    if ( isset( $this->settings['removeCanonical'] ) ) {
                        $this->removeCanonicalLink( $element );
                    }

                    break;
                case 'script':
                    /*
                        Script tags may contain src=
                        can also contain URLs within scripts
                        and escaped urls
                    */
                    $this->processElementURL( $element );

                    // get the CDATA sections within <SCRIPT>
                    foreach ( $element->childNodes as $node ) {
                        if ( $node->nodeType == XML_CDATA_SECTION_NODE ) {
                            $this->processCDATA( $node );
                        }
                    }
                    break;
            }
        }

        // NOTE: $this->base_tag_exists is being set during iteration of
        // elements, this prevents us from needing to do another iteration
        $this->dealWithBaseHREFElement(
            $this->xml_doc,
            $this->base_tag_exists
        );

        // allow empty favicon to prevent extra browser request
        if ( isset( $this->settings['createEmptyFavicon'] ) ) {
            $this->createEmptyFaviconLink( $this->xml_doc );
        }

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
                $this->base_element->parentNode->removeChild(
                    $this->base_element
                );
            }
        } elseif ( $this->shouldCreateBaseHREF() ) {
            $base_element = $xml_doc->createElement( 'base' );
            $base_element->setAttribute(
                'href',
                $this->settings['baseHREF']
            );

            if ( $this->head_element ) {
                $first_head_child = $this->head_element->firstChild;
                $this->head_element->insertBefore(
                    $base_element,
                    $first_head_child
                );
            } else {
                WsLog::l(
                    'No head element to attach base to: ' . $this->page_url
                );
            }
        }
    }

    public function createEmptyFaviconLink( $xml_doc ) {
        $link_element = $xml_doc->createElement( 'link' );
        $link_element->setAttribute(
            'rel',
            'icon'
        );

        $link_element->setAttribute(
            'href',
            'data:,'
        );

        if ( $this->head_element ) {
            $first_head_child = $this->head_element->firstChild;
            $this->head_element->insertBefore(
                $link_element,
                $first_head_child
            );
        } else {
            WsLog::l(
                'No head element to attach favicon link to to: ' .
                $this->page_url
            );
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

            $url = $this->rewriteLocalURL( $url );

            $new_src_set[] = "{$url} {$dimension}";
        }

        $element->setAttribute( 'srcset', implode( ',', $new_src_set ) );
    }

    public function rewriteLocalURL( $url ) {
        if ( URLHelper::startsWithHash( $url ) ) {
            return;
        }

        if ( URLHelper::isMailto( $url ) ) {
            return;
        }

        if ( ! URLHelper::isInternalLink( $url, $this->site_url_host ) ) {
            return;
        }

        if ( URLHelper::isProtocolRelative( $url ) ) {
            $url = URLHelper::protocolRelativeToAbsoluteURL(
                $url,
                $this->site_url
            );
        }

        // normalize site root-relative URLs here to absolute site-url
        if ( $url[0] === '/' ) {
            if ( $url[1] !== '/' ) {
                $url = $this->site_url . ltrim( $url, '/' );
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
        $url = NormalizeURL::normalize(
            $url,
            $this->page_url
        );

        $url = $this->removeQueryStringFromInternalLink( $url );

        if ( isset( $this->settings['includeDiscoveredAssets'] ) ) {
            // check url has extension at all
            $extension = pathinfo( $url, PATHINFO_EXTENSION );

            // only try to dl urls with extension
            if ( $extension ) {
                // TODO: where is the best place to put this
                // considering caching, ie, build array here
                // exclude Excludes, already crawled lists
                // then iterate just the ones not already on disk
                $this->downloadAsset( $url, $extension );
            }
        }

        // after normalizing, we need to rewrite to Destination URL
        $url = str_replace(
            $this->rewrite_rules['site_url_patterns'],
            $this->rewrite_rules['destination_url_patterns'],
            $url
        );

        /*
         * Note: We want to to perform as many functions on the URL, not have
         * to access the element multiple times. So, once we have it, do all
         * the things to it before sending back/updating the attribute
         */
        $url = $this->postProcessElementURLStructure(
            $url,
            $this->page_url,
            $this->site_url
        );

        return $url;
    }

    /*
     * Process URL within a DOMElement
     *
     * @param DOMElement $element candidate for URL change
     * @return DOMAttr|false
     */
    public function processElementURL( $element ) {
        list( $url, $attribute_to_change ) =
            $this->getURLAndTargetAttribute( $element );

        if ( ! $url || ! $attribute_to_change ) {
            return;
        }

        $url = $this->rewriteLocalURL( $url );

        return $element->setAttribute( $attribute_to_change, $url );
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
     * After we have normalized the element's URL and have an absolute
     * Placeholder URL, we can perform transformations, such as making it
     * an offline URL or document relative

     * site root relative URLs can be bulk rewritten before outputting HTML
     * so we don't both doing those here

     * We need to do while iterating the URLs, as we cannot accurately
     * iterate individual URLs in bulk rewriting mode and each URL
     * needs to be rewritten in a different manner for offline mode rewriting
     *
     * @param string $url absolute Site URL to change
     * @param string $page_url URL of current page for doc relative calculation
     * @param string $destination_url Site URL reference for rewriting
     * @return string Rewritten URL
     *
    */
    public function postProcessElementURLStructure(
        $url,
        $page_url,
        $site_url
    ) {
        $offline_mode = false;

        if ( isset( $this->settings['allowOfflineUsage'] ) ) {
            $offline_mode = true;
        }

        // TODO: move detection func higher
        if ( isset( $this->settings['useDocumentRelativeURLs'] ) ) {
            $url = ConvertToDocumentRelativeURL::convert(
                $url,
                $page_url,
                $site_url,
                $offline_mode
            );
        }

        if ( isset( $this->settings['useSiteRootRelativeURLs'] ) ) {
            $url = ConvertToSiteRootRelativeURL::convert(
                $url,
                $this->destination_url
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

    public function removeQueryStringFromInternalLink( $url ) {
        if ( strpos( $url, '?' ) !== false ) {
            // strip anything from the ? onwards
            $url = strtok( $url, '?' );
        }

        return $url;
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

    public function processCDATA( $node ) {
        $node_text = $node->textContent;

        $node_text = str_replace(
            $this->rewrite_rules['site_url_patterns'],
            $this->rewrite_rules['destination_url_patterns'],
            $node_text
        );

        $new_node =
            $this->xml_doc->createTextNode( $node_text );

        // replace old node with new
        $node->parentNode->replaceChild( $new_node, $node );
    }

    /*
     * Download discovered assets
     *
     * @param string $url Absolute local URL to potentially download
     * @return void
     *
     */
    public function downloadAsset( $url, $extension ) {
        // check if user wants to download discovered assets

        $crawlable_filetypes = [
            'css',
            'img',
            'jpeg',
            'png',
            'gif',
            // move to helper
        ];

        // add filter to allow user to specify extra downloadable extensions

        // check if supported filetype for crawling
        foreach ( $crawlable_filetypes as $filetype ) {
            // error_log('testing filetype: ' . $filetype);
            if ( $extension == $filetype ) {
                // get url without Site URL
                $save_path = str_replace(
                    $this->site_url,
                    '',
                    $url
                );

                $filename = SiteInfo::getPath( 'uploads' ) .
                    'wp2static-exported-site/' .
                    $save_path;

                // check if file exists on disk
                if ( is_file( $filename ) ) {
                    return;
                }

                // we now havbe something like
                // wp-content/plugins/elementor-pro/assets/css/frontend.min.css

                $curl_options = [];

                if ( isset( $this->settings['crawlPort'] ) ) {
                    $curl_options[ CURLOPT_PORT ] =
                        $this->settings['crawlPort'];
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

                if ( is_array( $response ) ) {
                    $ch = $response['ch'];
                    $body = $response['body'];
                }

                $basename = basename( $filename );

                $dir_without_filename = str_replace(
                    $basename,
                    $filename,
                    $filename
                );

                if ( ! is_dir( $dir_without_filename ) ) {
                    wp_mkdir_p( $dir_without_filename );
                }

                $result = file_put_contents(
                    $filename,
                    $body
                );

                if ( ! $result ) {
                    error_log( 'attempting to save' . $filename );
                }
            }
        }
    }
}

