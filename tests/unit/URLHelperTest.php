<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;

final class URLHelperTest extends TestCase {


    /**
     * @dataProvider protocolRelativeURLProvider
     */
    public function testgetProtocolRelativeURL( $url, $expectation ) {
        $protocol_relative_url = URLHelper::getProtocolRelativeURL( $url );

        $this->assertEquals(
            $expectation,
            $protocol_relative_url
        );
    }

    public function protocolRelativeURLProvider() {
        return [
            'http link becomes protocol relative' => [
                'http://myplaceholderdomain.com/some-post/',
                '//myplaceholderdomain.com/some-post/',
            ],
            'https link becomes protocol relative' => [
                'https://myplaceholderdomain.com/some-post/',
                '//myplaceholderdomain.com/some-post/',
            ],
            'doc relative link remains unchanged' => [
                'some-post/',
                'some-post/',
            ],
            'protocol relative link remains unchanged' => [
                '//some-post/',
                '//some-post/',
            ],
            'site root relative link remains unchanged' => [
                '/some-post/',
                '/some-post/',
            ],
            'url containing http but no colon remains unchanged' => [
                'myplaceholderdomain.com/some-post-with-http-in-url/',
                'myplaceholderdomain.com/some-post-with-http-in-url/',
            ],
        ];
    }

    /**
     * @dataProvider startsWithHashProvider
     */
    public function teststartsWithHash( $url, $expectation ) {
        $this->assertEquals(
            $expectation,
            URLHelper::startsWithHash( $url )
        );
    }

    public function startsWithHashProvider() {
        return [
            'doc relative url starting with hash returns true' => [
                '#somehash',
                true,
            ],
            'site root relative url starting with / returns false' => [
                '/someurl',
                false,
            ],
        ];
    }

    /**
     * @dataProvider isMailtoProvider
     */
    public function testisMailto( $url, $expectation ) {
        $this->assertEquals(
            $expectation,
            URLHelper::isMailto( $url )
        );
    }

    public function isMailtoProvider() {
        return [
            'doc relative url starting with mailto returns true' => [
                'mailto:leon@wp2static.com',
                true,
            ],
            'site root relative url starting with / returns false' => [
                '/someurl',
                false,
            ],
        ];
    }

    /**
     * @dataProvider isProtocolRelativeProvider
     */
    public function testisProtocolRelative( $url, $expectation ) {
        $this->assertEquals(
            $expectation,
            URLHelper::isProtocolRelative( $url )
        );
    }

    public function isProtocolRelativeProvider() {
        return [
            'protocol relative URL returns true' => [
                '//mydomain.com/animage.jpg',
                true,
            ],
            'site root relative url starting with / returns false' => [
                '/someurl',
                false,
            ],
        ];
    }

    /**
     * @dataProvider protocolRelativeToAbsoluteURLProvider
     */
    public function testprotocolRelativeToAbsoluteURL(
        $url,
        $site_url,
        $expectation
    ) {
        $url = URLHelper::protocolRelativeToAbsoluteURL( $url, $site_url );

        $this->assertEquals(
            $expectation,
            $url
        );
    }

    public function protocolRelativeToAbsoluteURLProvider() {
        return [
            'same domain host returns abs url' => [
                '//mydomain.com/animage.jpg',
                'http://mydomain.com/',
                'http://mydomain.com/animage.jpg',
            ],
            'different domain host returns unchanged protocol rel url' => [
                '//mydomain.com/animage.jpg',
                'http://example.com/',
                '//mydomain.com/animage.jpg',
            ],
        ];
    }

    /**
     * @dataProvider isInternalLinkProvider
     */
    public function testisInternalLink(
        $url,
        $site_url_host,
        $expectation
    ) {
        $this->assertEquals(
            $expectation,
            URLHelper::isInternalLink( $url, $site_url_host )
        );
    }

    public function isInternalLinkProvider() {
        return [
            'first char /, 2nd char other is site root rel internal link' => [
                '/somelink',
                'anyhost.com',
                true,
            ],
            'starts with . is internal' => [
                './somelink',
                'anyhost.com',
                true,
            ],
            'matching URL hosts is internal' => [
                'http://mywpsite.com/some/image.jpg',
                'mywpsite.com',
                true,
            ],
            'different URL hosts is false' => [
                'http://someremsite.com/some/image.jpg',
                'mywpsite.com',
                false,
            ],
        ];
    }
}
