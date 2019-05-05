<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;

final class ConvertToOfflineURLTest extends TestCase{

    /**
     * @dataProvider offlineURLConversionProvider
     */
    public function testaddsRelativePathToURL(
        $url_to_change, $page_url, $placeholder_url, $expectation
    ) {
        $converted_url = ConvertToOfflineURL::convert(
            $url_to_change, $page_url, $placeholder_url
        );

        $this->assertEquals(
            $expectation,
            $converted_url
        );
    }

    public function offlineURLConversionProvider() {
        return [
           'document relative asset' =>  [
                'mytheme/assets/link-to-an-image.jpg',
                'https://myplaceholderdomain.com/some-post/',
                'https://myplaceholderdomain.com/',
                '../mytheme/assets/link-to-an-image.jpg'
            ],
           'root relative asset' =>  [
                '/mytheme/assets/link-to-an-image.jpg',
                'https://myplaceholderdomain.com/some-post/',
                'https://myplaceholderdomain.com/',
                '../../mytheme/assets/link-to-an-image.jpg'
            ],
        ];
    }
}
