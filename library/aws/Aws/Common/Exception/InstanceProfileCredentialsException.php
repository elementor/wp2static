<?php
namespace Aws\Common\Exception;
use Aws\Common\Exception\RuntimeException;
class InstanceProfileCredentialsException extends RuntimeException
{
    protected $statusCode;
    public function setStatusCode($code)
    {
        $this->statusCode = $code;
    }
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
