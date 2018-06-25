<?php
namespace Aws\Api\Parser;
use Aws\Api\Service;
use Aws\CommandInterface;
use Aws\ResultInterface;
use Psr\Http\Message\ResponseInterface;
abstract class AbstractParser
{
    protected $api;
    public function __construct(Service $api)
    {
        $this->api = $api;
    }
    abstract public function __invoke(
        CommandInterface $command,
        ResponseInterface $response
    );
}
