<?php
/**
 * Base class for HTTP_Request2 adapters
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
 * Class representing a HTTP response
 */
require_once 'HTTP/Request2/Response.php';

/**
 * Base class for HTTP_Request2 adapters
 *
 * HTTP_Request2 class itself only defines methods for aggregating the request
 * data, all actual work of sending the request to the remote server and
 * receiving its response is performed by adapters.
 *
 * @category HTTP
 * @package  HTTP_Request2
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/HTTP_Request2
 */
abstract class HTTP_Request2_Adapter
{
    /**
     * A list of methods that MUST NOT have a request body, per RFC 2616
     * @var  array
     */
    protected static $bodyDisallowed = array('TRACE');

    /**
     * Methods having defined semantics for request body
     *
     * Content-Length header (indicating that the body follows, section 4.3 of
     * RFC 2616) will be sent for these methods even if no body was added
     *
     * @var  array
     * @link http://pear.php.net/bugs/bug.php?id=12900
     * @link http://pear.php.net/bugs/bug.php?id=14740
     */
    protected static $bodyRequired = array('POST', 'PUT');

    /**
     * Request being sent
     * @var  HTTP_Request2
     */
    protected $request;

    /**
     * Request body
     * @var  string|resource|HTTP_Request2_MultipartBody
     * @see  HTTP_Request2::getBody()
     */
    protected $requestBody;

    /**
     * Length of the request body
     * @var  integer
     */
    protected $contentLength;

    /**
     * Sends request to the remote server and returns its response
     *
     * @param HTTP_Request2 $request HTTP request message
     *
     * @return   HTTP_Request2_Response
     * @throws   HTTP_Request2_Exception
     */
    abstract public function sendRequest(HTTP_Request2 $request);

    /**
     * Calculates length of the request body, adds proper headers
     *
     * @param array &$headers associative array of request headers, this method
     *                        will add proper 'Content-Length' and 'Content-Type'
     *                        headers to this array (or remove them if not needed)
     */
    protected function calculateRequestLength(&$headers)
    {
        $this->requestBody = $this->request->getBody();

        if (is_string($this->requestBody)) {
            $this->contentLength = strlen($this->requestBody);
        } elseif (is_resource($this->requestBody)) {
            $stat = fstat($this->requestBody);
            $this->contentLength = $stat['size'];
            rewind($this->requestBody);
        } else {
            $this->contentLength = $this->requestBody->getLength();
            $headers['content-type'] = 'multipart/form-data; boundary=' .
                                       $this->requestBody->getBoundary();
            $this->requestBody->rewind();
        }

        if (in_array($this->request->getMethod(), self::$bodyDisallowed)
            || 0 == $this->contentLength
        ) {
            // No body: send a Content-Length header nonetheless (request #12900),
            // but do that only for methods that require a body (bug #14740)
            if (in_array($this->request->getMethod(), self::$bodyRequired)) {
                $headers['content-length'] = 0;
            } else {
                unset($headers['content-length']);
                // if the method doesn't require a body and doesn't have a
                // body, don't send a Content-Type header. (request #16799)
                unset($headers['content-type']);
            }
        } else {
            if (empty($headers['content-type'])) {
                $headers['content-type'] = 'application/x-www-form-urlencoded';
            }
            // Content-Length should not be sent for chunked Transfer-Encoding (bug #20125)
            if (!isset($headers['transfer-encoding'])) {
                $headers['content-length'] = $this->contentLength;
            }
        }
    }
}
?>
