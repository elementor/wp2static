<?php

chdir( dirname(__FILE__) . '/../../plugin' );

$plugin_dir = getcwd();

require_once $plugin_dir . '/WP2Static/WP2Static.php';
require_once $plugin_dir . '/WP2Static/HTMLProcessor.php';
require_once $plugin_dir . '/URL2/URL2.php';

use PHPUnit\Framework\TestCase;

final class HTMLProcessorRewriteUnchangedURLsTest extends TestCase {

    /**
     * Test data provider
     * @dataProvider unchangedURLsProvider
     */
    public function testRewritingRemainingPlaceholders(
        $destination_protocol,
        $processed_html,
        $exp_result
        ) {

        // mock out only the unrelated methods
        $processor = $this->getMockBuilder( 'HTMLProcessor' )
            ->setMethods(
                [
                    'isInternalLink',
                    'loadSettings',
                    'getTargetSiteProtocol',
                ]
            )
            ->getMock();

        $processor->method( 'loadSettings' )->willReturn( null );
        $processor->settings = array();
        $processor->settings['baseUrl'] = 'http://somedomain.com';
        $processor->settings['baseUrl'] =
            $destination_protocol . 'somedomain.com';

        $processor->method( 'getTargetSiteProtocol' )->willReturn( $destination_protocol );

        $processor->placeholder_url =
            $destination_protocol . 'PLACEHOLDER.wpsho/';


        $processor->page_url = new Net_URL2(
            'http://mywpsite.com/category/photos/my-gallery/'
        );

        $this->assertEquals(
            $exp_result,
            $processor->rewriteUnchangedPlaceholderURLs( $processed_html )
        );
    }

    public function unchangedURLsProvider() {
        return [
           'http destination URL with trailing slash and trailing chars' =>  [
                'http://',
                '<a href="http://PLACEHOLDER.wpsho/banana.jpg">Link to some file</a>',
                '<a href="http://somedomain.com/banana.jpg">Link to some file</a>',
            ],
           'http destination URL with trailing slash' =>  [
                'http://',
                '<a href="http://PLACEHOLDER.wpsho/">Link to some file</a>',
                '<a href="http://somedomain.com/">Link to some file</a>',
            ],
           'http destination URL without trailing slash' =>  [
                'http://',
                '<a href="http://PLACEHOLDER.wpsho">Link to some file</a>',
                '<a href="http://somedomain.com">Link to some file</a>',
            ],
           'https destination URL with trailing slash and trailing chars' =>  [
                'https://',
                '<a href="https://PLACEHOLDER.wpsho/banana.jpg">Link to some file</a>',
                '<a href="https://somedomain.com/banana.jpg">Link to some file</a>',
            ],
           'https destination URL with trailing slash' =>  [
                'https://',
                '<a href="https://PLACEHOLDER.wpsho/">Link to some file</a>',
                '<a href="https://somedomain.com/">Link to some file</a>',
            ],
           'https destination URL without trailing slash' =>  [
                'https://',
                '<a href="https://PLACEHOLDER.wpsho">Link to some file</a>',
                '<a href="https://somedomain.com">Link to some file</a>',
            ],
        ];
    }
}
