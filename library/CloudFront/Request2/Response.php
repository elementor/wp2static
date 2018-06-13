<?php
/**
 * Class representing a HTTP response
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to BSD 3-Clause License that is bundled
 * with this package in the file LICENSE and available at the URL
 * https://raw.github.com/pear/HTTP_Request2/trunk/docs/LICENSE
 *
 * @category  HTTP
 * @package   HTTP_Request2
 * @author    Alexey Borzov <avb@php.net>
 * @copyright 2008-2016 Alexey Borzov <avb@php.net>
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link      http://pear.php.net/package/HTTP_Request2
 */

/**
 * Exception class for HTTP_Request2 package
 */
require_once 'HTTP/Request2/Exception.php';

/**
 * Class representing a HTTP response
 *
 * The class is designed to be used in "streaming" scenario, building the
 * response as it is being received:
 * <code>
 * $statusLine = read_status_line();
 * $response = new HTTP_Request2_Response($statusLine);
 * do {
 *     $headerLine = read_header_line();
 *     $response->parseHeaderLine($headerLine);
 * } while ($headerLine != '');
 *
 * while ($chunk = read_body()) {
 *     $response->appendBody($chunk);
 * }
 *
 * var_dump($response->getHeader(), $response->getCookies(), $response->getBody());
 * </code>
 *
 * @category HTTP
 * @package  HTTP_Request2
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/HTTP_Request2
 * @link     http://tools.ietf.org/html/rfc2616#section-6
 */
class HTTP_Request2_Response
{
    /**
     * HTTP protocol version (e.g. 1.0, 1.1)
     * @var  string
     */
    protected $version;

    /**
     * Status code
     * @var  integer
     * @link http://tools.ietf.org/html/rfc2616#section-6.1.1
     */
    protected $code;

    /**
     * Reason phrase
     * @var  string
     * @link http://tools.ietf.org/html/rfc2616#section-6.1.1
     */
    protected $reasonPhrase;

    /**
     * Effective URL (may be different from original request URL in case of redirects)
     * @var  string
     */
    protected $effectiveUrl;

    /**
     * Associative array of response headers
     * @var  array
     */
    protected $headers = array();

    /**
     * Cookies set in the response
     * @var  array
     */
    protected $cookies = array();

    /**
     * Name of last header processed by parseHederLine()
     *
     * Used to handle the headers that span multiple lines
     *
     * @var  string
     */
    protected $lastHeader = null;

    /**
     * Response body
     * @var  string
     */
    protected $body = '';

    /**
     * Whether the body is still encoded by Content-Encoding
     *
     * cURL provides the decoded body to the callback; if we are reading from
     * socket the body is still gzipped / deflated
     *
     * @var  bool
     */
    protected $bodyEncoded;

    /**
     * Associative array of HTTP status code / reason phrase.
     *
     * @var  array
     * @link http://tools.ietf.org/html/rfc2616#section-10
     */
    protected static $phrases = array(

        // 1xx: Informational - Request received, continuing process
        100 => 'Continue',
        101 => 'Switching Protocols',

        // 2xx: Success - The action was successfully received, understood and
        // accepted
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // 3xx: Redirection - Further action must be taken in order to complete
        // the request
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',

        // 4xx: Client Error - The request contains bad syntax or cannot be
        // fulfilled
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // 5xx: Server Error - The server failed to fulfill an apparently
        // valid request
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded',

    );

    /**
     * Returns the default reason phrase for the given code or all reason phrases
     *
     * @param int $code Response code
     *
     * @return string|array|null Default reason phrase for $code if $code is given
     *                           (null if no phrase is available), array of all
     *                           reason phrases if $code is null
     * @link   http://pear.php.net/bugs/18716
     */
    public static function getDefaultReasonPhrase($code = null)
    {
        if (null === $code) {
            return self::$phrases;
        } else {
            return isset(self::$phrases[$code]) ? self::$phrases[$code] : null;
        }
    }

    /**
     * Constructor, parses the response status line
     *
     * @param string $statusLine   Response status line (e.g. "HTTP/1.1 200 OK")
     * @param bool   $bodyEncoded  Whether body is still encoded by Content-Encoding
     * @param string $effectiveUrl Effective URL of the response
     *
     * @throws   HTTP_Request2_MessageException if status line is invalid according to spec
     */
    public function __construct($statusLine, $bodyEncoded = true, $effectiveUrl = null)
    {
        if (!preg_match('!^HTTP/(\d\.\d) (\d{3})(?: (.+))?!', $statusLine, $m)) {
            throw new HTTP_Request2_MessageException(
                "Malformed response: {$statusLine}",
                HTTP_Request2_Exception::MALFORMED_RESPONSE
            );
        }
        $this->version      = $m[1];
        $this->code         = intval($m[2]);
        $this->reasonPhrase = !empty($m[3]) ? trim($m[3]) : self::getDefaultReasonPhrase($this->code);
        $this->bodyEncoded  = (bool)$bodyEncoded;
        $this->effectiveUrl = (string)$effectiveUrl;
    }

