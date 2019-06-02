<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;

// mock static method
class WsLog {
    public static function l() {
    }
}

final class ConvertToDocumentRelativeURLTest extends TestCase{

    /**
     * @dataProvider documentRelativeURLConversionProvider
     */
    public function testaddsRelativePathToURL(
        $url, $page_url, $site_url, $offline_mode, $expectation
    ) {
        $converted_url = ConvertToDocumentRelativeURL::convert(
            $url, $page_url, $site_url, $offline_mode
        );

        $this->assertEquals(
            $expectation,
            $converted_url
        );
    }

    public function testPageURLWithoutDomainEmptyReturnsOriginalURL() {
        $url = 'https://anything.com/';
        $page_url = 'https://mydomain.com/';
        $site_url = 'https://mydomain.com/';
        $offline_mode = false;

        $converted_url = ConvertToDocumentRelativeURL::convert(
            $url,
            $page_url,
            $site_url,
            $offline_mode
        );

        $this->assertEquals(
            $url,
            $converted_url
        );
    }

    public function testNoPagePathReturnsOriginalURL() {
        $url = 'https://anything.com/';
        $page_url = '';
        $site_url = 'https://mydomain.com/';
        $offline_mode = false;

        $converted_url = ConvertToDocumentRelativeURL::convert(
            $url,
            $page_url,
            $site_url,
            $offline_mode
        );

        $this->assertEquals(
            $url,
            $converted_url
        );
    }

    public function documentRelativeURLConversionProvider() {
        return [
           'destination URL with subdir nested asset' =>  [
                'https://myplaceholderdomain.com/mystaticsite/mytheme/' .
                    'assets/link-to-an-image.jpg',
                'https://myplaceholderdomain.com/some-post/',
                'https://myplaceholderdomain.com/mystaticsite/',
                false,
                '../mytheme/assets/link-to-an-image.jpg'
            ],
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
           'link to homepage from child adds index.html' =>  [
                'https://a.com/',
                'https://a.com/lvl1/2/',
                'https://a.com/',
                true,
                '../../index.html'
            ],
           'link to root asset from child' =>  [
                'https://a.com/animage.jpg',
                'https://a.com/lvl1/2/',
                'https://a.com/',
                true,
                '../../animage.jpg'
            ],
        ];
    }
}
