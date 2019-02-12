<?php

declare(strict_types=1);

chdir( dirname(__FILE__) . '/../../plugin' );

$plugin_dir = getcwd();

require_once $plugin_dir . '/WP2Static/WP2Static.php';
require_once $plugin_dir . '/WP2Static/HTMLProcessor.php';
require_once $plugin_dir . '/URL2/URL2.php';

use PHPUnit\Framework\TestCase;

final class HTMLProcessorRewriteSiteURLsToPlaceholderTest extends TestCase {

    /**
     * Test data provider
     * @dataProvider rewritePlaceholdersProvider
     */
    public function testRewritingRemainingPlaceholders(
        $site_url,
        $destination_protocol,
        $raw_html,
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
        $processor->settings['wp_site_url'] = $site_url;
        $processor->raw_html = $raw_html;

        $processor->method( 'getTargetSiteProtocol' )->willReturn( $destination_protocol );

        $processor->placeholder_url =
            $destination_protocol . 'PLACEHOLDER.wpsho/';

        $processor->destination_protocol = $destination_protocol;

        $processor->page_url = new Net_URL2(
            'http://mywpsite.com/category/photos/my-gallery/'
        );

        $processor->rewriteSiteURLsToPlaceholder();

        $this->assertEquals(
            $exp_result,
            $processor->raw_html
        );
    }

    public function rewritePlaceholdersProvider() {
        return [
           'http site url with trailing slash' =>  [
                'http://mywpdevsite.com',
                'https://',
                '<a href="http://mywpdevsite.com/banana.jpg">Link to some file</a>',
                '<a href="https://PLACEHOLDER.wpsho/banana.jpg">Link to some file</a>',
            ],
        ];
    }
}
