<?php

namespace WP2Static;

use WP2StaticGuzzleHttp;
use SimpleXMLElement;

/**
 * SitemapParser class
 *
 * Originally forked from https://github.com/VIPnytt/SitemapParser
 *
 * Specifications:
 *
 * @link http://www.sitemaps.org/protocol.html
 */
class SitemapParser {

    use URLParser;

    /**
     * Default User-Agent
     */
    const DEFAULT_USER_AGENT = 'WP2Static.com';

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
     *
     * @var string
     */
    protected $user_agent;

    /**
     * Configuration options
     *
     * @var mixed[]
     */
    protected $config = [];

    /**
     * Sitemaps discovered
     *
     * @var mixed[]
     */
    protected $sitemaps = [];

    /**
     * URLs discovered
     *
     * @var mixed[]
     */
    protected $urls = [];

    /**
     * Sitemap URLs discovered but not yet parsed
     *
     * @var mixed[]
     */
    protected $queue = [];

    /**
     * Parsed URLs history
     *
     * @var mixed[]
     */
    protected $history = [];

    /**
     * Current URL being parsed
     *
     * @var null|string
     */
    protected $current_url;

    /**
     * Constructor
     *
     * @param string $user_agent User-Agent to send with every HTTP(S) request
     * @param mixed[] $config Configuration options
     */
    public function __construct( $user_agent = self::DEFAULT_USER_AGENT, array $config = [] ) {
        $this->user_agent = $user_agent;
        $this->config = $config;
    }

    /**
     * Parse Recursive
     *
     * @param string $url
     * @return void
     */
    public function parseRecursive( $url ) {
        $this->addToQueue( [ $url ] );

        // TODO: ignore until refactor, need to tear apart
        // phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found
        while ( count( $todo = $this->getQueue() ) > 0 ) {
            $sitemaps = $this->sitemaps;
            $urls = $this->urls;
            try {
                $this->parse( $todo[0] );
            } catch ( WP2StaticException $e ) {
                WsLog::w( $e->getMessage() );
                // Keep crawling
                continue;
            }
            $this->sitemaps = array_merge_recursive( $sitemaps, $this->sitemaps );
            $this->urls = array_merge_recursive( $urls, $this->urls );
        }
    }

    /**
     * Add an array of URLs to the parser queue
     *
     * @param mixed[] $url_array
     */
    public function addToQueue( array $url_array ) : void {
        foreach ( $url_array as $url ) {
            $url = $this->urlEncode( $url );
            if ( $this->urlValidate( $url ) ) {
                $this->queue[] = $url;
            }
        }
    }

    /**
     * Sitemap URLs discovered but not yet parsed
     *
     * @return mixed[]
     */
    public function getQueue() {
        $this->queue = array_values(
            array_diff(
                array_unique(
                    array_merge( $this->queue, array_keys( $this->sitemaps ) )
                ),
                $this->history
            )
        );
        return $this->queue;
    }

    /**
     * Parse
     *
     * @param string $url URL to parse
     * @param string|null $url_content URL body content (provide to skip download)
     * @return void
     * @throws WP2StaticException
     */
    public function parse( $url, $url_content = null ) {
        $this->clean();
        $this->current_url = $this->urlEncode( $url );
        if ( ! $this->urlValidate( $this->current_url ) ) {
            throw new WP2StaticException( 'Invalid URL' );
        }
        $this->history[] = $this->current_url;
        $response = is_string( $url_content ) ? $url_content : $this->getContent();

        if ( ! $response ) {
            return;
        }

        if ( parse_url( $this->current_url, PHP_URL_PATH ) === self::ROBOTSTXT_PATH ) {
            $this->parseRobotstxt( $response );
            return;
        }
        // Check if content is an gzip file
        if ( strpos( $response, "\x1f\x8b\x08", 0 ) === 0 ) {
            $response = gzdecode( $response );
        }

        if ( ! is_string( $response ) ) {
            return;
        }

        $sitemap_json = $this->generateXMLObject( $response );

        if ( ! $sitemap_json instanceof SimpleXMLElement ) {
            $this->parseString( $response );
            return;
        }

        $this->parseJson( self::XML_TAG_SITEMAP, $sitemap_json );
        $this->parseJson( self::XML_TAG_URL, $sitemap_json );
    }

    /**
     * Cleanup between each parse
     *
     * @return void
     */
    protected function clean() {
        $this->sitemaps = [];
        $this->urls = [];
    }

    /**
     * Request the body content of an URL
     *
     * @return ?string Raw body content
     * @throws WP2StaticException
     */
    protected function getContent() {
        $this->current_url =
            $this->urlEncode( (string) $this->current_url );

        if ( ! $this->urlValidate( $this->current_url ) ) {
            throw new WP2StaticException( 'Invalid URL' );
        }

        try {
            if ( ! isset( $this->config['guzzle']['headers']['User-Agent'] ) ) {
                $this->config['guzzle']['headers']['User-Agent'] = $this->user_agent;
            }
            $client = new WP2StaticGuzzleHttp\Client();
            $res = $client->request( 'GET', $this->current_url, $this->config['guzzle'] );
            if ( $res->getStatusCode() === 200 ) {
                return $res->getBody()->getContents();
            } else {
                WsLog::w(
                    'Got ' . $res->getStatusCode() .
                    ' for sitemap url "' . $this->current_url . '", skipping.'
                );
                return null;
            }
        } catch ( WP2StaticGuzzleHttp\Exception\TransferException $e ) {
            throw new WP2StaticException( 'Unable to fetch URL contents', 0, $e );
        } catch ( WP2StaticGuzzleHttp\Exception\GuzzleException $e ) {
            throw new WP2StaticException( 'WP2StaticGuzzleHttp exception', 0, $e );
        }
    }

