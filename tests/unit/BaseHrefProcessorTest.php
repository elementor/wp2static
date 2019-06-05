<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;
use DOMDocument;

final class BaseHrefProcessorTest extends TestCase{
    public function testCreatesGivenBaseHrefWhenNoneExistAndNotOfflineMode() {
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

        $html = $dom->createElement( 'html' );
        $html->appendChild( $head );
        $html->appendChild( $body );
        $dom->appendChild( $html );

        $base_href_processor = new BaseHrefProcessor(
            'https://google.com/', // base_href
            false, // allow_offline_usage
        );

        $base_href_processor->dealWithBaseHREFElement(
            $dom, // DOMDocument
            null, // DOMElement $base_element
            $head // DOMNode $head_element
        );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head><base href="https://google.com/"> <link rel="canonical" href="https://somedomain.com/alink"></head><body> <a href="https://somedomain.com/alink">Some anchor text</a></body></html>' . PHP_EOL,
            $result
        );
    }

    public function testReplacesExistingBaseHrefWithGivenAndNotOfflineMode() {
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

        // create <base>
        $base = $dom->createElement('base');
        $base->setAttribute('href', 'https://yahoo.co.jp/') ;
        $head->appendChild( $base );

        $html = $dom->createElement( 'html' );
        $html->appendChild( $head );
        $html->appendChild( $body );
        $dom->appendChild( $html );

        $base_href_processor = new BaseHrefProcessor(
            'https://google.com/', // base_href
            false, // allow_offline_usage
        );

        $base_href_processor->dealWithBaseHREFElement(
            $dom, // DOMDocument
            $base, // DOMElement $base_element
            $head // DOMNode $head_element
        );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head> <link rel="canonical" href="https://somedomain.com/alink"><base href="https://google.com/"></head><body> <a href="https://somedomain.com/alink">Some anchor text</a></body></html>' . PHP_EOL,
            $result
        );
    }
}
