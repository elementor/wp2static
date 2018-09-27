<?php
// TODO: rewerite to be one loop of all elements, 
// applying multiple transformations at once per link, reducing iterations


// TODO: deal with inline CSS blocks or style attributes on tags
// TODO: don't rewrite mailto links unless specified, re #30
class HTMLProcessor {

    public function processHTML(
        $html_document,
        $page_url,
        $wp_site_env,
        $new_site_paths,
        $wp_site_url,
        $baseUrl,
        $allowOfflineUsage,
        $useRelativeURLs,
        $useBaseHref
        ) {

        // instantiate the XML body here
        $this->xml_doc = new DOMDocument();
        $this->raw_html = $html_document;
        $this->page_url = $page_url;
        $this->wp_site_env = $wp_site_env;
        $this->new_site_paths = $new_site_paths;
        $this->wp_site_url = $wp_site_url;
        $this->baseUrl = $baseUrl;
        $this->allowOfflineUsage = $allowOfflineUsage;
        $this->useRelativeURLs = $useRelativeURLs;
        $this->useBaseHref = $useBaseHref;

        require_once dirname( __FILE__ ) . '/../URL2/URL2.php';
        $this->page_url = new Net_URL2( $page_url );

        $this->discoverNewURLs = (
            isset( $_POST['discoverNewURLs'] ) &&
             $_POST['discoverNewURLs'] == 1 &&
             $_POST['ajax_action'] === 'crawl_site'
        );

        if ( $this->discoverNewURLs ) {
            $this->discovered_urls = [];
            // $this->excludes_list = [];
            // $this->includes_list = [];
            $this->wp_uploads_path = $_POST['wp_uploads_path'];
            $this->working_directory =
                isset( $_POST['workingDirectory'] ) ?
                $_POST['workingDirectory'] :
                $this->wp_uploads_path;
        }

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
            error_log(print_r($element, true));

            switch ( $element->tagName ) {
                case 'meta':
                    $this->processMeta($element);
                    break;
                case 'a':
                    $this->processAnchor($element);
                    break;
                case 'img':
                    $this->processImage($element);
                    break;
                case 'link':
                    // NOTE: not to confuse with anchor element
                    $this->processLink($element);
                    break;

            }
        }

        $this->removeQueryStringsFromInternalLinks();
        $this->rewriteWPPaths();

        // funcs to apply to whole page
        $this->detectEscapedSiteURLs();

        $processor->replaceBaseUrl(
            $this->wp_site_url,
            $this->baseUrl,
            $this->allowOfflineUsage,
            $this->useRelativeURLs,
            $this->useBaseHref
        );

