<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;

final class ConvertToDocumentRelativeURLTest extends TestCase{

    /**
     * @dataProvider offlineURLConversionProvider
     */
    public function testaddsRelativePathToURL(
        $url, $page_url, $destination_url, $offline_mode, $expectation
    ) {
        $converted_url = ConvertToDocumentRelativeURL::convert(
            $url, $page_url, $destination_url, $offline_mode
        );

        $this->assertEquals(
            $expectation,
            $converted_url
        );
    }

    public function offlineURLConversionProvider() {
        return [
           'document relative asset' =>  [
                'https://myplaceholderdomain.com/mytheme/' .
                    'assets/link-to-an-image.jpg',
                'https://myplaceholderdomain.com/some-post/',
                'https://myplaceholderdomain.com/',
                false,
                '../mytheme/assets/link-to-an-image.jpg'
            ],
           'root relative asset' =>  [
                'https://myplaceholderdomain.com/mytheme/' .
                    'assets/link-to-an-image.jpg',
                'https://myplaceholderdomain.com/some-post/',
                'https://myplaceholderdomain.com/',
                false,
                '../mytheme/assets/link-to-an-image.jpg'
            ],
           'asset at same level' =>  [
                'https://myplaceholderdomain.com/some-post/' .
                    'link-to-an-image.jpg',
                'https://myplaceholderdomain.com/some-post/',
                'https://myplaceholderdomain.com/',
                false,
                'link-to-an-image.jpg'
            ],
           'page URL originally with trailing slash' =>  [
                'https://myplaceholderdomain.com/some-post/',
                'https://myplaceholderdomain.com/another-post/',
                'https://myplaceholderdomain.com/',
                true,
                '../some-post/index.html'
            ],
           'page URL originally without trailing slash' =>  [
                'https://myplaceholderdomain.com/some-post',
                'https://myplaceholderdomain.com/another-post/',
                'https://myplaceholderdomain.com/',
                true,
                '../some-post/index.html'
            ],
           '5 levels deep' =>  [
                'https://a.com/1/2/3/4/5/',
                'https://a.com/lvl1/2/3/4/5/',
                'https://a.com/',
                true,
                '../../../../../1/2/3/4/5/index.html'
            ],
        ];
    }
}
