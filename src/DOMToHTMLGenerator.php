<?php

namespace WP2Static;

use DOMDocument;

class DOMToHTMLGenerator {

    private $user_rewrite_rules;
    private $rewrite_rules;
    private $settings;

    public function getHTML(
        DOMDocument $xml_doc,
        bool $force_https = false,
        bool $force_rewrite = false
    ) : string {
        $processed_html = $xml_doc->saveHtml();

        if ( ! is_string( $processed_html ) ) {
            return '';
        }

        // TODO: here is where we convertToSiteRelativeURLs, as this can be
        // bulk performed, just stripping the domain when rewriting

        // TODO: allow for user-defined rewrites to be done after all other
        // rewrites - enables fixes for situations where certain links haven't
        // been rewritten / arbitrary rewriting of any URLs, even external
        // allow filter here, for 3rd party development
        if ( $this->user_rewrite_rules ) {
            $rewrite_rules =
                RewriteRules::getUserRewriteRules( $this->user_rewrite_rules );

            if ( ! is_string( $processed_html ) ) {
                return '';
            }

            $processed_html = str_replace(
                $rewrite_rules['from'],
                $rewrite_rules['to'],
                $processed_html
            );
        }

        if ( ! is_string( $processed_html ) ) {
            return '';
        }

        if ( $force_https ) {
            $processed_html = str_replace(
                'http://',
                'https://',
                $processed_html
            );
        }

        if ( ! is_string( $processed_html ) ) {
            return '';
        }

        if ( $force_rewrite ) {
            $processed_html = str_replace(
                $this->rewrite_rules['site_url_patterns'],
                $this->rewrite_rules['destination_url_patterns'],
                $processed_html
            );
        }

        if ( ! is_string( $processed_html ) ) {
            return '';
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

    public function shouldCreateBaseHREF() : bool {
        if ( empty( $this->settings['baseHREF'] ) ) {
            return false;
        }

        // NOTE: base HREF should not be set when creating an offline ZIP
        if ( isset( $this->settings['allowOfflineUsage'] ) ) {
            return false;
        }

        return true;
    }
}
