<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;
use DOMDocument;

final class RemoveLinkElementsBasedOnRelAttrTest extends TestCase{

    public function testRemovesLinkElements() {
        $dom = new DOMDocument('1.0', 'utf-8');

        // create parent <head> 
        $head = $dom->createElement( 'head', ' ' );
        $body = $dom->createElement( 'body', ' ' );


        
        $relative_links_to_rm = array(
            'shortlink',
            'pingback',
            'alternate',
            'EditURI',
            'wlwmanifest',
            'index',
            'profile',
            'prev',
            'next',
            'wlwmanifest',
        );


        // create the <link> rel elementts
        $link_elements = [];
        foreach ( $relative_links_to_rm as $rel_link ) {
            $link = $dom->createElement('link');
            $link->setAttribute('rel', $rel_link) ;
            $head->appendChild( $link );
            $link_elements[] = $link;
        }

        // create dummy <a>
        $anchor = $dom->createElement('a', 'Some anchor text');
        $anchor->setAttribute('href', 'https://somedomain.com/alink') ;
        $body->appendChild( $anchor );

        $html = $dom->createElement( 'html' );
        $html->appendChild( $head );
        $html->appendChild( $body );
        $dom->appendChild( $html );

        $rel_link_remover = new RemoveLinkElementsBasedOnRelAttr();

        foreach ( $link_elements as $link_element ) {
            $rel_link_remover::removeLinkElement( $link_element );
        }

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head> </head><body> <a href="https://somedomain.com/alink">Some anchor text</a></body></html>' . PHP_EOL,
            $result
        );
    }

    public function testRemovesWPAPILinks() {
        $dom = new DOMDocument('1.0', 'utf-8');

        // create parent <head> 
        $head = $dom->createElement( 'head', ' ' );
        $body = $dom->createElement( 'body', ' ' );

        $link = $dom->createElement('link');
        $link->setAttribute('rel', 'blah.w.org') ;
        $head->appendChild( $link );

        // create dummy <a>
        $anchor = $dom->createElement('a', 'Some anchor text');
        $anchor->setAttribute('href', 'https://somedomain.com/alink') ;
        $body->appendChild( $anchor );

        $html = $dom->createElement( 'html' );
        $html->appendChild( $head );
        $html->appendChild( $body );
        $dom->appendChild( $html );

        $rel_link_remover = new RemoveLinkElementsBasedOnRelAttr();

        $rel_link_remover::removeLinkElement( $link );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head> </head><body> <a href="https://somedomain.com/alink">Some anchor text</a></body></html>' . PHP_EOL,
            $result
        );
    }
}
