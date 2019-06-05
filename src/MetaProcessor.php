<?php

namespace WP2Static;

use DOMElement;

class MetaProcessor {

    private $settings;
    private $site_url;
    private $site_url_host;
    private $page_url;
    private $rewrite_rules;
    private $includeDiscoveredAssets;
    private $asset_downloader;

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
        bool $includeDiscoveredAssets,
        AssetDownloader $asset_downloader
    ) {
        $this->site_url = $site_url;
        $this->site_url_host = $site_url_host;
        $this->page_url = $page_url;
        $this->rewrite_rules = $rewrite_rules;
        $this->includeDiscoveredAssets = $includeDiscoveredAssets;
        $this->asset_downloader = $asset_downloader;
    }

    public function processMeta( DOMElement $element ) : void {
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

        $url_rewriter = new URLRewriter(
            $this->site_url,
            $this->site_url_host,
            $this->page_url,
            $this->rewrite_rules,
            $this->includeDiscoveredAssets,
            $this->asset_downloader,
        );
        $url_rewriter->processElementURL( $element );
    }
}
