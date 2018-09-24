<?php
/**
 * HTTP_Request2_Adapter
 *
 * @package WP2Static
 */

require_once 'Response.php';
abstract class HTTP_Request2_Adapter
{
    protected static $bodyDisallowed = array('TRACE');
    protected static $bodyRequired = array('POST', 'PUT');
    protected $request;
    protected $requestBody;
    protected $contentLength;
    abstract public function sendRequest(HTTP_Request2 $request);
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
            // but do that only for methods that require a body (bug #14740)
            if (in_array($this->request->getMethod(), self::$bodyRequired)) {
                $headers['content-length'] = 0;
            } else {
                unset($headers['content-length']);
                // body, don't send a Content-Type header. (request #16799)
                unset($headers['content-type']);
            }
        } else {
            if (empty($headers['content-type'])) {
                $headers['content-type'] = 'application/x-www-form-urlencoded';
            }
            if (!isset($headers['transfer-encoding'])) {
                $headers['content-length'] = $this->contentLength;
            }
        }
    }
}
?>
