<?php

namespace WP2Static;

use GuzzleHttp;
use SimpleXMLElement;
use UrlParser;

/**
 * SitemapParser class
 *
 * Originally forked from https://github.com/VIPnytt/SitemapParser
 *
 * Specifications:
 * @link http://www.sitemaps.org/protocol.html
 */
class SitemapParser
{
    /**
     * Default User-Agent
     */
    const DEFAULT_USER_AGENT = 'WP2Static.com';

    /**
     * Default encoding
     */
    const ENCODING = 'UTF-8';

    /**
     * XML file extension
     */
    const XML_EXTENSION = 'xml';

    /**
     * Compressed XML file extension
     */
    const XML_EXTENSION_COMPRESSED = 'xml.gz';

    /**
     * XML Sitemap tag
     */
    const XML_TAG_SITEMAP = 'sitemap';

    /**
     * XML URL tag
     */
    const XML_TAG_URL = 'url';

    /**
     * Robots.txt path
     */
    const ROBOTSTXT_PATH = '/robots.txt';

    /**
     * User-Agent to send with every HTTP(S) request
     * @var string
     */
    protected $userAgent;

    /**
     * Configuration options
     * @var array
     */
    protected $config = [];

    /**
     * Sitemaps discovered
     * @var array
     */
    protected $sitemaps = [];

    /**
     * URLs discovered
     * @var array
     */
    protected $urls = [];

    /**
     * Sitemap URLs discovered but not yet parsed
     * @var array
     */
    protected $queue = [];

    /**
     * Parsed URLs history
     * @var array
     */
    protected $history = [];

    /**
     * Current URL being parsed
     * @var null|string
     */
    protected $currentURL;

    /**
     * Constructor
     *
     * @param string $userAgent User-Agent to send with every HTTP(S) request
     * @param array $config Configuration options
     * @throws WP2StaticException
     */
    public function __construct($userAgent = self::DEFAULT_USER_AGENT, array $config = [])
    {
        mb_language("uni");
        if (!mb_internal_encoding(self::ENCODING)) {
            throw new WP2StaticException('Unable to set internal character encoding to `' . self::ENCODING . '`');
        }
        $this->userAgent = $userAgent;
        $this->config = $config;
    }

    /**
     * Parse Recursive
     *
     * @param string $url
     * @return void
     * @throws WP2StaticException
     */
    public function parseRecursive($url)
    {
        $this->addToQueue([$url]);
        while (count($todo = $this->getQueue()) > 0) {
            $sitemaps = $this->sitemaps;
            $urls = $this->urls;
            try {
                $this->parse($todo[0]);
            } catch (WP2StaticException $e) {
                // Keep crawling
                continue;
            }
            $this->sitemaps = array_merge_recursive($sitemaps, $this->sitemaps);
            $this->urls = array_merge_recursive($urls, $this->urls);
        }
    }

    /**
     * Add an array of URLs to the parser queue
     *
     * @param array $urlArray
     */
    public function addToQueue(array $urlArray)
    {
        foreach ($urlArray as $url) {
            $url = $this->urlEncode($url);
            if ($this->urlValidate($url)) {
                $this->queue[] = $url;
            }
        }
    }

    /**
     * Sitemap URLs discovered but not yet parsed
     *
     * @return array
     */
    public function getQueue()
    {
        $this->queue = array_values(array_diff(array_unique(array_merge($this->queue, array_keys($this->sitemaps))), $this->history));
        return $this->queue;
    }

    /**
     * Parse
     *
     * @param string $url URL to parse
     * @param string|null $urlContent URL body content (provide to skip download)
     * @return void
     * @throws WP2StaticException
     */
    public function parse($url, $urlContent = null)
    {
        $this->clean();
        $this->currentURL = $this->urlEncode($url);
        if (!$this->urlValidate($this->currentURL)) {
            throw new WP2StaticException('Invalid URL');
        }
        $this->history[] = $this->currentURL;
        $response = is_string($urlContent) ? $urlContent : $this->getContent();
        if (parse_url($this->currentURL, PHP_URL_PATH) === self::ROBOTSTXT_PATH) {
            $this->parseRobotstxt($response);
            return;
        }
        // Check if content is an gzip file
        if (mb_strpos($response, "\x1f\x8b\x08", 0, "US-ASCII") === 0) {
            $response = gzdecode($response);
        }
        $sitemapJson = $this->generateXMLObject($response);
        if ($sitemapJson instanceof SimpleXMLElement === false) {
            $this->parseString($response);
            return;
        }
        $this->parseJson(self::XML_TAG_SITEMAP, $sitemapJson);
        $this->parseJson(self::XML_TAG_URL, $sitemapJson);
    }

    /**
     * Cleanup between each parse
     *
     * @return void
     */
    protected function clean()
    {
        $this->sitemaps = [];
        $this->urls = [];
    }

