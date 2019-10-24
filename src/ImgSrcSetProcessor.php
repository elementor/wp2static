<?php

namespace WP2Static;

use DOMElement;

class ImgSrcSetProcessor {

    private $asset_downloader;
    private $page_url;

    /**
     * ImgSrcSetProcessor constructor
     *
     * @param mixed[] $rewrite_rules URL rewrite rules
     */
    public function __construct(
        string $page_url,
        AssetDownloader $asset_downloader
    ) {
        $this->page_url = $page_url;
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
                $this->page_url,
                $this->asset_downloader
            );

            $url = $url_rewriter->rewriteLocalURL( $url );

            $new_src_set[] = "{$url} {$dimension}";
        }

        $element->setAttribute( 'srcset', implode( ',', $new_src_set ) );
    }
}
