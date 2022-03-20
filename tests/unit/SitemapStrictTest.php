<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;

class SitemapStrictTest extends TestCase {

    /**
     * @group ExternalRequests
     * @dataProvider generateDataForTest
     * @param string $url URL
     * @param string $body URL body content
     */
    public function testStrict( $url, $body ) {
        $parser = new SitemapParser( 'SitemapParser', [] );
        $this->assertInstanceOf( 'WP2Static\SitemapParser', $parser );
        $parser->parse( $url, $body );
        $this->assertEquals( [], $parser->getSitemaps() );
        $this->assertEquals( [], $parser->getURLs() );
    }

    /**
     * Generate test data
     *
     * @return array
     */
    public function generateDataForTest() {
        return [
            [
                'http://www.example.com/sitemap.txt',
                <<<TEXT
http://www.example.com/sitemap1.xml
http://www.example.com/sitemap2.xml
http://www.example.com/sitemap3.xml.gz
http://www.example.com/page1/
http://www.example.com/page2/
http://www.example.com/page3/file.gz
TEXT,
            ],
        ];
    }
}
