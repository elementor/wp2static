<?php
namespace Aws\Common\Exception;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
class ServiceResponseException extends RuntimeException
{
    protected $response;
    protected $request;
    protected $requestId;
    protected $exceptionType;
    protected $exceptionCode;
    public function setExceptionCode($code)
    {
        $this->exceptionCode = $code;
    }
    public function getExceptionCode()
    {
        return $this->exceptionCode;
    }
    public function setExceptionType($type)
    {
        $this->exceptionType = $type;
    }
    public function getExceptionType()
    {
        return $this->exceptionType;
    }
    public function setRequestId($id)
    {
        $this->requestId = $id;
    }
    public function getRequestId()
    {
        return $this->requestId;
    }
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }
    public function getResponse()
    {
        return $this->response;
    }
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }
    public function getRequest()
    {
        return $this->request;
    }
    public function getStatusCode()
    {
        return $this->response ? $this->response->getStatusCode() : null;
    }
    public function __toString()
    {
        $message = get_class($this) . ': '
            . 'AWS Error Code: ' . $this->getExceptionCode() . ', '
            . 'Status Code: ' . $this->getStatusCode() . ', '
            . 'AWS Request ID: ' . $this->getRequestId() . ', '
            . 'AWS Error Type: ' . $this->getExceptionType() . ', '
            . 'AWS Error Message: ' . $this->getMessage();
        if ($this->request) {
            $message .= ', ' . 'User-Agent: ' . $this->request->getHeader('User-Agent');
        }
        return $message;
    }
    public function getAwsRequestId()
    {
        return $this->requestId;
    }
    public function getAwsErrorType()
    {
        return $this->exceptionType;
    }
    public function getAwsErrorCode()
    {
        return $this->exceptionCode;
    }
}
