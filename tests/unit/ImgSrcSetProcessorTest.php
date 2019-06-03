<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;
use DOMDocument;

// mock URLRewriter TODO: pass rewrite rules to URLRewriter when updated
class URLRewriter {
   public function rewriteLocalURL( $url ) {
        $url = str_replace(
            'https://somedomain.com/',
            'https://newdomain.com/',
            $url
        );

        return $url;
   } 
}

final class ImgSrcSetProcessorTest extends TestCase{

    public function testprocessImageSrcSet() {
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

        $srcset_processor = new ImgSrcSetProcessor();
        $srcset_processor->processImageSrcSet( $img );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head> <link rel="canonical" href="https://somedomain.com/alink"></head><body> <a href="https://somedomain.com/alink">Some anchor text</a><img href="https://somedomain.com/alink" srcset="https://newdomain.com/image-1x.png 1x,https://newdomain.com/image-2x.png 2x,https://newdomain.com/image-3x.png 3x,https://newdomain.com/image-4x.png 4x"></body></html>' . PHP_EOL,
            $result
        );
    }

    public function testReturnsImageIfNoSrcSet() {
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

        $srcset_processor = new ImgSrcSetProcessor();
        $srcset_processor->processImageSrcSet( $img );

        $result = $dom->saveHtml();

        $this->assertEquals(
            '<html><head> <link rel="canonical" href="https://somedomain.com/alink"></head><body> <a href="https://somedomain.com/alink">Some anchor text</a><img href="https://somedomain.com/alink"></body></html>' . PHP_EOL,
            $result
        );
    }
}
