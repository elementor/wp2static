<?php
namespace Aws\Common\Exception;
use Aws\Common\Exception\Parser\ExceptionParserInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
class NamespaceExceptionFactory implements ExceptionFactoryInterface
{
    protected $parser;
    protected $baseNamespace;
    protected $defaultException;
    public function __construct(
        ExceptionParserInterface $parser,
        $baseNamespace,
        $defaultException = 'Aws\Common\Exception\ServiceResponseException'
    ) {
        $this->parser = $parser;
        $this->baseNamespace = $baseNamespace;
        $this->defaultException = $defaultException;
    }
    public function fromResponse(RequestInterface $request, Response $response)
    {
        $parts = $this->parser->parse($request, $response);
        $className = $this->baseNamespace . '\\' . str_replace(array('AWS.', '.'), '', $parts['code']);
        if (substr($className, -9) !== 'Exception') {
            $className .= 'Exception';
        }
        $className = class_exists($className) ? $className : $this->defaultException;
        return $this->createException($className, $request, $response, $parts);
    }
    protected function createException($className, RequestInterface $request, Response $response, array $parts)
    {
        $class = new $className($parts['message']);
        if ($class instanceof ServiceResponseException) {
            $class->setExceptionCode($parts['code']);
            $class->setExceptionType($parts['type']);
            $class->setResponse($response);
            $class->setRequest($request);
            $class->setRequestId($parts['request_id']);
        }
        return $class;
    }
}
