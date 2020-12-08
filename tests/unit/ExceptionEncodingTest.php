<?php

namespace vipnytt\SitemapParser\Tests;

use PHPUnit\Framework\TestCase;
use vipnytt\SitemapParser;

class ExceptionEncodingTest extends TestCase
{
    /**
     * Test if exception is thrown when trying to set encoding to `UTF-8`
     */
    public function testExceptionEncoding()
    {
        if (!mb_internal_encoding('UTF-8')) {
            $this->expectException('\vipnytt\SitemapParser\Exceptions\SitemapParserException');
            new SitemapParser('SitemapParser');
        }
    }
}
