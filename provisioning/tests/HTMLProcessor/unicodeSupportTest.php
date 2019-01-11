<?php

declare(strict_types=1);

require_once 'library/StaticHtmlOutput/HTMLProcessor.php';
require_once 'library/URL2/URL2.php';

use PHPUnit\Framework\TestCase;

final class HTMLProcessorUnicodeSupportTest extends TestCase {

    /**
     * @dataProvider unicodeProvider
     */
    public function testUnicodeOutput(
        $test_HTML_content,
        $exp_result
        ) {

        $mockProcessor = $this->getMockBuilder( 'HTMLProcessor' )
            ->setMethods(
                [
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

        $mockProcessor->method( 'loadSettings' )->willReturn( null );
        $mockProcessor->method( 'rewriteSiteURLsToPlaceholder' )->willReturn( null );
        $mockProcessor->method( 'detectIfURLsShouldBeHarvested' )->willReturn( null );
        $mockProcessor->method( 'writeDiscoveredURLs' )->willReturn( null );

        $mockProcessor->settings = array(
            'baseUrl' => 'http://baseurldomainfromsettings.com/',
        );

        $mockProcessor->processHTML( $test_HTML_content, $page_URL );

        $this->assertEquals(
            $exp_result,
            $mockProcessor->getHTML()
        );

    }

    public function unicodeProvider() {
        return [
           'unicode characters in source' =>  [
                '<!DOCTYPE html><html lang="en-US"><head></head><meta charset="utf-8"/><title>wpnotes | Поредният WordPress сайт</title><body></body></html>',
                '<!DOCTYPE html>
<html lang="en-US"><head></head><meta charset="utf-8"><title>wpnotes | Поредният WordPress сайт</title><body></body></html>
',
            ],
        ];
    }
}
