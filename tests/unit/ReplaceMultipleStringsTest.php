<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;

final class ReplaceMultipleStringsTest extends TestCase{

    /**
     * @dataProvider replaceMultipleStringsProvider
     */
    public function testReplace(
        $html_document,
        $search_patterns,
        $replace_patterns,
        $expectation
    ) {
        $rewritten_source = ReplaceMultipleStrings::replace(
            $html_document, $search_patterns, $replace_patterns
        );

        $this->assertEquals(
            $expectation,
            $rewritten_source
        );
    }

    public function replaceMultipleStringsProvider() {
        return [
           'http site url without trailing slash, https destination' =>  [
                '<a href="http://localhost/banana.jpg">Link to some file</a>',
                [
                    'http://localhost',
                    'http:\/\/localhost',
                    '//localhost',
                    '//localhost//',
                    '\/\/localhost',
                ],
                [
                    'https://PLACEHOLDER.wpsho',
                    'https:\/\/PLACEHOLDER.wpsho',
                    '//PLACEHOLDER.wpsho',
                    '//PLACEHOLDER.wpsho/',
                    '\/\/PLACEHOLDER.wpsho',
                ],
                '<a href="https://PLACEHOLDER.wpsho/banana.jpg">Link to some file</a>',
            ],
            'http site url with trailing slash, https destination' =>  [
                 '<a href="http://localhost/banana.jpg">Link to some file</a>',
                [
                    'http://localhost/',
                    'http:\/\/localhost\/',
                    '//localhost/',
                    '//localhost//',
                    '\/\/localhost\/',
                ],
                [
                    'https://PLACEHOLDER.wpsho/',
                    'https:\/\/PLACEHOLDER.wpsho\/',
                    '//PLACEHOLDER.wpsho/',
                    '//PLACEHOLDER.wpsho//',
                    '\/\/PLACEHOLDER.wpsho\/',
                ],
                 '<a href="https://PLACEHOLDER.wpsho/banana.jpg">Link to some file</a>',
             ],
            'https site url without trailing slash, https destination' =>  [
                 '<a href="https://mywpdevsite.com/banana.jpg">Link to some file</a>',
                [
                    'http://mywpdevsite.com',
                    'http:\/\/mywpdevsite.com',
                    '//mywpdevsite.com',
                    '//mywpdevsite.com//',
                    '\/\/mywpdevsite.com',
                ],
                [
                    'https://PLACEHOLDER.wpsho',
                    'https:\/\/PLACEHOLDER.wpsho',
                    '//PLACEHOLDER.wpsho',
                    '//PLACEHOLDER.wpsho/',
                    '\/\/PLACEHOLDER.wpsho',
                ],
                 '<a href="https://PLACEHOLDER.wpsho/banana.jpg">Link to some file</a>',
             ],
            'https site url with trailing slash, https destination' =>  [
                 '<a href="https://mywpdevsite.com/banana.jpg">Link to some file</a>',
                [
                    'https://mywpdevsite.com/',
                    'https:\/\/mywpdevsite.com\/',
                    '//mywpdevsite.com/',
                    '//mywpdevsite.com//',
                    '\/\/mywpdevsite.com\/',
                ],
                [
                    'https://PLACEHOLDER.wpsho/',
                    'https:\/\/PLACEHOLDER.wpsho\/',
                    '//PLACEHOLDER.wpsho/',
                    '//PLACEHOLDER.wpsho//',
                    '\/\/PLACEHOLDER.wpsho\/',
                ],
                 '<a href="https://PLACEHOLDER.wpsho/banana.jpg">Link to some file</a>',
             ],
            'escaped URL' =>  [
                 '<a href="https:\/\/localhost\/banana.jpg">Link to some file</a>',
                [
                    'http://localhost',
                    'http:\/\/localhost',
                    '//localhost',
                    '//localhost//',
                    '\/\/localhost',
                ],
                [
                    'https://PLACEHOLDER.wpsho',
                    'https:\/\/PLACEHOLDER.wpsho',
                    '//PLACEHOLDER.wpsho',
                    '//PLACEHOLDER.wpsho/',
                    '\/\/PLACEHOLDER.wpsho',
                ],
                 '<a href="https:\/\/PLACEHOLDER.wpsho\/banana.jpg">Link to some file</a>',
             ],
            'https site url with http leftovers in original source, https destination DOESNT rewrite leftovers (use companion plugin)' =>  [
                 '<a href="http://localhost/banana.jpg">Link to some file</a>',
                [
                    'https://localhost',
                    'https:\/\/localhost',
                    '//localhost',
                    '//localhost//',
                    '\/\/localhost',
                ],
                [
                    'http://PLACEHOLDER.wpsho',
                    'http:\/\/PLACEHOLDER.wpsho',
                    '//PLACEHOLDER.wpsho',
                    '//PLACEHOLDER.wpsho/',
                    '\/\/PLACEHOLDER.wpsho',
                ],
                 '<a href="http://PLACEHOLDER.wpsho/banana.jpg">Link to some file</a>',
             ],
        ];
    }
}
