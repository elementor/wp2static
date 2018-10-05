<?php

declare(strict_types=1);

require_once 'library/StaticHtmlOutput/HTMLProcessor.php';
require_once 'library/URL2/URL2.php';

use PHPUnit\Framework\TestCase;

final class HTMLProcessorIsInternalLinkTest extends TestCase {

    /**
     * @dataProvider internalLinkProvider
     */
    public function testDetectsInternalLink( $link, $domain, $expectation ): void {

        $processor = new HTMLProcessor();

        $processor->wp_site_url = 'http://mywpsite.com';

        $result = $processor->isInternalLink( $link, $domain );

        $this->assertEquals(
            $expectation,
            $result
        );
    }

    public function internalLinkProvider() {
        return [
           'site root' =>  [
                'http://mywpsite.com',
                null,
                true
            ],
           'internal FQU with file in nested subdirs' =>  [
                'http://mywpsite.com/category/travel/photos/001.jpg',
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
                'http://mywpsite.com/category/travel/photos/001.jpg',
                'http://someotherdomain.com',
                false
            ],
           'not subdomain' =>  [
                'http://sub.mywpsite.com',
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
