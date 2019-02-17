<?php

chdir( dirname(__FILE__) . '/../../plugin' );

$plugin_dir = getcwd();

require_once $plugin_dir . '/WP2Static/WP2Static.php';
require_once $plugin_dir . '/WP2Static/HTMLProcessor.php';
require_once $plugin_dir . '/URL2/URL2.php';

use PHPUnit\Framework\TestCase;

final class HTMLProcessorIsInternalLinkTest extends TestCase {

    /**
     * @dataProvider internalLinkProvider
     */
    public function testDetectsInternalLink( $link, $domain, $expectation ) {
        /*
            $link should match $domain

            $domain defaults to placeholder_url

            we've rewritten all URLs before here to use the
            placeholder one, so internal link usually(always?)
            means it matches our placeholder domain

            TODO: rename function to reflect what it's now doing

        */

        $processor = $this->getMockBuilder( 'HTMLProcessor' )
            ->setMethods(
                array(
                    'loadSettings',
                )
            )
            ->getMock();

        $processor->method( 'loadSettings' )->willReturn( null );

        $processor->settings = array();

        $processor->placeholder_url = 'https://PLACEHOLDER.wpsho/';

        $result = $processor->isInternalLink( $link, $domain );

        $this->assertEquals(
            $expectation,
            $result
        );
    }

    public function internalLinkProvider() {
        return [
           'site root' =>  [
                'https://PLACEHOLDER.wpsho/',
                null,
                true
            ],
           'internal FQU with file in nested subdirs' =>  [
                'https://PLACEHOLDER.wpsho//category/travel/photos/001.jpg',
                null,
                true
            ],
           'external FQU with matching domain as 2nd arg' =>  [
                'http://someotherdomain.com/category/travel/photos/001.jpg',
                'http://someotherdomain.com',
                true
            ],
           'not external FQU' =>  [
                'http://someothersite.com/category/travel/photos/001.jpg',
                null,
                false
            ],
           'not internal FQU with different domain as 2nd arg' =>  [
                'https://PLACEHOLDER.wpsho//category/travel/photos/001.jpg',
                'http://someotherdomain.com',
                false
            ],
           'not subdomain' =>  [
                'https://sub.PLACEHOLDER.wpsho/',
                null,
                false
            ],
           'not internal partial URL' =>  [
                '/category/travel/photos/001.jpg',
                null,
                false
            ],
        ];
    }
}
