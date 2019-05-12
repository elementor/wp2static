<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;

final class ConvertToSiteRootRelativeURLTest extends TestCase{

    /**
     * @dataProvider siteRootRelativeURLConversionProvider
     */
    public function testconvertsToSiteRootRelativeURL(
        $url, $destination_url, $expectation
    ) {
        $converted_url = ConvertToSiteRootRelativeURL::convert(
            $url, $destination_url
        );

        $this->assertEquals(
            $expectation,
            $converted_url
        );
    }

    public function siteRootRelativeURLConversionProvider() {
        return [
           'nested asset' =>  [
                'https://myplaceholderdomain.com/mystaticsite/some-post/' .
                    'link-to-an-image.jpg',
                'https://myplaceholderdomain.com/mystaticsite/',
                '/some-post/link-to-an-image.jpg',
            ],
           'site url' =>  [
                'https://myplaceholderdomain.com/',
                'https://myplaceholderdomain.com/',
                '/'
            ],
           'escaped nested asset' =>  [
                'https:\/\/myplaceholderdomain.com\/mystaticsite\/some-post\/' .
                    'link-to-an-image.jpg',
                'https://myplaceholderdomain.com/mystaticsite/',
                '\/some-post\/link-to-an-image.jpg',
            ],
           'site url' =>  [
                'https:\/\/myplaceholderdomain.com\/',
                'https://myplaceholderdomain.com/',
                '\/'
            ],
        ];
    }
}
