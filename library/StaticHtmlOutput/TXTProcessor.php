<?php

class TXTProcessor {

    public function processTXT( $txt_document, $page_url ) {
        if ( $txt_document == '' ) {
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

        $this->txt_doc = $txt_document;
        $this->placeholder_url = 'https://PLACEHOLDER.wpsho/';

        $this->rewriteSiteURLsToPlaceholder();

        return true;
    }

    public function getTXT() {
        $processed_txt = $this->txt_doc;

        $processed_txt = $this->detectEscapedSiteURLs( $processed_txt );
        $processed_txt = $this->detectUnchangedURLs( $processed_txt );

        return $processed_txt;
    }

    public function loadSettings() {
        if ( defined( 'WP_CLI' ) ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/DBSettings.php';

            $this->settings =
                WPSHO_DBSettings::get( $this->target_settings );
        } else {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/PostSettings.php';

            $this->settings =
                WPSHO_PostSettings::get( $this->target_settings );
        }
    }

    public function detectEscapedSiteURLs( $processed_txt ) {
        // NOTE: this does return the expected http:\/\/172.18.0.3
        // but your error log may escape again and
        // show http:\\/\\/172.18.0.3
        $escaped_site_url = addcslashes( $this->placeholder_url, '/' );

        if ( strpos( $processed_txt, $escaped_site_url ) !== false ) {
            return $this->rewriteEscapedURLs( $processed_txt );
        }

        return $processed_txt;
    }

    public function detectUnchangedURLs( $processed_txt ) {
        $site_url = $this->placeholder_url;

        if ( strpos( $processed_txt, $site_url ) !== false ) {
            return $this->rewriteUnchangedURLs( $processed_txt );
        }

        return $processed_txt;
    }

    public function rewriteUnchangedURLs( $processed_txt ) {
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
            $processed_txt
        );

        return $rewritten_source;
    }

    public function rewriteEscapedURLs( $processed_txt ) {
        // NOTE: fix input TXT, which can have \ slashes modified to %5C
        $processed_txt = str_replace(
            '%5C/',
            '\\/',
            $processed_txt
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
            $processed_txt
        );

        return $rewritten_source;
    }

    public function rewriteSiteURLsToPlaceholder() {
        $rewritten_source = str_replace(
            array(
                $this->settings['wp_site_url'],
                addcslashes( $this->settings['wp_site_url'], '/' ),
            ),
            array(
                $this->placeholder_url,
                addcslashes( $this->placeholder_url, '/' ),
            ),
            $this->txt_doc
        );

        $this->txt_doc = $rewritten_source;
    }
}

