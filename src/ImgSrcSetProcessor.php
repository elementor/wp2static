<?php

namespace WP2Static;

use DOMElement;

class ImgSrcSetProcessor {

    private $allow_offline_usage;
    private $asset_downloader;
    private $destination_url;
    private $include_discovered_assets;
    private $page_url;
    private $rewrite_rules;
    private $site_url;
    private $site_url_host;
    private $use_document_relative_urls;
    private $use_site_root_relative_urls;

    /**
     * ImgSrcSetProcessor constructor
     *
     * @param mixed[] $rewrite_rules URL rewrite rules
     */
    public function __construct(
        string $site_url,
        string $site_url_host,
        string $page_url,
        string $destination_url,
        bool $allow_offline_usage,
        bool $use_document_relative_urls,
        bool $use_site_root_relative_urls,
        array $rewrite_rules,
        bool $include_discovered_assets,
        AssetDownloader $asset_downloader
    ) {
        $this->site_url = $site_url;
        $this->site_url_host = $site_url_host;
        $this->page_url = $page_url;
        $this->destination_url = $destination_url;
        $this->allow_offline_usage = $allow_offline_usage;
        $this->use_document_relative_urls = $use_document_relative_urls;
        $this->use_site_root_relative_urls = $use_site_root_relative_urls;
        $this->rewrite_rules = $rewrite_rules;
        $this->include_discovered_assets = $include_discovered_assets;
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
                $this->destination_url,
                $this->allow_offline_usage,
                $this->use_document_relative_urls,
                $this->use_site_root_relative_urls,
                $this->page_url,
                $this->rewrite_rules,
                $this->include_discovered_assets,
                $this->asset_downloader
            );

            $url = $url_rewriter->rewriteLocalURL( $url );

            $new_src_set[] = "{$url} {$dimension}";
        }

        $element->setAttribute( 'srcset', implode( ',', $new_src_set ) );
    }
}
