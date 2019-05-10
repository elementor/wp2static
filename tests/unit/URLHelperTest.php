<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;

final class URLHelperTest extends TestCase{

    /**
     * @dataProvider protocolRelativeURLProvider
     */
    public function testgetProtocolRelativeURL( $url, $expectation) {
        $protocol_relative_url = URLHelper::getProtocolRelativeURL( $url );

        $this->assertEquals(
            $expectation,
            $protocol_relative_url
        );
    }

    public function protocolRelativeURLProvider() {
        return [
           'http link becomes protocol relative' =>  [
                'http://myplaceholderdomain.com/some-post/',
                '//myplaceholderdomain.com/some-post/',
            ],
           'https link becomes protocol relative' =>  [
                'https://myplaceholderdomain.com/some-post/',
                '//myplaceholderdomain.com/some-post/',
            ],
           'doc relative link remains unchanged' =>  [
                'some-post/',
                'some-post/',
            ],
           'protocol relative link remains unchanged' =>  [
                '//some-post/',
                '//some-post/',
            ],
           'site root relative link remains unchanged' =>  [
                '/some-post/',
                '/some-post/',
            ],
           'url containing http but no colon remains unchanged' =>  [
                'myplaceholderdomain.com/some-post-with-http-in-url/',
                'myplaceholderdomain.com/some-post-with-http-in-url/',
            ],
        ];
    }
}