    /**
     * Parses the line from HTTP response filling $headers array
     *
     * The method should be called after reading the line from socket or receiving
     * it into cURL callback. Passing an empty string here indicates the end of
     * response headers and triggers additional processing, so be sure to pass an
     * empty string in the end.
     *
     * @param string $headerLine Line from HTTP response
     */
    public function parseHeaderLine($headerLine)
    {
        $headerLine = trim($headerLine, "\r\n");

        if ('' == $headerLine) {
            // empty string signals the end of headers, process the received ones
            if (!empty($this->headers['set-cookie'])) {
                $cookies = is_array($this->headers['set-cookie'])?
                           $this->headers['set-cookie']:
                           array($this->headers['set-cookie']);
                foreach ($cookies as $cookieString) {
                    $this->parseCookie($cookieString);
                }
                unset($this->headers['set-cookie']);
            }
            foreach (array_keys($this->headers) as $k) {
                if (is_array($this->headers[$k])) {
                    $this->headers[$k] = implode(', ', $this->headers[$k]);
                }
            }

        } elseif (preg_match('!^([^\x00-\x1f\x7f-\xff()<>@,;:\\\\"/\[\]?={}\s]+):(.+)$!', $headerLine, $m)) {
            // string of the form header-name: header value
            $name  = strtolower($m[1]);
            $value = trim($m[2]);
            if (empty($this->headers[$name])) {
                $this->headers[$name] = $value;
            } else {
                if (!is_array($this->headers[$name])) {
                    $this->headers[$name] = array($this->headers[$name]);
                }
                $this->headers[$name][] = $value;
            }
            $this->lastHeader = $name;

        } elseif (preg_match('!^\s+(.+)$!', $headerLine, $m) && $this->lastHeader) {
            // continuation of a previous header
            if (!is_array($this->headers[$this->lastHeader])) {
                $this->headers[$this->lastHeader] .= ' ' . trim($m[1]);
            } else {
                $key = count($this->headers[$this->lastHeader]) - 1;
                $this->headers[$this->lastHeader][$key] .= ' ' . trim($m[1]);
            }
        }
    }

    /**
     * Parses a Set-Cookie header to fill $cookies array
     *
     * @param string $cookieString value of Set-Cookie header
     *
     * @link     http://web.archive.org/web/20080331104521/http://cgi.netscape.com/newsref/std/cookie_spec.html
     */
    protected function parseCookie($cookieString)
    {
        $cookie = array(
            'expires' => null,
            'domain'  => null,
            'path'    => null,
            'secure'  => false
        );

        if (!strpos($cookieString, ';')) {
            // Only a name=value pair
            $pos = strpos($cookieString, '=');
            $cookie['name']  = trim(substr($cookieString, 0, $pos));
            $cookie['value'] = trim(substr($cookieString, $pos + 1));

        } else {
            // Some optional parameters are supplied
            $elements = explode(';', $cookieString);
            $pos = strpos($elements[0], '=');
            $cookie['name']  = trim(substr($elements[0], 0, $pos));
            $cookie['value'] = trim(substr($elements[0], $pos + 1));

            for ($i = 1; $i < count($elements); $i++) {
                if (false === strpos($elements[$i], '=')) {
                    $elName  = trim($elements[$i]);
                    $elValue = null;
                } else {
                    list ($elName, $elValue) = array_map('trim', explode('=', $elements[$i]));
                }
                $elName = strtolower($elName);
                if ('secure' == $elName) {
                    $cookie['secure'] = true;
                } elseif ('expires' == $elName) {
                    $cookie['expires'] = str_replace('"', '', $elValue);
                } elseif ('path' == $elName || 'domain' == $elName) {
                    $cookie[$elName] = urldecode($elValue);
                } else {
                    $cookie[$elName] = $elValue;
                }
            }
        }
        $this->cookies[] = $cookie;
    }

    /**
     * Appends a string to the response body
     *
     * @param string $bodyChunk part of response body
     */
    public function appendBody($bodyChunk)
    {
        $this->body .= $bodyChunk;
    }

    /**
     * Returns the effective URL of the response
     *
     * This may be different from the request URL if redirects were followed.
     *
     * @return string
     * @link   http://pear.php.net/bugs/bug.php?id=18412
     */
    public function getEffectiveUrl()
    {
        return $this->effectiveUrl;
    }

    /**
     * Returns the status code
     *
     * @return   integer
     */
    public function getStatus()
    {
        return $this->code;
    }

