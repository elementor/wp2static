<?php
namespace Aws\Common\Exception\Parser;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
interface ExceptionParserInterface
{
    public function parse(RequestInterface $request, Response $response);
}
