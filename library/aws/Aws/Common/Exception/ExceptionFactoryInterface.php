<?php
namespace Aws\Common\Exception;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
interface ExceptionFactoryInterface
{
    public function fromResponse(RequestInterface $request, Response $response);
}
