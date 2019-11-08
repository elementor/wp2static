<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;

final class URLTest extends TestCase{
    public function testFailsToCreateFromNonAbsoluteURLWithoutParentPage() {
        $url = 'mylink.jpg';

        $this->expectException( WP2StaticException::class );

        $new_url = new URL( $url );
    }

    public function testCreatesFromAbsoluteURL() {
        $url = 'https://somedomain.com/mylink.jpg';

        $new_url = new URL( $url );

        $this->assertEquals(
            'https://somedomain.com/mylink.jpg',
            $new_url->get()
        );
    }

    public function testCreatesWithAbsoluteFromRelative() {
        $url = 'mylink.jpg';
        $parent_page_url = 'https://abc.com'; 

        $new_url = new URL( $url, $parent_page_url);

        $this->assertEquals(
            'https://abc.com/mylink.jpg',
            $new_url->get()
        );
    }

    public function testCreatesWithAbsoluteFromAbsolute() {
        $url = 'https://abc.com/mylink.jpg';
        $parent_page_url = 'https://abc.com'; 

        $new_url = new URL( $url, $parent_page_url);

        $this->assertEquals(
            'https://abc.com/mylink.jpg',
            $new_url->get()
        );
    }

    public function testCreatesWithAbsoluteFromProtocolRelative() {
        $url = '//abc.com/mylink.jpg';
        $parent_page_url = 'https://abc.com'; 

        $new_url = new URL( $url, $parent_page_url);

        $this->assertEquals(
            'https://abc.com/mylink.jpg',
            $new_url->get()
        );
    }

    public function testRewritesHostAndScheme() {
        $url = '//abc.com/mylink.jpg';
        $parent_page_url = 'http://abc.com'; 

        $new_url = new URL( $url, $parent_page_url);

        $new_url->rewriteHostAndProtocol('https://xyz.com/static/');

        $this->assertEquals(
            'https://xyz.com/mylink.jpg',
            $new_url->get()
        );
    }
}
