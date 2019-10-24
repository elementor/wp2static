<?php

namespace WP2Static;

use DOMDocument;

class DOMToHTMLGenerator {

    private $user_rewrite_rules;
    private $rewrite_rules;
    private $settings;

    public function getHTML( DOMDocument $xml_doc ) : string {
        $processed_html = $xml_doc->saveHtml();

        if ( ! is_string( $processed_html ) ) {
            return '';
        }

        // Allow themes to modify entities to preserve
        $html_entities_to_preserve =
            apply_filters(
                'wp2static_html_entities_to_preserve',
                array( '&lt;', '&gt;' )
            );

        // Strip entities down to just the "name"
        $cleaned_entities_to_preserve =
            preg_replace( '/^&(.*?);$/', '\1', $html_entities_to_preserve );

        // Build up the regex from the entity "names"
        $preserve_entities_rx =
            sprintf(
                '/&(%s);/',
                implode( '|', $cleaned_entities_to_preserve )
            );

        // Replace entities with placeholders to preserve them
        $processed_html =
            preg_replace(
                $preserve_entities_rx,
                '{{wp2static-entity-placeholder:\1}}',
                $processed_html
            );

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

            $processed_html = str_replace(
                $rewrite_rules['from'],
                $rewrite_rules['to'],
                $processed_html
            );
        }

        if ( ! is_string( $processed_html ) ) {
            return '';
        }

        if ( ExportSettings::get( 'forceHTTPS' ) ) {
            $processed_html = str_replace(
                'http://',
                'https://',
                $processed_html
            );
        }

        if ( ! is_string( $processed_html ) ) {
            return '';
        }

        if ( ExportSettings::get( 'forceRewriteSiteURLs' ) ) {
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
}
