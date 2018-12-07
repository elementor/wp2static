<?php

declare(strict_types=1);

require_once 'library/StaticHtmlOutput/HTMLProcessor.php';
require_once 'library/URL2/URL2.php';

use PHPUnit\Framework\TestCase;

final class HTMLProcessorBaseHREFTest extends TestCase {

    /**
     * @dataProvider baseHREFProvider
     */
    public function testSetBaseHREF(
        $head_html,
        $baseHREF,
        $exp_detect_existing,
        $exp_result
        ) {
        $html_header = '<!DOCTYPE html><html lang="en-US" class="no-js no-svg">';
        $html_footer = '<body></body></html>';
        $test_HTML_content = $html_header . $head_html . $html_footer;

        // mock out only the unrelated methods
        $mockProcessor = $this->getMockBuilder( 'HTMLProcessor' )
            ->setMethods(
                [
                    'isInternalLink',
                    'loadSettings',
                    'rewriteSiteURLsToPlaceholder',
                    'detectIfURLsShouldBeHarvested',
                    'writeDiscoveredURLs',
                ]
            )
            ->getMock();

        $page_URL = new Net_URL2(
            'http://mywpsite.com/category/photos/my-gallery/'
        );



        $mockProcessor->method( 'isInternalLink' )->willReturn( true );
        $mockProcessor->method( 'loadSettings' )->willReturn( null );
        $mockProcessor->method( 'rewriteSiteURLsToPlaceholder' )->willReturn( null );
        $mockProcessor->method( 'detectIfURLsShouldBeHarvested' )->willReturn( null );
        $mockProcessor->method( 'writeDiscoveredURLs' )->willReturn( null );

        $mockProcessor->expects( $this->once() )->method( 'isInternalLink' );
//        $mockProcessor->expects( $this->once() )->method( 'processHead' );
//        $mockProcessor->expects( $this->once() )->method( 'processAnchor' );

        $mockProcessor->settings = array(
            'baseUrl' => 'http://baseurldomainfromsettings.com/',
        );

        // we expect the $this->base_tag_exists to be set when existing is detected

        $mockProcessor->processHTML($test_HTML_content, $page_URL);

        $this->assertEquals(
            $exp_result,
            $mockProcessor->ownerDocument->processHTML( $element )
        );
    }

    public function baseHREFProvider() {
        return [
           'base HREF to change existing in source' =>  [
                '<head><base href="https://mydomain.com"></head>',
                'https://mynewdomain.com',
                true,
                '<head><base href="https://mynewdomain.com"></head>',
            ],
//           'base HREF with none existing in source' =>  [
//                '<head><base href="https://mydomain.com"></head><a href="https://mydomain.com/posts/my_blog_post/">Link text</a>',
//                'a',
//                'href',
//                '<a href="http://mywpsite.com/first_lvl_dir/a_file.jpg">Link to some file</a>'
//            ],
//           'no base HREF to remove existing in source' =>  [
//                '<head><base href="https://mydomain.com"></head><a href="https://mydomain.com/posts/my_blog_post/">Link text</a>',
//                'a',
//                'href',
//                '<a href="http://mywpsite.com/first_lvl_dir/a_file.jpg">Link to some file</a>'
//            ],
//           'no base HREF and none existing in source' =>  [
//                '<head><base href="https://mydomain.com"></head><a href="https://mydomain.com/posts/my_blog_post/">Link text</a>',
//                'a',
//                'href',
//                '<a href="http://mywpsite.com/first_lvl_dir/a_file.jpg">Link to some file</a>'
//            ],
        ];
    }
}
