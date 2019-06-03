<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;
use DOMDocument;

final class CanonicalLinkRemoverTest extends TestCase{

    public function testremoveCanonicalLink() {
        $dom = new DOMDocument('1.0', 'utf-8');

        // create parent head 
        $head = $dom->createElement( 'head', ' ' );
        $body = $dom->createElement( 'body', ' ' );

        // create the link rel Element
        $link = $dom->createElement('link');
        $link->setAttribute('rel', 'canonical') ;
        $link->setAttribute('href', 'https://somedomain.com/alink') ;
        $head->appendChild( $link );

        // create dummy anchor
        $anchor = $dom->createElement('a', 'Some anchor text');
        $anchor->setAttribute('href', 'https://somedomain.com/alink') ;
        $body->appendChild( $anchor );

        $html = $dom->createElement( 'html' );
        $html->appendChild( $head );
        $html->appendChild( $body );
        $dom->appendChild( $html );

        $canonical_link_remover = new CanonicalLinkRemover();
        $canonical_link_remover->removeCanonicalLink( $link );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head> </head><body> <a href="https://somedomain.com/alink">Some anchor text</a></body></html>' . PHP_EOL,
            $result
        );
    }
}
