<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;

final class ConvertToOfflineURLTest extends TestCase{

    public function testOfflineConversion() {
        $url_to_change = '';
        $page_url = '';
        $placeholder_url = '';

        $converted_url = ConvertToOfflineURL::convert(
            $url_to_change, $page_url, $placeholder_url
        );

        $this->assertEquals(
            $converted_url,
            'https://google.com'
        );
    }
}