        // TODO: this whole 1-layer only discovery needs to be redone
        //       was a quick add-on to get some real crawling in 5.9 
        if ( $this->discoverNewURLs && $_POST['ajax_action'] === 'crawl_site' ) {
            $processor->writeDiscoveredURLs();
        }
    }

    public function processLink( $element ) {
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

        $link_rel = $element->getAttribute( 'rel' );

        if ( in_array( $link_rel, $relativeLinksToRemove ) ) {
            $element->parentNode->removeChild( $element );
        } elseif ( strpos( $link_rel, '.w.org' ) !== false ) {
            $element->parentNode->removeChild( $element );
        }
    }

    public function addDiscoveredURL( $url ) {
        if ( $this->discoverNewURLs ) {
            $this->discovered_urls[] = $url;
        }
    }

    public function processImage( $element ) {
        $this->normalizeURL( $element, 'src' );
        $this->discovered_urls[] = $element->getAttribute( 'src' );
        $this->rewriteWPPaths( $element );
        $this->rewriteBaseURL( $element );
        
    }

    public function processAnchor( $element ) {
        $this->normalizeURL( $element, 'href' );
        $this->discovered_urls[] = $element->getAttribute( 'href' );
        $this->rewriteWPPaths( $element );
        $this->rewriteBaseURL( $element );
        
    }

    public function processMeta($element) {
        $meta_name = $element->getAttribute( 'name' );

        if ( strpos( $meta_name, 'generator' ) !== false ) {
            $element->parentNode->removeChild( $element );
        }
    }

    public function writeDiscoveredURLs() {
        if ( ! $this->discoverNewURLs &&
            $_POST['ajax_action'] === 'crawl_site' ) {
            return;
        }

        file_put_contents(
            $this->working_directory . '/WP-STATIC-DISCOVERED-URLS',
            PHP_EOL .
                implode( PHP_EOL, array_unique( $this->discovered_urls ) ),
            FILE_APPEND | LOCK_EX
        );
    }

    // make all links absolute and relative to the current document
    public function normalizeURL($element, $attribute) {
        $original_link = $element->getAttribute( $attribute );

        if ( $this->isInternalLink( $original_link ) ) {
            $abs = $this->page_url->resolve( $original_link );
            $element->setAttribute( $attribute, $abs );
        }
    }

    public function cleanup() {
    }

    public function isInternalLink( $link ) {
        // TODO: apply only to links starting with .,..,/,
        // or any with just a path, like banana.png
        // check link is same host as $this->url and not a subdomain
        return parse_url( $link, PHP_URL_HOST ) === parse_url(
            $this->wp_site_url,
            PHP_URL_HOST
        );
    }

    public function removeQueryStringsFromInternalLinks() {
        // TODO: benchmark 3 calls vs * elements perf
        foreach ( $this->xml_doc->getElementsByTagName( 'a' ) as $link ) {
            $link_href = $link->getAttribute( 'href' );

            // check if it's an internal link not a subdomain
            if ( $this->isInternalLink( $link_href ) ) {
                // strip anything from the ? onwards
                // https://stackoverflow.com/a/42476194/1668057
                $link->setAttribute( 'href', strtok( $link_href, '?' ) );
            }
        }

        foreach ( $this->xml_doc->getElementsByTagName( 'img' ) as $link ) {
            $link_href = $link->getAttribute( 'src' );

            // check if it's an internal link not a subdomain
            if ( $this->isInternalLink( $link_href ) ) {
                // strip anything from the ? onwards
                // https://stackoverflow.com/a/42476194/1668057
                $link->setAttribute( 'src', strtok( $link_href, '?' ) );
            }
        }

        foreach ( $this->xml_doc->getElementsByTagName( 'script' ) as $link ) {
            $link_href = $link->getAttribute( 'src' );

            // check if it's an internal link not a subdomain
            if ( $this->isInternalLink( $link_href ) ) {
                // strip anything from the ? onwards
                // https://stackoverflow.com/a/42476194/1668057
                $link->setAttribute( 'src', strtok( $link_href, '?' ) );
            }
        }
    }


    public function detectEscapedSiteURLs() {
        // NOTE: this does return the expected http:\/\/172.18.0.3
        // but the PHP error log will escape again and
        // show http:\\/\\/172.18.0.3
        $escaped_site_url = addcslashes( get_option( 'siteurl' ), '/' );

        // if ( strpos( $this->raw_html, $escaped_site_url ) !== false ) {
        // TODO: renable this function being called. needs to be on
        // raw HTML, so ideally after the Processor has done all other
        // XML things, so no need to parse again
        // suggest adding a processRawHTML function, that
        // includes this stuff.. and call it within the getHTML function
        // or finalizeProcessing or such....
        // $this->rewriteEscapedURLs($wp_site_env,
        // $new_site_paths);
        // }
    }

    public function rewriteEscapedURLs() {
        /*
        This function will be a bit more costly. To cover bases like:

         data-images="[&quot;https:\/\/mysite.example.com\/wp...
        from the onepress(?) theme, for example

        */

        $rewritten_source = str_replace(
            array(
                addcslashes( $wp_site_env['wp_active_theme'], '/' ),
                addcslashes( $wp_site_env['wp_themes'], '/' ),
                addcslashes( $wp_site_env['wp_uploads'], '/' ),
                addcslashes( $wp_site_env['wp_plugins'], '/' ),
                addcslashes( $wp_site_env['wp_content'], '/' ),
                addcslashes( $wp_site_env['wp_inc'], '/' ),
            ),
            array(
                addcslashes( $new_site_paths['new_active_theme_path'], '/' ),
                addcslashes( $new_site_paths['new_themes_path'], '/' ),
                addcslashes( $new_site_paths['new_uploads_path'], '/' ),
                addcslashes( $new_site_paths['new_plugins_path'], '/' ),
                addcslashes( $new_site_paths['new_wp_content_path'], '/' ),
                addcslashes( $new_site_paths['new_wpinc_path'], '/' ),
            ),
            $this->response['body']
        );

        $this->setResponseBody( $rewritten_source );
    }

    public function rewriteWPPaths( $element ) {
        $attribute_to_change = '';
        $url_to_change = '';

        if ( $element->hasAttribute( 'href' ) ) {
            $attribute_to_change = 'href';
        } elseif ( $element->hasAttribute( 'src' ) ) {
            $attribute_to_change = 'src';
            // skip elements without href or src
        } else {
            return;
        }

        $url_to_change = $element->getAttribute( $attribute_to_change );

        if ( $this->isInternalLink( $url_to_change ) ) {
            // rewrite URLs, starting with longest paths down to shortest
            $rewritten_url = str_replace(
                array(
                    $this->wp_site_env['wp_active_theme'],
                    $this->wp_site_env['wp_themes'],
                    $this->wp_site_env['wp_uploads'],
                    $this->wp_site_env['wp_plugins'],
                    $this->wp_site_env['wp_content'],
                    $this->wp_site_env['wp_inc'],
                ),
                array(
                    $this->new_site_paths['new_active_theme_path'],
                    $this->new_site_paths['new_themes_path'],
                    $this->new_site_paths['new_uploads_path'],
                    $this->new_site_paths['new_plugins_path'],
                    $this->new_site_paths['new_wp_content_path'],
                    $this->new_site_paths['new_wpinc_path'],
                ),
                $url_to_change
            );

            $element->setAttribute( $attribute_to_change, $rewritten_url );
        }
    }

    public function getHTML() {
        return $this->xml_doc->saveHtml();
    }

    // NOTE: separate from WP rewrites in case people have disabled that
    public function rewriteBaseURL( $element ) {
        if ( $element->hasAttribute( 'href' ) ) {
            $attribute_to_change = 'href';
        } elseif ( $element->hasAttribute( 'src' ) ) {
            $attribute_to_change = 'src';
            // skip elements without href or src
        } else {
            return;
        }

        $url_to_change = $element->getAttribute( $attribute_to_change );

        // check it actually needs to be changed
        // TODO: is this check not done already by isInternalLink()?
        if ( strpos( $url_to_change, $this->wp_site_url ) !== false ) {
            $rewritten_url = str_replace(
                $this->wp_site_url,
                $this->baseUrl,
                $url_to_change
            );

            $element->setAttribute( $attribute_to_change, $rewritten_url );
        }

        if ( $absolutePaths ) {

            // TODO: re-implement as separate func as another processing layer
            // error_log('SKIPPING absolute path rewriting');
            if ( $this->useBaseHref ) {
                $responseBody = str_replace(
                    '<head>',
                    "<head>\n<base href=\"" .
                    esc_attr( $new_URL ) . "/\" />\n",
                    $responseBody
                );
            } else {
                $responseBody = str_replace(
                    '<head>',
                    "<head>\n<base href=\"/\" />\n",
                    $responseBody
                );
            }
        }

        // elseif ( $allowOfflineUsage ) {
        // detect urls starting with our domain and append index.html to
        // the end if they end in /
        // TODO: re-implement as separate function as
        // another processing layer
        // error_log('SKIPPING offline usage rewriting');
        // foreach($xml->getElementsByTagName('a') as $link) {
        // $original_link = $link->getAttribute("href");
        //
        // process links from our site only
        // if (strpos($original_link, $oldDomain) !== false) {
        //
        // }
        //
        // $link->setAttribute('href', $original_link . 'index.html');
        // }
        //
        // }
        // TODO: should we check for incorrectly linked references?
        // like an http link on a WP site served from https? - probably not
    }
}

