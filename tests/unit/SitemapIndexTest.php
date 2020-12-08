<?php

namespace vipnytt\SitemapParser\Tests;

use PHPUnit\Framework\TestCase;
use vipnytt\SitemapParser;

class SitemapIndexTest extends TestCase
{
    /**
     * @dataProvider generateDataForTest
     * @param string $url URL
     * @param string $body URL body content
     * @param array $result Test result to match
     */
    public function testSitemapIndex($url, $body, $result)
    {
        $parser = new SitemapParser('SitemapParser');
        $this->assertInstanceOf('vipnytt\SitemapParser', $parser);
        $parser->parse($url, $body);
        $this->assertEquals($result, $parser->getSitemaps());
        $this->assertEquals([], $parser->getURLs());
    }

    /**
     * Generate test data
     * @return array
     */
    public function generateDataForTest()
    {
        return [
            [
                'http://www.example.com/sitemap.xml',
                <<<XMLSITEMAP
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <sitemap>
    <loc>http://www.example.com/sitemap2.xml</loc>
    <lastmod>2004-10-01T18:23:17+00:00</lastmod>
  </sitemap>
  <sitemap>
    <loc>http://www.example.com/sitemap3.xml</loc>
    <lastmod>2005-09-01T17:22:16+00:00</lastmod>
  </sitemap>
  <sitemap>
    <loc>http://www.example.com/sitemap4.xml.gz</loc>
    <lastmod>2006-08-01T16:21:15+00:00</lastmod>
  </sitemap>
</sitemapindex>
XMLSITEMAP
                ,
                $result = [
                    'http://www.example.com/sitemap2.xml' => [
                        'loc' => 'http://www.example.com/sitemap2.xml',
                        'lastmod' => '2004-10-01T18:23:17+00:00',
                    ],
                    'http://www.example.com/sitemap3.xml' => [
                        'loc' => 'http://www.example.com/sitemap3.xml',
                        'lastmod' => '2005-09-01T17:22:16+00:00',
                    ],
                    'http://www.example.com/sitemap4.xml.gz' => [
                        'loc' => 'http://www.example.com/sitemap4.xml.gz',
                        'lastmod' => '2006-08-01T16:21:15+00:00',
                    ],
                ]
            ]
        ];
    }
}
