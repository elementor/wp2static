<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;

final class RewriteSiteURLsToPlaceholderTest extends TestCase{

    /**
     * @dataProvider rewriteToOfflineProvider
     */
    public function testaddsRelativePathToURL(
        $html_document,
        $search_patterns,
        $replace_patterns,
        $expectation
    ) {
        $rewritten_source = RewriteSiteURLsToPlaceholder::rewrite(
            $html_document, $search_patterns, $replace_patterns
        );

        $this->assertEquals(
            $expectation,
            $rewritten_source
        );
    }

    public function rewriteToOfflineProvider() {
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
//            'http site url with trailing slash, https destination' =>  [
//                 'http://mywpdevsite.com/',
//                 'https://',
//                 '<a href="http://mywpdevsite.com/banana.jpg">Link to some file</a>',
//                 '<a href="https://PLACEHOLDER.wpsho/banana.jpg">Link to some file</a>',
//             ],
//            'https site url without trailing slash, https destination' =>  [
//                 'https://mywpdevsite.com',
//                 'https://',
//                 '<a href="https://mywpdevsite.com/banana.jpg">Link to some file</a>',
//                 '<a href="https://PLACEHOLDER.wpsho/banana.jpg">Link to some file</a>',
//             ],
//            'https site url with trailing slash, https destination' =>  [
//                 'https://mywpdevsite.com/',
//                 'https://',
//                 '<a href="https://mywpdevsite.com/banana.jpg">Link to some file</a>',
//                 '<a href="https://PLACEHOLDER.wpsho/banana.jpg">Link to some file</a>',
//             ],
//            'https site url without trailing slash, http destination' =>  [
//                 'https://mywpdevsite.com',
//                 'http://',
//                 '<a href="https://mywpdevsite.com/banana.jpg">Link to some file</a>',
//                 '<a href="http://PLACEHOLDER.wpsho/banana.jpg">Link to some file</a>',
//             ],
//            'https site url with trailing slash, http destination' =>  [
//                 'https://mywpdevsite.com/',
//                 'http://',
//                 '<a href="https://mywpdevsite.com/banana.jpg">Link to some file</a>',
//                 '<a href="http://PLACEHOLDER.wpsho/banana.jpg">Link to some file</a>',
//             ],
//            'https site url with trailing slash, http destination, escaped link' =>  [
//                 'https://mywpdevsite.com/',
//                 'http://',
//                 '<a href="https:\/\/mywpdevsite.com\/banana.jpg">Link to some file</a>',
//                 '<a href="http:\/\/PLACEHOLDER.wpsho\/banana.jpg">Link to some file</a>',
//             ],
//            'http site url without trailing slash, https destination, escaped link' =>  [
//                 'http://mywpdevsite.com',
//                 'https://',
//                 '<a href="http:\/\/mywpdevsite.com\/banana.jpg">Link to some file</a>',
//                 '<a href="https:\/\/PLACEHOLDER.wpsho\/banana.jpg">Link to some file</a>',
//             ],
//            'https site url with http leftovers in original source, https destination DOESNT rewrite leftovers (use companion plugin)' =>  [
//                 'https://mywpdevsite.com',
//                 'https://',
//                 '<a href="http://mywpdevsite.com/banana.jpg">Link to some file</a>',
//                 '<a href="http://PLACEHOLDER.wpsho/banana.jpg">Link to some file</a>',
//             ],
        ];
    }
}
