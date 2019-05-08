<?php

namespace WP2Static;

use Exception;

class TXTProcessor extends Base {

    public function __construct() {
        $this->loadSettings(
            array(
                'crawling',
                'wpenv',
                'processing',
                'advanced',
            )
        );

    }

    public function processTXT( $txt_document, $page_url ) {
        if ( $txt_document == '' ) {
            return false;
        }

        $this->txt_doc = $txt_document;

        $this->destination_protocol =
            $this->getTargetSiteProtocol( $this->settings['baseUrl'] );

        $this->placeholder_url =
            $this->destination_protocol . 'PLACEHOLDER.wpsho/';

        $this->rewriteSiteURLsToPlaceholder();

        return true;
    }

    public function getTXT() {
        $processed_txt = $this->txt_doc;

        $processed_txt = $this->detectEscapedSiteURLs( $processed_txt );
        $processed_txt = $this->detectUnchangedURLs( $processed_txt );

        return $processed_txt;
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

        // add protocol relative URL to rewrite_rules
        $this->settings['rewrite_rules'] .=
            PHP_EOL .
                URLHelper::getProtocolRelativeURL( $this->placeholder_url ) . ',' .
                URLHelper::getProtocolRelativeURL( $this->settings['baseUrl'] );

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
            $processed_txt
        );

        return $rewritten_source;
    }

    public function rewriteSiteURLsToPlaceholder() {
        $site_url = SiteInfo::getUrl( 'site' );

        if ( ! is_string( $site_url ) ) {
            $err = 'Site URL not defined ';
            WsLog::l( $err );
            throw new Exception( $err );
        }

        $patterns = array(
            $site_url,
            URLHelper::getProtocolRelativeURL(
                $site_url
            ),
            URLHelper::getProtocolRelativeURL(
                rtrim( $site_url, '/' )
            ),
            URLHelper::getProtocolRelativeURL(
                $site_url . '//'
            ),
            URLHelper::getProtocolRelativeURL(
                addcslashes( $site_url, '/' )
            ),
        );

        $replacements = array(
            $this->placeholder_url,
            URLHelper::getProtocolRelativeURL(
                $this->placeholder_url
            ),
            URLHelper::getProtocolRelativeURL(
                $this->placeholder_url
            ),
            URLHelper::getProtocolRelativeURL(
                $this->placeholder_url . '/'
            ),
            URLHelper::getProtocolRelativeURL(
                addcslashes( $this->placeholder_url, '/' )
            ),
        );

        // catch any http links on an https WP site
        if ( $this->destination_protocol === 'https' ) {
            $patterns[] = str_replace(
                'http:',
                'https:',
                $site_url
            );

            $replacements[] = $this->placeholder_url;
        }

        $rewritten_source = str_replace(
            $patterns,
            $replacements,
            $this->txt_doc
        );

        $this->txt_doc = $rewritten_source;
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
}