    /**
     * Returns the reason phrase
     *
     * @return   string
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    /**
     * Whether response is a redirect that can be automatically handled by HTTP_Request2
     *
     * @return   bool
     */
    public function isRedirect()
    {
        return in_array($this->code, array(300, 301, 302, 303, 307))
               && isset($this->headers['location']);
    }

    /**
     * Returns either the named header or all response headers
     *
     * @param string $headerName Name of header to return
     *
     * @return   string|array    Value of $headerName header (null if header is
     *                           not present), array of all response headers if
     *                           $headerName is null
     */
    public function getHeader($headerName = null)
    {
        if (null === $headerName) {
            return $this->headers;
        } else {
            $headerName = strtolower($headerName);
            return isset($this->headers[$headerName])? $this->headers[$headerName]: null;
        }
    }

    /**
     * Returns cookies set in response
     *
     * @return   array
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Returns the body of the response
     *
     * @return   string
     * @throws   HTTP_Request2_Exception if body cannot be decoded
     */
    public function getBody()
    {
        if (0 == strlen($this->body) || !$this->bodyEncoded
            || !in_array(strtolower($this->getHeader('content-encoding')), array('gzip', 'deflate'))
        ) {
            return $this->body;

        } else {
            if (extension_loaded('mbstring') && (2 & ini_get('mbstring.func_overload'))) {
                $oldEncoding = mb_internal_encoding();
                mb_internal_encoding('8bit');
            }

            try {
                switch (strtolower($this->getHeader('content-encoding'))) {
                case 'gzip':
                    $decoded = self::decodeGzip($this->body);
                    break;
                case 'deflate':
                    $decoded = self::decodeDeflate($this->body);
                }
            } catch (Exception $e) {
            }

            if (!empty($oldEncoding)) {
                mb_internal_encoding($oldEncoding);
            }
            if (!empty($e)) {
                throw $e;
            }
            return $decoded;
        }
    }

    /**
     * Get the HTTP version of the response
     *
     * @return   string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Checks whether data starts with GZIP format identification bytes from RFC 1952
     *
     * @param string $data gzip-encoded (presumably) data
     *
     * @return bool
     */
    public static function hasGzipIdentification($data)
    {
        return 0 === strcmp(substr($data, 0, 2), "\x1f\x8b");
    }

    /**
     * Tries to parse GZIP format header in the given string
     *
     * If the header conforms to RFC 1952, its length is returned. If any
     * sanity check fails, HTTP_Request2_MessageException is thrown.
     *
     * Note: This function might be usable outside of HTTP_Request2 so it might
     * be good idea to be moved to some common package. (Delian Krustev)
     *
     * @param string  $data         Either the complete response body or
     *                              the leading part of it
     * @param boolean $dataComplete Whether $data contains complete response body
     *
     * @return int  gzip header length in bytes
     * @throws HTTP_Request2_MessageException
     * @link   http://tools.ietf.org/html/rfc1952
     */
    public static function parseGzipHeader($data, $dataComplete = false)
    {
        // if data is complete, trailing 8 bytes should be present for size and crc32
        $length = strlen($data) - ($dataComplete ? 8 : 0);

        if ($length < 10 || !self::hasGzipIdentification($data)) {
            throw new HTTP_Request2_MessageException(
                'The data does not seem to contain a valid gzip header',
                HTTP_Request2_Exception::DECODE_ERROR
            );
        }

        $method = ord(substr($data, 2, 1));
        if (8 != $method) {
            throw new HTTP_Request2_MessageException(
                'Error parsing gzip header: unknown compression method',
                HTTP_Request2_Exception::DECODE_ERROR
            );
        }
        $flags = ord(substr($data, 3, 1));
        if ($flags & 224) {
            throw new HTTP_Request2_MessageException(
                'Error parsing gzip header: reserved bits are set',
                HTTP_Request2_Exception::DECODE_ERROR
            );
        }

        // header is 10 bytes minimum. may be longer, though.
        $headerLength = 10;
        // extra fields, need to skip 'em
        if ($flags & 4) {
            if ($length - $headerLength - 2 < 0) {
                throw new HTTP_Request2_MessageException(
                    'Error parsing gzip header: data too short',
                    HTTP_Request2_Exception::DECODE_ERROR
                );
            }
            $extraLength = unpack('v', substr($data, 10, 2));
            if ($length - $headerLength - 2 - $extraLength[1] < 0) {
                throw new HTTP_Request2_MessageException(
                    'Error parsing gzip header: data too short',
                    HTTP_Request2_Exception::DECODE_ERROR
                );
            }
            $headerLength += $extraLength[1] + 2;
        }
        // file name, need to skip that
        if ($flags & 8) {
            if ($length - $headerLength - 1 < 0) {
                throw new HTTP_Request2_MessageException(
                    'Error parsing gzip header: data too short',
                    HTTP_Request2_Exception::DECODE_ERROR
                );
            }
            $filenameLength = strpos(substr($data, $headerLength), chr(0));
            if (false === $filenameLength
                || $length - $headerLength - $filenameLength - 1 < 0
            ) {
                throw new HTTP_Request2_MessageException(
                    'Error parsing gzip header: data too short',
                    HTTP_Request2_Exception::DECODE_ERROR
                );
            }
            $headerLength += $filenameLength + 1;
        }
        // comment, need to skip that also
        if ($flags & 16) {
            if ($length - $headerLength - 1 < 0) {
                throw new HTTP_Request2_MessageException(
                    'Error parsing gzip header: data too short',
                    HTTP_Request2_Exception::DECODE_ERROR
                );
            }
            $commentLength = strpos(substr($data, $headerLength), chr(0));
            if (false === $commentLength
                || $length - $headerLength - $commentLength - 1 < 0
            ) {
                throw new HTTP_Request2_MessageException(
                    'Error parsing gzip header: data too short',
                    HTTP_Request2_Exception::DECODE_ERROR
                );
            }
            $headerLength += $commentLength + 1;
        }
        // have a CRC for header. let's check
        if ($flags & 2) {
            if ($length - $headerLength - 2 < 0) {
                throw new HTTP_Request2_MessageException(
                    'Error parsing gzip header: data too short',
                    HTTP_Request2_Exception::DECODE_ERROR
                );
            }
            $crcReal   = 0xffff & crc32(substr($data, 0, $headerLength));
            $crcStored = unpack('v', substr($data, $headerLength, 2));
            if ($crcReal != $crcStored[1]) {
                throw new HTTP_Request2_MessageException(
                    'Header CRC check failed',
                    HTTP_Request2_Exception::DECODE_ERROR
                );
            }
            $headerLength += 2;
        }
        return $headerLength;
    }