    /**
     * Request the body content of an URL
     *
     * @return string Raw body content
     * @throws WP2StaticException
     */
    protected function getContent()
    {
        $this->currentURL = $this->urlEncode($this->currentURL);
        if (!$this->urlValidate($this->currentURL)) {
            throw new WP2StaticException('Invalid URL');
        }
        try {
            if (!isset($this->config['guzzle']['headers']['User-Agent'])) {
                $this->config['guzzle']['headers']['User-Agent'] = $this->userAgent;
            }
            $client = new GuzzleHttp\Client();
            $res = $client->request('GET', $this->currentURL, $this->config['guzzle']);
            return $res->getBody()->getContents();
        } catch (GuzzleHttp\Exception\TransferException $e) {
            throw new WP2StaticException('Unable to fetch URL contents', 0, $e);
        } catch (GuzzleHttp\Exception\GuzzleException $e) {
            throw new WP2StaticException('GuzzleHttp exception', 0, $e);
        }
    }

    /**
     * Search for sitemaps in the robots.txt content
     *
     * @param string $robotstxt
     * @return bool
     */
    protected function parseRobotstxt($robotstxt)
    {
        // Split lines into array
        $lines = array_filter(array_map('trim', mb_split('\r\n|\n|\r', $robotstxt)));
        // Parse each line individually
        foreach ($lines as $line) {
            // Remove comments
            $line = mb_split('#', $line, 2)[0];
            // Split by directive and rule
            $pair = array_map('trim', mb_split(':', $line, 2));
            // Check if the line contains a sitemap
            if (
                mb_strtolower($pair[0]) !== self::XML_TAG_SITEMAP ||
                empty($pair[1])
            ) {
                // Line does not contain any supported directive
                continue;
            }
            $url = $this->urlEncode($pair[1]);
            if ($this->urlValidate($url)) {
                $this->addArray(self::XML_TAG_SITEMAP, ['loc' => $url]);
            }
        }
        return true;
    }

    /**
     * Validate URL arrays and add them to their corresponding arrays
     *
     * @param string $type sitemap|url
     * @param array $array Tag array
     * @return bool
     */
    protected function addArray($type, array $array)
    {
        if (!isset($array['loc'])) {
            return false;
        }
        $array['loc'] = $this->urlEncode(trim($array['loc']));
        if ($this->urlValidate($array['loc'])) {
            switch ($type) {
                case self::XML_TAG_SITEMAP:
                    $this->sitemaps[$array['loc']] = $this->fixMissingTags(['lastmod'], $array);
                    return true;
                case self::XML_TAG_URL:
                    $this->urls[$array['loc']] = $this->fixMissingTags(['lastmod', 'changefreq', 'priority'], $array);
                    return true;
            }
        }
        return false;
    }

    /**
     * Check for missing values and set them to null
     *
     * @param array $tags Tags check if exists
     * @param array $array Array to check
     * @return array
     */
    protected function fixMissingTags(array $tags, array $array)
    {
        foreach ($tags as $tag) {
            if (empty($array[$tag])) {
                $array[$tag] = null;
            }
        }
        return $array;
    }

    /**
     * Generate the \SimpleXMLElement object if the XML is valid
     *
     * @param string $xml
     * @return \SimpleXMLElement|false
     */
    protected function generateXMLObject($xml)
    {
        // strip XML comments from files
        // if they occur at the beginning of the file it will invalidate the XML
        // this occurs with certain versions of Yoast
        $xml = preg_replace('/\s*\<\!\-\-((?!\-\-\>)[\s\S])*\-\-\>\s*/', '', (string)$xml);
        try {
            libxml_use_internal_errors(true);
            return new SimpleXMLElement($xml, LIBXML_NOCDATA);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Parse line separated text string
     *
     * @param string $string
     * @return bool
     */
    protected function parseString($string)
    {
        if (!isset($this->config['strict']) || $this->config['strict'] !== false) {
            // Strings are not part of any documented sitemap standard
            return false;
        }
        $array = array_filter(array_map('trim', mb_split('\r\n|\n|\r', $string)));
        foreach ($array as $line) {
            if ($this->isSitemapURL($line)) {
                $this->addArray(self::XML_TAG_SITEMAP, ['loc' => $line]);
                continue;
            }
            $this->addArray(self::XML_TAG_URL, ['loc' => $line]);
        }
        return true;
    }

    /**
     * Check if the URL may contain an Sitemap
     *
     * @param string $url
     * @return bool
     */
    protected function isSitemapURL($url)
    {
        $path = parse_url($this->urlEncode($url), PHP_URL_PATH);
        return $this->urlValidate($url) && (
                mb_substr($path, -mb_strlen(self::XML_EXTENSION) - 1) == '.' . self::XML_EXTENSION ||
                mb_substr($path, -mb_strlen(self::XML_EXTENSION_COMPRESSED) - 1) == '.' . self::XML_EXTENSION_COMPRESSED
            );
    }

    /**
     * Parse Json object
     *
     * @param string $type Sitemap or URL
     * @param \SimpleXMLElement $json object
     * @return bool
     */
    protected function parseJson($type, \SimpleXMLElement $json)
    {
        if (!isset($json->$type)) {
            return false;
        }
        foreach ($json->$type as $url) {
            $this->addArray($type, (array)$url);
        }
        return true;
    }

    /**
     * Sitemaps discovered
     *
     * @return array
     */
    public function getSitemaps()
    {
        return $this->sitemaps;
    }

    /**
     * URLs discovered
     *
     * @return array
     */
    public function getURLs()
    {
        return $this->urls;
    }
}
