<?php

class JSProcessor {

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

    public function processJS( $js_document, $page_url ) {
        $target_settings = array(
            'general',
            'wpenv',
            'processing',
            'advanced',
        );

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $target_settings );
        } else {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/DBSettings.php';

            $this->settings = WPSHO_DBSettings::get( $target_settings );
        }

        $this->js_doc = $js_document;

        require_once dirname( __FILE__ ) . '/../URL2/URL2.php';
        $this->page_url = new Net_URL2( $page_url );

        $this->discoverNewURLs = (
            isset( $this->settings['discoverNewURLs'] ) &&
             $this->settings['discoverNewURLs'] == 1 &&
             $_POST['ajax_action'] === 'crawl_site'
        );

        $this->discovered_urls = [];

        // funcs to apply to whole page
        // $this->detectEscapedSiteURLs();
        // $this->setBaseHref();
        // $this->writeDiscoveredURLs();
    }
}