    /**
     * Decodes the message-body encoded by gzip
     *
     * The real decoding work is done by gzinflate() built-in function, this
     * method only parses the header and checks data for compliance with
     * RFC 1952
     *
     * @param string $data gzip-encoded data
     *
     * @return   string  decoded data
     * @throws   HTTP_Request2_LogicException
     * @throws   HTTP_Request2_MessageException
     * @link     http://tools.ietf.org/html/rfc1952
     */
    public static function decodeGzip($data)
    {
        // If it doesn't look like gzip-encoded data, don't bother
        if (!self::hasGzipIdentification($data)) {
            return $data;
        }
        if (!function_exists('gzinflate')) {
            throw new HTTP_Request2_LogicException(
                'Unable to decode body: gzip extension not available',
                HTTP_Request2_Exception::MISCONFIGURATION
            );
        }

        // unpacked data CRC and size at the end of encoded data
        $tmp = unpack('V2', substr($data, -8));
        $dataCrc  = $tmp[1];
        $dataSize = $tmp[2];

        $headerLength = self::parseGzipHeader($data, true);

        // don't pass $dataSize to gzinflate, see bugs #13135, #14370
        $unpacked = gzinflate(substr($data, $headerLength, -8));
        if (false === $unpacked) {
            throw new HTTP_Request2_MessageException(
                'gzinflate() call failed',
                HTTP_Request2_Exception::DECODE_ERROR
            );
        } elseif ($dataSize != strlen($unpacked)) {
            throw new HTTP_Request2_MessageException(
                'Data size check failed',
                HTTP_Request2_Exception::DECODE_ERROR
            );
        } elseif ((0xffffffff & $dataCrc) != (0xffffffff & crc32($unpacked))) {
            throw new HTTP_Request2_MessageException(
                'Data CRC check failed',
                HTTP_Request2_Exception::DECODE_ERROR
            );
        }
        return $unpacked;
    }

    /**
     * Decodes the message-body encoded by deflate
     *
     * @param string $data deflate-encoded data
     *
     * @return   string  decoded data
     * @throws   HTTP_Request2_LogicException
     */
    public static function decodeDeflate($data)
    {
        if (!function_exists('gzuncompress')) {
            throw new HTTP_Request2_LogicException(
                'Unable to decode body: gzip extension not available',
                HTTP_Request2_Exception::MISCONFIGURATION
            );
        }
        // RFC 2616 defines 'deflate' encoding as zlib format from RFC 1950,
        // while many applications send raw deflate stream from RFC 1951.
        // We should check for presence of zlib header and use gzuncompress() or
        // gzinflate() as needed. See bug #15305
        $header = unpack('n', substr($data, 0, 2));
        return (0 == $header[1] % 31)? gzuncompress($data): gzinflate($data);
    }
}
?>