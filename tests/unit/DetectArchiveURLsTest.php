<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;
use WP_Mock;

final class DetectArchiveURLsTest extends TestCase {


    public function testDetect() {
        $site_url = 'https://foo.com/';

        \WP_Mock::userFunction(
            'wp_get_archives',
            [
                'times' => 1,
                'args' => [
                    [
                        'type' => 'yearly',
                        'echo' => 0,
                    ],
                ],
                'return' =>
                    "<li><a href='{$site_url}archives/2020/'>2020</a></li>
                    <li><a href='{$site_url}archives/2019/'>2019</a></li>
                    <li><a href='{$site_url}archives/2018/'>2018</a></li>
                    <li><a href='{$site_url}archives/2017/'>2017</a></li>",
            ]
        );

        \WP_Mock::userFunction(
            'wp_get_archives',
            [
                'times' => 1,
                'args' => [
                    [
                        'type' => 'monthly',
                        'echo' => 0,
                    ],
                ],
                'return' =>
                    "<li><a href='{$site_url}archives/2020/08/'>August 2020</a></li>
                    <li><a href='{$site_url}archives/2020/07/'>July 2020</a></li>
                    <li><a href='{$site_url}archives/2020/06/'>June 2020</a></li>
                    <li><a href='{$site_url}archives/2020/05/'>May 2020</a></li>",
            ]
        );

        \WP_Mock::userFunction(
            'wp_get_archives',
            [
                'times' => 1,
                'args' => [
                    [
                        'type' => 'daily',
                        'echo' => 0,
                    ],
                ],
                'return' =>
                    "<li><a href='{$site_url}archives/2020/08/20/'>August 20, 2020</a></li>
                    <li><a href='{$site_url}archives/2020/08/17/'>August 17, 2020</a></li>
                    <li><a href='{$site_url}archives/2020/08/16/'>August 16, 2020</a></li>
                    <li><a href='{$site_url}archives/2020/08/15/'>August 15, 2020</a></li>",
            ]
        );

        $expected = [
            "{$site_url}archives/2020/",
            "{$site_url}archives/2019/",
            "{$site_url}archives/2018/",
            "{$site_url}archives/2017/",
            "{$site_url}archives/2020/08/",
            "{$site_url}archives/2020/07/",
            "{$site_url}archives/2020/06/",
            "{$site_url}archives/2020/05/",
            "{$site_url}archives/2020/08/20/",
            "{$site_url}archives/2020/08/17/",
            "{$site_url}archives/2020/08/16/",
            "{$site_url}archives/2020/08/15/",
        ];
        $actual = DetectArchiveURLs::detect();
        $this->assertEquals( $expected, $actual );
    }
}
