<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;
use DOMDocument;

final class MetaProcessorTest extends TestCase{
    public function testprocessMeta() {
        // isolate from URLRewriter functions that occur after Meta Processor
        $url_rewriter = $this->createMock(URLRewriter::class);
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

        // create WordPress <meta>'s
        $generator_meta = $dom->createElement('meta', '');
        $generator_meta->setAttribute('name', 'generator') ;
        $generator_meta->setAttribute('content', 'WordPress 5.2') ;
        $robots_meta = $dom->createElement('meta', '');
        $robots_meta->setAttribute('name', 'robots') ;
        $robots_meta->setAttribute('content', 'noindex') ;
        $head->appendChild( $generator_meta );
        $head->appendChild( $robots_meta );

        $html = $dom->createElement( 'html' );
        $html->appendChild( $head );
        $html->appendChild( $body );
        $dom->appendChild( $html );

        $meta_processor = new MetaProcessor(
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
            ], // $rewriteRules,
            true, // $includeDiscoveredAssets,
            false, // removeRobotsNoIndex,
            false, // removeWPMeta,
            $url_rewriter // url rewriter
        );

        $meta_processor->processMeta( $generator_meta );
        $meta_processor->processMeta( $robots_meta );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head> <link rel="canonical" href="https://somedomain.com/alink"><meta name="generator" content="WordPress 5.2"><meta name="robots" content="noindex"></head><body> <a href="https://somedomain.com/alink">Some anchor text</a></body></html>' . PHP_EOL,
            $result
        );
    }

    public function testRemoveGenerator() {
        // isolate from URLRewriter functions that occur after Meta Processor
        $url_rewriter = $this->createMock(URLRewriter::class);
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

        // create WordPress <meta>'s
        $generator_meta = $dom->createElement('meta', '');
        $generator_meta->setAttribute('name', 'generator') ;
        $generator_meta->setAttribute('content', 'WordPress 5.2') ;
        $robots_meta = $dom->createElement('meta', '');
        $robots_meta->setAttribute('name', 'robots') ;
        $robots_meta->setAttribute('content', 'noindex') ;
        $head->appendChild( $generator_meta );
        $head->appendChild( $robots_meta );

        $html = $dom->createElement( 'html' );
        $html->appendChild( $head );
        $html->appendChild( $body );
        $dom->appendChild( $html );

        $meta_processor = new MetaProcessor(
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
            ], // $rewriteRules,
            true, // $includeDiscoveredAssets,
            false, // removeRobotsNoIndex,
            true, // removeWPMeta,
            $url_rewriter // url rewriter
        );

        $meta_processor->processMeta( $generator_meta );
        $meta_processor->processMeta( $robots_meta );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head> <link rel="canonical" href="https://somedomain.com/alink"><meta name="robots" content="noindex"></head><body> <a href="https://somedomain.com/alink">Some anchor text</a></body></html>' . PHP_EOL,
            $result
        );
    }

    public function testRemoveRobotsNoIndex() {
        // isolate from URLRewriter functions that occur after Meta Processor
        $url_rewriter = $this->createMock(URLRewriter::class);
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

        // create WordPress <meta>'s
        $generator_meta = $dom->createElement('meta', '');
        $generator_meta->setAttribute('name', 'generator') ;
        $generator_meta->setAttribute('content', 'WordPress 5.2') ;
        $robots_meta = $dom->createElement('meta', '');
        $robots_meta->setAttribute('name', 'robots') ;
        $robots_meta->setAttribute('content', 'noindex') ;
        $head->appendChild( $generator_meta );
        $head->appendChild( $robots_meta );

        $html = $dom->createElement( 'html' );
        $html->appendChild( $head );
        $html->appendChild( $body );
        $dom->appendChild( $html );

        $meta_processor = new MetaProcessor(
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
            ], // $rewriteRules,
            true, // $includeDiscoveredAssets,
            true, // removeRobotsNoIndex,
            true, // removeWPMeta,
            $url_rewriter // url rewriter
        );

        $meta_processor->processMeta( $generator_meta );
        $meta_processor->processMeta( $robots_meta );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head> <link rel="canonical" href="https://somedomain.com/alink"></head><body> <a href="https://somedomain.com/alink">Some anchor text</a></body></html>' . PHP_EOL,
            $result
        );
    }
}
