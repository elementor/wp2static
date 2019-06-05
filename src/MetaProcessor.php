<?php

namespace WP2Static;

use DOMElement;

class MetaProcessor {

    private $settings;
    private $site_url;
    private $site_url_host;
    private $page_url;
    private $rewrite_rules;
    private $include_discovered_assets;
    private $remove_wp_meta;
    private $url_rewriter;

    /**
     * MetaProcessor constructor
     *
     * @param mixed[] $rewrite_rules URL rewrite rules
     */
    public function __construct(
        string $site_url,
        string $site_url_host,
        string $page_url,
        array $rewrite_rules,
        bool $include_discovered_assets,
        bool $remove_wp_meta,
        URLRewriter $url_rewriter
    ) {
        $this->site_url = $site_url;
        $this->site_url_host = $site_url_host;
        $this->page_url = $page_url;
        $this->rewrite_rules = $rewrite_rules;
        $this->include_discovered_assets = $include_discovered_assets;
        $this->remove_wp_meta = $remove_wp_meta;
        $this->url_rewriter = $url_rewriter;
    }

    public function processMeta( DOMElement $element ) : void {
        // TODO: detect meta redirects here + build list for rewriting
        if ( isset( $this->remove_wp_meta ) ) {
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

        $this->url_rewriter->processElementURL( $element );
    }
}
