<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;
use DOMDocument;

final class URLRewriterTest extends TestCase {
    public function testprocessElementURL() {
        // mock the AssetDownloader class which is passed to processor
        $asset_downloader = $this->createMock(AssetDownloader::class);

        $dom = new DOMDocument('1.0', 'utf-8');

        // create parent head 
        $head = $dom->createElement( 'head', ' ' );
        $body = $dom->createElement( 'body', ' ' );

        // create anchor (get URL from href)
        $anchor = $dom->createElement('a', 'Some anchor text');
        $anchor->setAttribute('href', 'https://somedomain.com/alink') ;
        $body->appendChild( $anchor );

        // test hash anchor isn't processed
        $hash_anchor = $dom->createElement('a', 'My hash link');
        $hash_anchor->setAttribute('href', '#some-hash') ;
        $body->appendChild( $hash_anchor );

        // create image (get URL from src)
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
            'https://google.com', // destination_url
            false, // allow_offline_usage
            false, // use_document_relative_urls,
            false, // use_site_root_relative_urls,
            'https://somedomain.com/apage', // $page_url
            [
                'site_url_patterns' => [
                    'https://somedomain.com',
                ],
                'destination_url_patterns' => [
                    'https://google.com',
                ],
            ], // $rewriteRules,
            true, // $includeDiscoveredAssets,
            $asset_downloader
        );

        $url_rewriter->processElementURL( $anchor );
        $url_rewriter->processElementURL( $img );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head> </head><body> <a href="https://google.com/alink">Some anchor text</a><a href="#some-hash">My hash link</a><img src="https://google.com/an/image.png"></body></html>' . PHP_EOL,
            $result
        );
    }

    public function testprocessElementURLSkipsElementsWithoutURLOrAttribute() {
        // mock the AssetDownloader class which is passed to processor
        $asset_downloader = $this->createMock(AssetDownloader::class);

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

        // element without attribute
        $img_no_attr = $dom->createElement('img');
        $body->appendChild( $img_no_attr );

        $html = $dom->createElement( 'html' );
        $html->appendChild( $head );
        $html->appendChild( $body );
        $dom->appendChild( $html );

        $url_rewriter = new URLRewriter(
            'https://somedomain.com/', // site_url
            'somedomain.com', // site_url_host
            'https://google.com', // destination_url
            false, // allow_offline_usage
            false, // use_document_relative_urls,
            false, // use_site_root_relative_urls,
            'https://somedomain.com/apage', // $page_url
            [
                'site_url_patterns' => [
                    'https://somedomain.com',
                ],
                'destination_url_patterns' => [
                    'https://google.com',
                ],
            ], // $rewriteRules,
            true, // $includeDiscoveredAssets,
            $asset_downloader
        );

        $url_rewriter->processElementURL( $anchor );
        $url_rewriter->processElementURL( $img );
        $url_rewriter->processElementURL( $img_no_attr );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head> </head><body> <a href="https://google.com/alink">Some anchor text</a><img src="https://google.com/an/image.png"><img></body></html>' . PHP_EOL,
            $result
        );
    }

    /**
     * @dataProvider rewriteLocalURLProvider
     */
    public function testRewriteLocalURL( string $url, string $expectation ) {
        // mock the AssetDownloader class which is passed to processor
        $asset_downloader = $this->createMock(AssetDownloader::class);

        $url_rewriter = new URLRewriter(
            'https://somedomain.com/', // site_url
            'somedomain.com', // site_url_host
            'https://google.com', // destination_url
            false, // allow_offline_usage
            false, // use_document_relative_urls,
            false, // use_site_root_relative_urls,
            'https://somedomain.com/apage', // $page_url
            [
                'site_url_patterns' => [
                    'https://somedomain.com',
                ],
                'destination_url_patterns' => [
                    'https://google.com',
                ],
            ], // $rewriteRules,
            true, // $includeDiscoveredAssets,
            $asset_downloader
        );

        $result = $url_rewriter->rewriteLocalURL( $url );


        $this->assertEquals(
            $expectation,
            $result
        );
    }

    public function rewriteLocalURLProvider() {
        return [
           'doesn\'t rewrite named anchors' =>  [
                '#some-anchor',
                '#some-anchor',
            ],
           'doesn\'t rewrite mailto: anchors' =>  [
                'mailto:testemail@somedomain.com',
                'mailto:testemail@somedomain.com',
            ],
           'doesn\'t rewrite anchors not matching site_url_host' =>  [
                'https://someotherdomain.com/alink',
                'https://someotherdomain.com/alink',
            ],
           'rewrites anchors matching site_url_host' =>  [
                'https://somedomain.com/alink',
                'https://google.com/alink',
            ],
           'rewrites protocol relative URLs' =>  [
                '//somedomain.com/alink',
                'https://google.com/alink',
            ],
           'rewrites site root relative URLs' =>  [
                '/alink',
                'https://google.com/alink',
            ],
        ];
    }
}
