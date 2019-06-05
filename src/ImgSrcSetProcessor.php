<?php

namespace WP2Static;

use DOMElement;

class ImgSrcSetProcessor {

    private $site_url;
    private $site_url_host;
    private $page_url;
    private $rewrite_rules;
    private $includeDiscoveredAssets;
    private $asset_downloader;

    /**
     * ImgSrcSetProcessor constructor
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

    public function processImageSrcSet( DOMElement $element ) : void {
        if ( ! $element->hasAttribute( 'srcset' ) ) {
            return;
        }

        $new_src_set = array();

        $src_set = $element->getAttribute( 'srcset' );

        $src_set_lines = explode( ',', $src_set );

        foreach ( $src_set_lines as $src_set_line ) {
            $all_pieces = explode( ' ', $src_set_line );

            // rm empty elements
            $pieces = array_filter( $all_pieces );

            // reindex array
            $pieces = array_values( $pieces );

            $url = $pieces[0];
            $dimension = $pieces[1];

            $url_rewriter = new URLRewriter(
                $this->site_url,
                $this->site_url_host,
                $this->page_url,
                $this->rewrite_rules,
                $this->includeDiscoveredAssets,
                $this->asset_downloader,
            );

            $url = $url_rewriter->rewriteLocalURL( $url );

            $new_src_set[] = "{$url} {$dimension}";
        }

        $element->setAttribute( 'srcset', implode( ',', $new_src_set ) );
    }
}
