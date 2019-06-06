<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;
use DOMDocument;

final class FaviconRequestBlockerTest extends TestCase{

    public function testCreatesFavIconWhenHeadElementIsGiven() {
        $dom = new DOMDocument('1.0', 'utf-8');

        // create parent head 
        $head = $dom->createElement( 'head', ' ' );
        $body = $dom->createElement( 'body', ' ' );

        // create dummy anchor
        $anchor = $dom->createElement('a', 'Some anchor text');
        $anchor->setAttribute('href', 'https://somedomain.com/alink') ;
        $body->appendChild( $anchor );

        $html = $dom->createElement( 'html' );
        $html->appendChild( $head );
        $html->appendChild( $body );
        $dom->appendChild( $html );

        $favicon_generator = new FaviconRequestBlocker();
        $favicon_generator->createEmptyFaviconLink( $dom, $head );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head><link rel="icon" href="data:,"> </head><body> <a href="https://somedomain.com/alink">Some anchor text</a></body></html>' . PHP_EOL,
            $result
        );
    }

    public function testNoChangeWhenNoHeadElementGiven() {
        $dom = new DOMDocument('1.0', 'utf-8');

        // create parent head 
        $head = $dom->createElement( 'head', ' ' );
        $body = $dom->createElement( 'body', ' ' );

        // create dummy anchor
        $anchor = $dom->createElement('a', 'Some anchor text');
        $anchor->setAttribute('href', 'https://somedomain.com/alink') ;
        $body->appendChild( $anchor );

        $html = $dom->createElement( 'html' );
        $html->appendChild( $head );
        $html->appendChild( $body );
        $dom->appendChild( $html );

        $favicon_generator = new FaviconRequestBlocker();
        $favicon_generator->createEmptyFaviconLink( $dom );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head> </head><body> <a href="https://somedomain.com/alink">Some anchor text</a></body></html>' . PHP_EOL,
            $result
        );
    }
}
