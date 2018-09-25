<?php
/**
 * HTMLProcessor
 *
 * @package WP2Static
 */
class HTMLProcessor {

    /**
     * TODO: deal with inline CSS blocks or style attributes on tags
     */
    /**
     * TODO: don't rewrite mailto links unless specified, re #30
     */

    /**
     * Constructor
     *
     * @param string $html_document HTML document
     * @param string $wp_site_url   Site URL
     */
    public function __construct( $html_document, $wp_site_url ) {
        $this->wp_site_url = $wp_site_url;

        // instantiate the XML body here
        $this->xml_doc = new DOMDocument();
        $this->raw_html = $html_document;

        // PERF: 70% of function time
        // prevent warnings, via https://stackoverflow.com/a/9149241/1668057
        libxml_use_internal_errors( true );
        $this->xml_doc->loadHTML( $html_document );
        libxml_use_internal_errors( false );
    }


    /**
     * Make all links absolute and relative to the current document
     *
     * @param string $url URL
     * @return void
     */
    public function normalizeURLs( $url ) {
        require_once dirname( __FILE__ ) . '/../URL2/URL2.php';
        $base = new Net_URL2( $url );

        foreach ( $this->xml_doc->getElementsByTagName( 'a' ) as $link ) {
            $original_link = $link->getAttribute( 'href' );

            if ( $this->isInternalLink( $original_link ) ) {
                /**
                 * TODO: apply only to links starting with .,..,/, or any
                 * TODO: ... with just a path, like banana.png
                 */
                $abs = $base->resolve( $original_link );
                $link->setAttribute( 'href', $abs );
            }
        }
    }


    /**
     * Cleanup
     *
     * @param string $wp_site_environment    Site environment
     * @param array  $overwrite_slug_targets Target slugs
     * @return void
     */
    public function cleanup( $wp_site_environment, $overwrite_slug_targets ) {
        $this->stripWPMetaElements();
        $this->stripWPLinkElements();
        $this->removeQueryStringsFromInternalLinks();
        $this->rewriteWPPaths( $wp_site_environment, $overwrite_slug_targets );
        $this->detectEscapedSiteURLs(
            $wp_site_environment,
            $overwrite_slug_targets
        );
    }


    /**
     * Check whether link is internal
     *
     * @param string $link Link
     * @return boolean
     */
    public function isInternalLink( $link ) {
        // check link is same host as $this->url and not a subdomain
        $url_host = parse_url( $link, PHP_URL_HOST );
        $url_site = parse_url( $this->wp_site_url, PHP_URL_HOST );
        return $url_host == $url_site;
    }


    /**
     * Remove query string from internal links
     *
     * @return void
     */
    public function removeQueryStringsFromInternalLinks() {
        foreach ( $this->xml_doc->getElementsByTagName( 'a' ) as $link ) {
            $link_href = $link->getAttribute( 'href' );

            // check if it's an internal link not a subdomain
            if ( $this->isInternalLink( $link_href ) ) {
                // strip anything from the ? onwards
                // https://stackoverflow.com/a/42476194/1668057
                $link->setAttribute( 'href', strtok( $link_href, '?' ) );
            }
        }
    }


    /**
     * Strip WordPress meta elements
     *
     * @return void
     */
    public function stripWPMetaElements() {
        $metas = iterator_to_array(
            $this->xml_doc->getElementsByTagName( 'meta' )
        );

        foreach ( $metas as $meta ) {
            $meta_name = $meta->getAttribute( 'name' );

            if ( strpos( $meta_name, 'generator' ) !== false ) {
                $meta->parentNode->removeChild( $meta );
            }
        }
    }


