<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;

final class RewriteRulesTest extends TestCase{

    /**
     * @dataProvider generateProvider
     */
    public function testgenerate( $site_url, $destination_url, $expectation) {
        $rewrite_rules =
           RewriteRules::generate( $site_url, $destination_url );

        $this->assertEquals(
            $expectation,
            $rewrite_rules
        );
    }

    public function generateProvider() {
        return [
           'naked domain site and destination' =>  [
                'http://mywpsite.com/',
                'https://mylivesite.com/',
                [
                    'site_url_patterns' => [
                        'http://mywpsite.com',
                        'http:\/\/mywpsite.com',
                    ],
                    'destination_url_patterns' => [
                        'https://mylivesite.com',
                        'https:\/\/mylivesite.com',
                    ],
                ],
            ],
           'subdomain site, subdir destination' =>  [
                'http://dev.mywpsite.com/',
                'https://mylivesite.com/static/',
                [
                    'site_url_patterns' => [
                        'http://dev.mywpsite.com',
                        'http:\/\/dev.mywpsite.com',
                    ],
                    'destination_url_patterns' => [
                        'https://mylivesite.com/static',
                        'https:\/\/mylivesite.com\/static',
                    ],
                ],
            ],
        ];
    }
}
