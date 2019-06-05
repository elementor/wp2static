<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;
use DOMDocument;

// mock AssetDownloader
class AssetDownloader {
    public function downloadAsset( $url, $extension ) {
        //
    }

}

final class URLRewriterTest extends TestCase {

    public function testprocessElementURL() {
        $dom = new DOMDocument('1.0', 'utf-8');

        // create parent head 
        $head = $dom->createElement( 'head', ' ' );
        $body = $dom->createElement( 'body', ' ' );

        // create anchor
        $anchor = $dom->createElement('a', 'Some anchor text');
        $anchor->setAttribute('href', 'https://somedomain.com/alink') ;
        $body->appendChild( $anchor );

        // create image
        $img = $dom->createElement('img');
        $img->setAttribute('src', 'https://somedomain.com/an/image.png') ;
        $body->appendChild( $img );

        $html = $dom->createElement( 'html' );
        $html->appendChild( $head );
        $html->appendChild( $body );
        $dom->appendChild( $html );

        $url_rewriter = new URLRewriter(
            'https://somedomain.com/', // site_url
            'somedomain.com', // site_url_host
            'https://somedomain.com/apage', // $page_url
            [
                'site_url_patterns' => [
                    'https://somedomain.com',
                ],
                'destination_url_patterns' => [
                    'https://google.com',
                ],
            ], // $rewrite_rules,
            true, // $includeDiscoveredAssets,
        );

        $url_rewriter->processElementURL( $anchor );
        $url_rewriter->processElementURL( $img );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head> </head><body> <a href="https://google.com/alink">Some anchor text</a><img src="https://google.com/an/image.png"></body></html>' . PHP_EOL,
            $result
        );
    }
}