    /**
     * Strip WordPress link elements
     *
     * @return void
     */
    public function stripWPLinkElements() {
        $relativeLinksToRemove = array(
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

        $links = iterator_to_array(
            $this->xml_doc->getElementsByTagName( 'link' )
        );

        foreach ( $links as $link ) {
            $link_rel = $link->getAttribute( 'rel' );
            if ( in_array( $link_rel, $relativeLinksToRemove ) ) {
                $link->parentNode->removeChild( $link );
            } elseif ( strpos( $link_rel, '.w.org' ) !== false ) {
                $link->parentNode->removeChild( $link );
            }
        }
    }


    /**
     * Detect escaped site URLs
     *
     * @param string $wp_site_environment    Site environment
     * @param array  $overwrite_slug_targets Target slugs
     * @return void
     */
    public function detectEscapedSiteURLs(
        $wp_site_environment,
        $overwrite_slug_targets
    ) {
        // NOTE: this does return the expected http:\/\/172.18.0.3
        // but the PHP error log will escape again and show
        // http:\\/\\/172.18.0.3
        $escaped_site_url = addcslashes( get_option( 'siteurl' ), '/' );

        // if ( strpos( $this->raw_html, $escaped_site_url ) !== false ) {
            /**
             * TODO: renable this function being called. needs to be on raw
             * TODO: ... HTML, so ideally after the Processor has done all
             * TODO: ... other XML things, so no need to parse again
             */
            /**
             * suggest adding a processRawHTML function, that includes this
             * stuff.. and call it within the getHTML function or
             * finalizeProcessing or such....
             *
             * // leave this error log in until fixed
             * error_log('skipping rewriting of escaped URLs');
             *
             * $this->rewriteEscapedURLs(
             *     $wp_site_environment,
             *     $overwrite_slug_targets
             * );
             */
        // }
    }


    /**
     * Rewrite escaped URLs
     *
     * @param string $wp_site_environment    Site environment
     * @param array  $overwrite_slug_targets Target slugs
     * @return void
     */
    public function rewriteEscapedURLs(
        $wp_site_environment,
        $overwrite_slug_targets
    ) {
        /**
         * This function will be a bit more costly. To cover bases like:
         *
         * <section
         *     id="hero"
         *     data-images="[&quot;https:\/\/mysite.example.com\/wp-content\/
         *                  themes\/onepress\/assets\/images\/hero5.jpg&quot;]"
         *     class="hero-slideshow-wrapper hero-slideshow-normal"
         *     >
         *
         * from the onepress(?) theme, for example
         */

        $rewritten_source = str_replace(
            array(
                addcslashes( $wp_site_environment['wp_active_theme'], '/' ),
                addcslashes( $wp_site_environment['wp_themes'], '/' ),
                addcslashes( $wp_site_environment['wp_uploads'], '/' ),
                addcslashes( $wp_site_environment['wp_plugins'], '/' ),
                addcslashes( $wp_site_environment['wp_content'], '/' ),
                addcslashes( $wp_site_environment['wp_inc'], '/' ),
            ),
            array(
                addcslashes(
                    $overwrite_slug_targets['new_active_theme_path'],
                    '/'
                ),
                addcslashes( $overwrite_slug_targets['new_themes_path'], '/' ),
                addcslashes(
                    $overwrite_slug_targets['new_uploads_path'],
                    '/'
                ),
                addcslashes(
                    $overwrite_slug_targets['new_plugins_path'],
                    '/'
                ),
                addcslashes(
                    $overwrite_slug_targets['new_wp_content_path'],
                    '/'
                ),
                addcslashes( $overwrite_slug_targets['new_wpinc_path'], '/' ),
            ),
            $this->response['body']
        );

        $this->setResponseBody( $rewritten_source );
    }


    /**
     * Rewrite WordPress paths
     *
     * @param string $wp_site_environment    Site environment
     * @param array  $overwrite_slug_targets Target slugs
     */
    public function rewriteWPPaths(
        $wp_site_environment,
        $overwrite_slug_targets
    ) {
        // NOTE: drier code but costlier memory usage
        foreach ( $this->xml_doc->getElementsByTagName( '*' ) as $element ) {
            $attribute_to_change = '';
            $url_to_change = '';

            if ( $element->hasAttribute( 'href' ) ) {
                $attribute_to_change = 'href';
            } elseif ( $element->hasAttribute( 'src' ) ) {
                $attribute_to_change = 'src';
                // skip elements without href or src
            } else {
                continue;
            }

            $url_to_change = $element->getAttribute( $attribute_to_change );

            if ( $this->isInternalLink( $url_to_change ) ) {
                /**
                 * rewrite all the things, starting with longest paths down
                 * to shortest
                 */
                $rewritten_url = str_replace(
                    array(
                        $wp_site_environment['wp_active_theme'],
                        $wp_site_environment['wp_themes'],
                        $wp_site_environment['wp_uploads'],
                        $wp_site_environment['wp_plugins'],
                        $wp_site_environment['wp_content'],
                        $wp_site_environment['wp_inc'],
                    ),
                    array(
                        $overwrite_slug_targets['new_active_theme_path'],
                        $overwrite_slug_targets['new_themes_path'],
                        $overwrite_slug_targets['new_uploads_path'],
                        $overwrite_slug_targets['new_plugins_path'],
                        $overwrite_slug_targets['new_wp_content_path'],
                        $overwrite_slug_targets['new_wpinc_path'],
                    ),
                    $url_to_change
                );

                $element->setAttribute( $attribute_to_change, $rewritten_url );
            }//end if
        }//end foreach
    }


    /**
     * Get HTML
     *
     * @return string
     */
    public function getHTML() {
        return $this->xml_doc->saveHtml();

    }

    /**
     * Replace base URL
     *
     * @param string  $oldBaseUrl        Old base URL
     * @param string  $newBaseUrl        New base URL
     * @param boolean $allowOfflineUsage Offline usage flag
     * @param boolean $absolutePaths     Absolute path flag
     * @param boolean $useBaseHref       Base href flag
     * @return void
     */
    public function replaceBaseUrl(
        $oldBaseUrl,
        $newBaseUrl,
        $allowOfflineUsage,
        $absolutePaths = false,
        $useBaseHref = true
    ) {
        // TODO: damn, this is going to take a while to cleanup...
        /**
         * TODO: some optimization to be gained by doing this in same
         * TODO: ... loop as rewriteWPPaths loop
         */
        // error_log('old base url: ' . $oldBaseUrl);
        // error_log('new base url: ' . $newBaseUrl);
        foreach ( $this->xml_doc->getElementsByTagName( '*' ) as $element ) {
            if ( $element->hasAttribute( 'href' ) ) {
                $attribute_to_change = 'href';
            } elseif ( $element->hasAttribute( 'src' ) ) {
                $attribute_to_change = 'src';
                // skip elements without href or src
            } else {
                continue;
            }

            $url_to_change = $element->getAttribute( $attribute_to_change );

            // check it actually needs to be changed
            if ( strpos( $url_to_change, $oldBaseUrl ) !== false ) {
                $rewritten_url = str_replace(
                    $oldBaseUrl,
                    $newBaseUrl,
                    $url_to_change
                );

                $element->setAttribute( $attribute_to_change, $rewritten_url );
            }
        }//end foreach

        if ( $absolutePaths ) {

            /**
             * TODO: re-implement as separate function as another processing
             * TODO: ... layer
             */
            // error_log('SKIPPING absolute path rewriting');
            if ( $useBaseHref ) {
                $headHTML = "<head>\n<base href=\"" .
                    esc_attr( $newBaseUrl ) . "/\" />\n";
                $responseBody = str_replace(
                    '<head>',
                    $headHTML,
                    $responseBody
                );
            } else {
                $responseBody = str_replace(
                    '<head>',
                    "<head>\n<base href=\"/\" />\n",
                    $responseBody
                );
            }
            // } elseif ( $allowOfflineUsage ) {
            /**
             * detect urls starting with our domain and append index.html to
             * the end if they end in /
             *
             * TODO: re-implement as separate function as another processing
             * TODO: ... layer
             * error_log('SKIPPING offline usage rewriting');
             * foreach($xml->getElementsByTagName('a') as $link) {
             * $original_link = $link->getAttribute("href");
             * // process links from our site only
             * if (strpos($original_link, $oldDomain) !== false) {
             * }
             *
             * $link->setAttribute('href', $original_link . 'index.html');
             * }
             */
        }//end if

        /**
         * TODO: should we check for incorrectly linked references?
         * TODO: ... like an http link on a WP site served from https?
         * TODO: ... probably not
         */
    }
}


