<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;
use DOMDocument;

final class HeadProcessorTest extends TestCase{

    public function testProcessHead() {
        $dom = new DOMDocument('1.0', 'utf-8');

        $html_with_conditional_comments = <<<ENDHTML
<!DOCTYPE html>
<html lang="en-US" class="no-js no-svg">
<head>
<title>Test</title>
<!--[if IE]>
  This is inside a HTML comment, so most browsers will ignore it, but IE will
  interpret it.
<![endif]-->
</head>
<body>
<p>Document body</p>
</body>
</html>
ENDHTML;

        // NOTE: brittle tests with concatenation and newlines varying
        //       so concatenating strings for comparison
        $dom->preserveWhiteSpace = false;

        libxml_use_internal_errors( true );
        $dom->loadHTML( $html_with_conditional_comments );
        libxml_use_internal_errors( false );

        $head = $dom->getElementsByTagName('head')[0];

        $head_processor = new HeadProcessor(
            true // rm_conditional_head_comments
        );

        $head_processor->processHead(
            $head
        );

        $result = str_replace( array("\r", "\n"), '', $dom->saveHtml() );

        $expected_result = '<!DOCTYPE html><html lang="en-US" class="no-js no-svg"><head><title>Test</title></head><body><p>Document body</p></body></html>';

        $this->assertEquals(
            $expected_result,
            $result
        );
    }

    public function testReturnsBaseElementIfPresent() {
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

        $head_processor = new HeadProcessor(
            true // rm_conditional_head_comments
        );

        $returned_head = $head_processor->processHead(
            $head
        );

        $returned_head_html =
            $returned_head->ownerDocument->saveHTML( $returned_head );

        $this->assertEquals(
            '<base href="https://yahoo.co.jp/">',
            $returned_head_html
        );
    }
}
