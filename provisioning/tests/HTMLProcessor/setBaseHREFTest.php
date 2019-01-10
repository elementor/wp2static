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
        $test_HTML_content,
        $baseHREF,
        $exp_detect_existing,
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
            'baseHREF' => $baseHREF,
        );

        $mockProcessor->processHTML( $test_HTML_content, $page_URL );

        $this->assertEquals(
            $exp_detect_existing,
            $mockProcessor->base_tag_exists
        );

        $this->assertEquals(
            $exp_result,
            $mockProcessor->xml_doc->saveHTML()
        );

    }

    public function baseHREFProvider() {
        return [
           'base HREF to change existing in source' =>  [
                '<!DOCTYPE html><html lang="en-US"><head><base href="https://mydomain.com"></head><body></body></html>',
                'https://mynewdomain.com',
                true,
                '<!DOCTYPE html>
<html lang="en-US"><head><base href="https://mynewdomain.com"></head><body></body></html>
',
            ],
           'base HREF with none existing in source' =>  [
                '<!DOCTYPE html><html lang="en-US"><head></head><body></body></html>',
                'https://mynewdomain.com',
                false,
                '<!DOCTYPE html>
<html lang="en-US"><head><base href="https://mynewdomain.com"></head><body></body></html>
',
            ],
           'base HREF of "/" with none existing in source' =>  [
                '<!DOCTYPE html><html lang="en-US"><head></head><body></body></html>',
                '/',
                false,
                '<!DOCTYPE html>
<html lang="en-US"><head><base href="/"></head><body></body></html>
',
            ],
           'empty base HREF removes existing in source' =>  [
                '<!DOCTYPE html><html lang="en-US"><head><base href="https://mydomain.com"></head><body></body></html>',
                '',
                true,
                '<!DOCTYPE html>
<html lang="en-US"><head></head><body></body></html>
',
            ],
           'no base HREF and none existing in source' =>  [
                '<!DOCTYPE html><html lang="en-US"><head></head><body></body></html>',
                '',
                false,
                '<!DOCTYPE html>
<html lang="en-US"><head></head><body></body></html>
',
            ],
           'new base HREF becomes first child of <head>' =>  [
                '<!DOCTYPE html><html lang="en-US"><head><link rel="stylesheet" href="#"></head><body></body></html>',
                '/',
                false,
                '<!DOCTYPE html>
<html lang="en-US"><head><base href="/"><link rel="stylesheet" href="#"></head><body></body></html>
',
            ],
        ];
    }
}
