<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;
use DOMDocument;

final class HTMLCommentStripperTest extends TestCase{

    public function testremoveHTMLComments() {
        $dom = new DOMDocument('1.0', 'utf-8');

        // create parent head 
        $head = $dom->createElement( 'head', ' ' );
        $body = $dom->createElement( 'body', ' ' );

        // create comment in HEAD
        $comment_for_head = $dom->createComment('This is an HTML comment in the HEAD');
        $head->appendChild( $comment_for_head );

        // create dummy anchor
        $anchor = $dom->createElement('a', 'Some anchor text');
        $anchor->setAttribute('href', 'https://somedomain.com/alink') ;

        // create comment in BODY
        $comment_for_body = $dom->createComment('This is an HTML comment in the BODY');
        $body->appendChild( $comment_for_body );
        $body->appendChild( $anchor );

        $html = $dom->createElement( 'html' );
        $html->appendChild( $head );
        $html->appendChild( $body );
        $dom->appendChild( $html );

        $comment_stripper = new HTMLCommentStripper();
        $comment_stripper->stripHTMLComments( $dom );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head> </head><body> <a href="https://somedomain.com/alink">Some anchor text</a></body></html>' . PHP_EOL,
            $result
        );
    }
}
