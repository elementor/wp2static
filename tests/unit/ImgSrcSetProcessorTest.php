<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;
use DOMDocument;

final class ImgSrcSetProcessorTest extends TestCase{
    public function testprocessImageSrcSet() {
        // mock the AssetDownloader class which is passed to processor
        $asset_downloader = $this->createMock(AssetDownloader::class);

        $dom = new DOMDocument('1.0', 'utf-8');

        // create parent <head> 
        $head = $dom->createElement( 'head', ' ' );
        $body = $dom->createElement( 'body', ' ' );

        // create the <link> with rel Element
        $link = $dom->createElement('link');
        $link->setAttribute('rel', 'canonical') ;
        $link->setAttribute('href', 'https://somedomain.com/alink') ;
        $head->appendChild( $link );

        // create dummy <a>
        $anchor = $dom->createElement('a', 'Some anchor text');
        $anchor->setAttribute('href', 'https://somedomain.com/alink') ;
        $body->appendChild( $anchor );

        // create <img> with srcset
        $img = $dom->createElement('img');
        $img->setAttribute('href', 'https://somedomain.com/alink') ;
        $img->setAttribute('srcset', 'https://somedomain.com/image-1x.png 1x, https://somedomain.com/image-2x.png 2x, https://somedomain.com/image-3x.png 3x, https://somedomain.com/image-4x.png 4x') ;
        $body->appendChild( $img );

        $html = $dom->createElement( 'html' );
        $html->appendChild( $head );
        $html->appendChild( $body );
        $dom->appendChild( $html );

        $srcset_processor = new ImgSrcSetProcessor(
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
            $asset_downloader
        );
        $srcset_processor->processImageSrcSet( $img );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head> <link rel="canonical" href="https://somedomain.com/alink"></head><body> <a href="https://somedomain.com/alink">Some anchor text</a><img href="https://somedomain.com/alink" srcset="https://google.com/image-1x.png 1x,https://google.com/image-2x.png 2x,https://google.com/image-3x.png 3x,https://google.com/image-4x.png 4x"></body></html>' . PHP_EOL,
            $result
        );
    }

    public function testReturnsImageIfNoSrcSet() {
        $asset_downloader = $this->createMock(AssetDownloader::class);

        $dom = new DOMDocument('1.0', 'utf-8');

        // create parent <head> 
        $head = $dom->createElement( 'head', ' ' );
        $body = $dom->createElement( 'body', ' ' );

        // create the <link> with rel Element
        $link = $dom->createElement('link');
        $link->setAttribute('rel', 'canonical') ;
        $link->setAttribute('href', 'https://somedomain.com/alink') ;
        $head->appendChild( $link );

        // create dummy <a>
        $anchor = $dom->createElement('a', 'Some anchor text');
        $anchor->setAttribute('href', 'https://somedomain.com/alink') ;
        $body->appendChild( $anchor );

        // create <img> with srcset
        $img = $dom->createElement('img');
        $img->setAttribute('href', 'https://somedomain.com/alink') ;
        $body->appendChild( $img );

        $html = $dom->createElement( 'html' );
        $html->appendChild( $head );
        $html->appendChild( $body );
        $dom->appendChild( $html );

        $srcset_processor = new ImgSrcSetProcessor(
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
            $asset_downloader
        );
        $srcset_processor->processImageSrcSet( $img );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head> <link rel="canonical" href="https://somedomain.com/alink"></head><body> <a href="https://somedomain.com/alink">Some anchor text</a><img href="https://somedomain.com/alink"></body></html>' . PHP_EOL,
            $result
        );
    }
}
