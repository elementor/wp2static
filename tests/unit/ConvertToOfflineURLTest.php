<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;

final class ConvertToOfflineURLTest extends TestCase{

    public function testaddsRelativePathToURL() {
        $url_to_change = 'mytheme/assets/link-to-an-image.jpg';
        $page_url = 'https://myplaceholderdomain.com/some-post/';
        $placeholder_url = 'https://myplaceholderdomain.com/';

        $converted_url = ConvertToOfflineURL::convert(
            $url_to_change, $page_url, $placeholder_url
        );

        $this->assertEquals(
            '../mytheme/assets/link-to-an-image.jpg',
            $converted_url
        );
    }
}
