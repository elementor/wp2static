<?php
/**
 * Mock adapter intended for testing
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
 * Base class for HTTP_Request2 adapters
 */
require_once 'HTTP/Request2/Adapter.php';

/**
 * Mock adapter intended for testing
 *
 * Can be used to test applications depending on HTTP_Request2 package without
 * actually performing any HTTP requests. This adapter will return responses
 * previously added via addResponse()
 * <code>
 * $mock = new HTTP_Request2_Adapter_Mock();
 * $mock->addResponse("HTTP/1.1 ... ");
 *
 * $request = new HTTP_Request2();
 * $request->setAdapter($mock);
 *
 * // This will return the response set above
 * $response = $req->send();
 * </code>
 *
 * @category HTTP
 * @package  HTTP_Request2
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/HTTP_Request2
 */
class HTTP_Request2_Adapter_Mock extends HTTP_Request2_Adapter
{
    /**
     * A queue of responses to be returned by sendRequest()
     * @var  array
     */
    protected $responses = array();

    /**
     * Returns the next response from the queue built by addResponse()
     *
     * Only responses without explicit URLs or with URLs equal to request URL
     * will be considered. If matching response is not found or the queue is
     * empty then default empty response with status 400 will be returned,
     * if an Exception object was added to the queue it will be thrown.
     *
     * @param HTTP_Request2 $request HTTP request message
     *
     * @return   HTTP_Request2_Response
     * @throws   Exception
     */
    public function sendRequest(HTTP_Request2 $request)
    {
        $requestUrl = (string)$request->getUrl();
        $response   = null;
        foreach ($this->responses as $k => $v) {
            if (!$v[1] || $requestUrl == $v[1]) {
                $response = $v[0];
                array_splice($this->responses, $k, 1);
                break;
            }
        }
        if (!$response) {
            return self::createResponseFromString("HTTP/1.1 400 Bad Request\r\n\r\n");

        } elseif ($response instanceof HTTP_Request2_Response) {
            return $response;

        } else {
            // rethrow the exception
            $class   = get_class($response);
            $message = $response->getMessage();
            $code    = $response->getCode();
            throw new $class($message, $code);
        }
    }

    /**
     * Adds response to the queue
     *
     * @param mixed  $response either a string, a pointer to an open file,
     *                         an instance of HTTP_Request2_Response or Exception
     * @param string $url      A request URL this response should be valid for
     *                         (see {@link http://pear.php.net/bugs/bug.php?id=19276})
     *
     * @throws   HTTP_Request2_Exception
     */
    public function addResponse($response, $url = null)
    {
        if (is_string($response)) {
            $response = self::createResponseFromString($response);
        } elseif (is_resource($response)) {
            $response = self::createResponseFromFile($response);
        } elseif (!$response instanceof HTTP_Request2_Response &&
                  !$response instanceof Exception
        ) {
            throw new HTTP_Request2_Exception('Parameter is not a valid response');
        }
        $this->responses[] = array($response, $url);
    }

    /**
     * Creates a new HTTP_Request2_Response object from a string
     *
     * @param string $str string containing HTTP response message
     *
     * @return   HTTP_Request2_Response
     * @throws   HTTP_Request2_Exception
     */
    public static function createResponseFromString($str)
    {
        $parts       = preg_split('!(\r?\n){2}!m', $str, 2);
        $headerLines = explode("\n", $parts[0]);
        $response    = new HTTP_Request2_Response(array_shift($headerLines));
        foreach ($headerLines as $headerLine) {
            $response->parseHeaderLine($headerLine);
        }
        $response->parseHeaderLine('');
        if (isset($parts[1])) {
            $response->appendBody($parts[1]);
        }
        return $response;
    }

    /**
     * Creates a new HTTP_Request2_Response object from a file
     *
     * @param resource $fp file pointer returned by fopen()
     *
     * @return   HTTP_Request2_Response
     * @throws   HTTP_Request2_Exception
     */
    public static function createResponseFromFile($fp)
    {
        $response = new HTTP_Request2_Response(fgets($fp));
        do {
            $headerLine = fgets($fp);
            $response->parseHeaderLine($headerLine);
        } while ('' != trim($headerLine));

        while (!feof($fp)) {
            $response->appendBody(fread($fp, 8192));
        }
        return $response;
    }
}
?>