<?php
/**
 * An observer that saves response body to stream, possibly uncompressing it
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
 * @author    Delian Krustev <krustev@krustev.net>
 * @author    Alexey Borzov <avb@php.net>
 * @copyright 2008-2016 Alexey Borzov <avb@php.net>
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link      http://pear.php.net/package/HTTP_Request2
 */

require_once 'HTTP/Request2/Response.php';

/**
 * An observer that saves response body to stream, possibly uncompressing it
 *
 * This Observer is written in compliment to pear's HTTP_Request2 in order to
 * avoid reading the whole response body in memory. Instead it writes the body
 * to a stream. If the body is transferred with content-encoding set to
 * "deflate" or "gzip" it is decoded on the fly.
 *
 * The constructor accepts an already opened (for write) stream (file_descriptor).
 * If the response is deflate/gzip encoded a "zlib.inflate" filter is applied
 * to the stream. When the body has been read from the request and written to
 * the stream ("receivedBody" event) the filter is removed from the stream.
 *
 * The "zlib.inflate" filter works fine with pure "deflate" encoding. It does
 * not understand the "deflate+zlib" and "gzip" headers though, so they have to
 * be removed prior to being passed to the stream. This is done in the "update"
 * method.
 *
 * It is also possible to limit the size of written extracted bytes by passing
 * "max_bytes" to the constructor. This is important because e.g. 1GB of
 * zeroes take about a MB when compressed.
 *
 * Exceptions are being thrown if data could not be written to the stream or
 * the written bytes have already exceeded the requested maximum. If the "gzip"
 * header is malformed or could not be parsed an exception will be thrown too.
 *
 * Example usage follows:
 *
 * <code>
 * require_once 'HTTP/Request2.php';
 * require_once 'HTTP/Request2/Observer/UncompressingDownload.php';
 *
 * #$inPath = 'http://carsten.codimi.de/gzip.yaws/daniels.html';
 * #$inPath = 'http://carsten.codimi.de/gzip.yaws/daniels.html?deflate=on';
 * $inPath = 'http://carsten.codimi.de/gzip.yaws/daniels.html?deflate=on&zlib=on';
 * #$outPath = "/dev/null";
 * $outPath = "delme";
 *
 * $stream = fopen($outPath, 'wb');
 * if (!$stream) {
 *     throw new Exception('fopen failed');
 * }
 *
 * $request = new HTTP_Request2(
 *     $inPath,
 *     HTTP_Request2::METHOD_GET,
 *     array(
 *         'store_body'        => false,
 *         'connect_timeout'   => 5,
 *         'timeout'           => 10,
 *         'ssl_verify_peer'   => true,
 *         'ssl_verify_host'   => true,
 *         'ssl_cafile'        => null,
 *         'ssl_capath'        => '/etc/ssl/certs',
 *         'max_redirects'     => 10,
 *         'follow_redirects'  => true,
 *         'strict_redirects'  => false
 *     )
 * );
 *
 * $observer = new HTTP_Request2_Observer_UncompressingDownload($stream, 9999999);
 * $request->attach($observer);
 *
 * $response = $request->send();
 *
 * fclose($stream);
 * echo "OK\n";
 * </code>
 *
 * @category HTTP
 * @package  HTTP_Request2
 * @author   Delian Krustev <krustev@krustev.net>
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/HTTP_Request2
 */
class HTTP_Request2_Observer_UncompressingDownload implements SplObserver
{
    /**
     * The stream to write response body to
     * @var resource
     */
    private $_stream;

    /**
     * zlib.inflate filter possibly added to stream
     * @var resource
     */
    private $_streamFilter;

    /**
     * The value of response's Content-Encoding header
     * @var string
     */
    private $_encoding;

    /**
     * Whether the observer is still waiting for gzip/deflate header
     * @var bool
     */
    private $_processingHeader = true;

    /**
     * Starting position in the stream observer writes to
     * @var int
     */
    private $_startPosition = 0;

    /**
     * Maximum bytes to write
     * @var int|null
     */
    private $_maxDownloadSize;

    /**
     * Whether response being received is a redirect
     * @var bool
     */
    private $_redirect = false;

    /**
     * Accumulated body chunks that may contain (gzip) header
     * @var string
     */
    private $_possibleHeader = '';

    /**
     * Class constructor
     *
     * Note that there might be problems with max_bytes and files bigger
     * than 2 GB on 32bit platforms
     *
     * @param resource $stream          a stream (or file descriptor) opened for writing.
     * @param int      $maxDownloadSize maximum bytes to write
     */
    public function __construct($stream, $maxDownloadSize = null)
    {
        $this->_stream = $stream;
        if ($maxDownloadSize) {
            $this->_maxDownloadSize = $maxDownloadSize;
            $this->_startPosition   = ftell($this->_stream);
        }
    }

    /**
     * Called when the request notifies us of an event.
     *
     * @param SplSubject $request The HTTP_Request2 instance
     *
     * @return void
     * @throws HTTP_Request2_MessageException
     */
    public function update(SplSubject $request)
    {
        /* @var $request HTTP_Request2 */
        $event   = $request->getLastEvent();
        $encoded = false;

        /* @var $event['data'] HTTP_Request2_Response */
        switch ($event['name']) {
        case 'receivedHeaders':
            $this->_processingHeader = true;
            $this->_redirect = $event['data']->isRedirect();
            $this->_encoding = strtolower($event['data']->getHeader('content-encoding'));
            $this->_possibleHeader = '';
            break;

        case 'receivedEncodedBodyPart':
            if (!$this->_streamFilter
                && ($this->_encoding === 'deflate' || $this->_encoding === 'gzip')
            ) {
                $this->_streamFilter = stream_filter_append(
                    $this->_stream, 'zlib.inflate', STREAM_FILTER_WRITE
                );
            }
            $encoded = true;
            // fall-through is intentional

        case 'receivedBodyPart':
            if ($this->_redirect) {
                break;
            }

            if (!$encoded || !$this->_processingHeader) {
                $bytes = fwrite($this->_stream, $event['data']);

            } else {
                $offset = 0;
                $this->_possibleHeader .= $event['data'];
                if ('deflate' === $this->_encoding) {
                    if (2 > strlen($this->_possibleHeader)) {
                        break;
                    }
                    $header = unpack('n', substr($this->_possibleHeader, 0, 2));
                    if (0 == $header[1] % 31) {
                        $offset = 2;
                    }

                } elseif ('gzip' === $this->_encoding) {
                    if (10 > strlen($this->_possibleHeader)) {
                        break;
                    }
                    try {
                        $offset = HTTP_Request2_Response::parseGzipHeader($this->_possibleHeader, false);

                    } catch (HTTP_Request2_MessageException $e) {
                        // need more data?
                        if (false !== strpos($e->getMessage(), 'data too short')) {
                            break;
                        }
                        throw $e;
                    }
                }

                $this->_processingHeader = false;
                $bytes = fwrite($this->_stream, substr($this->_possibleHeader, $offset));
            }

            if (false === $bytes) {
                throw new HTTP_Request2_MessageException('fwrite failed.');
            }

            if ($this->_maxDownloadSize
                && ftell($this->_stream) - $this->_startPosition > $this->_maxDownloadSize
            ) {
                throw new HTTP_Request2_MessageException(sprintf(
                    'Body length limit (%d bytes) reached',
                    $this->_maxDownloadSize
                ));
            }
            break;

        case 'receivedBody':
            if ($this->_streamFilter) {
                stream_filter_remove($this->_streamFilter);
                $this->_streamFilter = null;
            }
            break;
        }
    }
}
