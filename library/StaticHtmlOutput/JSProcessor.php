<?php


class JSProcessor {

    public function __construct( $js_document, $wp_site_url ) {
        $this->wp_site_url = $wp_site_url;
        $this->js_doc = $js_document;
    }

    public function isInternalLink( $link ) {
        // check link is same host as $this->url and not a subdomain
        return parse_url( $link, PHP_URL_HOST ) ===
            parse_url( $this->wp_site_url, PHP_URL_HOST );
    }

    public function normalizeURLs( $url ) {
        // do nothing yet
    }

    public function getJS() {
        return $this->js_doc;
    }
}

