<?php
namespace Aws\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Aws\CommandInterface;
use Aws\ResultInterface;
class AwsException extends \RuntimeException
{
    private $response;
    private $request;
    private $result;
    private $command;
    private $requestId;
    private $errorType;
    private $errorCode;
    private $connectionError;
    private $transferInfo;
    private $errorMessage;
    public function __construct(
        $message,
        CommandInterface $command,
        array $context = [],
        \Exception $previous = null
    ) {
        $this->command = $command;
        $this->response = isset($context['response']) ? $context['response'] : null;
        $this->request = isset($context['request']) ? $context['request'] : null;
        $this->requestId = isset($context['request_id'])
            ? $context['request_id']
            : null;
        $this->errorType = isset($context['type']) ? $context['type'] : null;
        $this->errorCode = isset($context['code']) ? $context['code'] : null;
        $this->connectionError = !empty($context['connection_error']);
        $this->result = isset($context['result']) ? $context['result'] : null;
        $this->transferInfo = isset($context['transfer_stats'])
            ? $context['transfer_stats']
            : [];
        $this->errorMessage = isset($context['message'])
            ? $context['message']
            : null;
        parent::__construct($message, 0, $previous);
    }
    public function __toString()
    {
        if (!$this->getPrevious()) {
            return parent::__toString();
        }
        return sprintf(
            "exception '%s' with message '%s'\n\n%s",
            get_class($this),
            $this->getMessage(),
            parent::__toString()
        );
    }
    public function getCommand()
    {
        return $this->command;
    }
    public function getAwsErrorMessage()
    {
        return $this->errorMessage;
    }
    public function getRequest()
    {
        return $this->request;
    }
    public function getResponse()
    {
        return $this->response;
    }
    public function getResult()
    {
        return $this->result;
    }
    public function isConnectionError()
    {
        return $this->connectionError;
    }
    public function getStatusCode()
    {
        return $this->response ? $this->response->getStatusCode() : null;
    }
    public function getAwsRequestId()
    {
        return $this->requestId;
    }
    public function getAwsErrorType()
    {
        return $this->errorType;
    }
    public function getAwsErrorCode()
    {
        return $this->errorCode;
    }
    public function getTransferInfo($name = null)
    {
        if (!$name) {
            return $this->transferInfo;
        }
        return isset($this->transferInfo[$name])
            ? $this->transferInfo[$name]
            : null;
    }
    public function setTransferInfo(array $info)
    {
        $this->transferInfo = $info;
    }
}