    /**
     * Search for sitemaps in the robots.txt content
     *
     * @param string $robotstxt
     * @return bool
     */
    protected function parseRobotstxt( $robotstxt ) {
        // Split lines into array
        $lines = array_filter(
            array_map(
                fn ( $line ) => trim( (string) $line ),
                (array) preg_split( '/\r\n|\n|\r/', $robotstxt )
            )
        );

        // Parse each line individually
        foreach ( $lines as $line ) {
            // Remove comments
            $line = preg_split( '/#/', $line, 2 );

            if ( ! $line ) {
                continue;
            }

            $line = $line[0];

            // Split by directive and rule
            $pair = array_map(
                fn ( $line ) => trim( (string) $line ),
                (array) preg_split( '/:/', $line, 2 )
            );
            // Check if the line contains a sitemap
            if (
                strtolower( $pair[0] ) !== self::XML_TAG_SITEMAP ||
                empty( $pair[1] )
            ) {
                // Line does not contain any supported directive
                continue;
            }
            $url = $this->urlEncode( $pair[1] );
            if ( $this->urlValidate( $url ) ) {
                $this->addArray( self::XML_TAG_SITEMAP, [ 'loc' => $url ] );
            } else {
                WsLog::l(
                    "Invalid sitemap URL in robots.txt: $url"
                    . '\nEnsure that it is a valid absolute URL.'
                );
            }
        }
        return true;
    }

    /**
     * Validate URL arrays and add them to their corresponding arrays
     *
     * @param string $type sitemap|url
     * @param mixed[] $array Tag array
     * @return bool
     */
    protected function addArray( $type, array $array ) {
        if ( ! isset( $array['loc'] ) ) {
            return false;
        }
        $array['loc'] = $this->urlEncode( trim( $array['loc'] ) );
        if ( $this->urlValidate( $array['loc'] ) ) {
            switch ( $type ) {
                case self::XML_TAG_SITEMAP:
                    $this->sitemaps[ $array['loc'] ] =
                        $this->fixMissingTags( [ 'lastmod' ], $array );
                    return true;
                case self::XML_TAG_URL:
                    $this->urls[ $array['loc'] ] = $this->fixMissingTags(
                        [ 'lastmod', 'changefreq', 'priority' ],
                        $array
                    );
                    return true;
            }
        }
        return false;
    }

    /**
     * Check for missing values and set them to null
     *
     * @param mixed[] $tags Tags check if exists
     * @param mixed[] $array Array to check
     * @return mixed[]
     */
    protected function fixMissingTags( array $tags, array $array ) {
        foreach ( $tags as $tag ) {
            if ( empty( $array[ $tag ] ) ) {
                $array[ $tag ] = null;
            }
        }
        return $array;
    }

    /**
     * Generate the \SimpleXMLElement object if the XML is valid
     *
     * @param string $xml
     * @return \SimpleXMLElement|bool
     * @throws WP2StaticException
     */
    protected function generateXMLObject( $xml ) {
        // strip XML comments from files
        // if they occur at the beginning of the file it will invalidate the XML
        // this occurs with certain versions of Yoast
        $xml = preg_replace(
            '/\s*\<\!\-\-((?!\-\-\>)[\s\S])*\-\-\>\s*/',
            '',
            (string) $xml
        );
        try {
            libxml_use_internal_errors( true );
            return new SimpleXMLElement( (string) $xml, LIBXML_NOCDATA );
            // TODO: ignoring until refactor as only generic exception
            // thrown and abuses try/catch as conditional
            // @phpstan-ignore-next-line
        } catch ( \Exception $e ) {
            return false;
        }
    }

    /**
     * Parse line separated text string
     *
     * @param string $string
     * @return bool
     */
    protected function parseString( $string ) {
        if ( ! isset( $this->config['strict'] ) || $this->config['strict'] !== false ) {
            // Strings are not part of any documented sitemap standard
            return false;
        }
        $array = array_filter(
            array_map(
                fn ( $line ) => trim( (string) $line ),
                (array) preg_split( '/\r\n|\n|\r/', $string )
            )
        );
        foreach ( $array as $line ) {
            if ( $this->isSitemapURL( $line ) ) {
                $this->addArray( self::XML_TAG_SITEMAP, [ 'loc' => $line ] );
                continue;
            }
            $this->addArray( self::XML_TAG_URL, [ 'loc' => $line ] );
        }
        return true;
    }

    /**
     * Check if the URL may contain an Sitemap
     *
     * @param string $url
     * @return bool
     */
    protected function isSitemapURL( $url ) {
        $path = parse_url( $this->urlEncode( $url ), PHP_URL_PATH );
        return $this->urlValidate( $url ) && (
                substr(
                    (string) $path,
                    -strlen( self::XML_EXTENSION ) - 1
                ) == '.' . self::XML_EXTENSION ||
                substr(
                    (string) $path,
                    -strlen( self::XML_EXTENSION_COMPRESSED ) - 1
                ) == '.' . self::XML_EXTENSION_COMPRESSED
            );
    }

    /**
     * Parse Json object
     *
     * @param string $type Sitemap or URL
     * @param \SimpleXMLElement $json object
     * @return bool
     */
    protected function parseJson( $type, \SimpleXMLElement $json ) {
        if ( ! isset( $json->$type ) ) {
            return false;
        }
        foreach ( $json->$type as $url ) {
            $this->addArray( $type, (array) $url );
        }
        return true;
    }

    /**
     * Sitemaps discovered
     *
     * @return mixed[]
     */
    public function getSitemaps() {
        return $this->sitemaps;
    }

    /**
     * URLs discovered
     *
     * @return mixed[]
     */
    public function getURLs() {
        return $this->urls;
    }
}
