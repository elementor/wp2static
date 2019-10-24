<?php

namespace WP2Static;

use DOMElement;

class MetaProcessor {

    private $page_url;
    private $url_rewriter;

    /**
     * MetaProcessor constructor
     *
     * @param mixed[] $rewrite_rules URL rewrite rules
     */
    public function __construct(
        string $page_url,
        URLRewriter $url_rewriter
    ) {
        $this->page_url = $page_url;
        $this->url_rewriter = $url_rewriter;
    }

    public function processMeta( DOMElement $element ) : void {
        $meta_name = $element->getAttribute( 'name' );

        // TODO: detect meta redirects here + build list for rewriting
        if ( $this->remove_wp_meta ) {
            if ( strpos( $meta_name, 'generator' ) !== false ) {
                $element->parentNode->removeChild( $element );

                return;
            }
        }

        if ( $this->remove_robots_noindex ) {
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
