<?php
namespace Aws\S3;
use Guzzle\Http\Exception\HttpException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Backoff\BackoffStrategyInterface;
use Guzzle\Plugin\Backoff\AbstractBackoffStrategy;
class SocketTimeoutChecker extends AbstractBackoffStrategy
{
    const ERR = 'Your socket connection to the server was not read from or written to within the timeout period';
    public function __construct(BackoffStrategyInterface $next = null)
    {
        if ($next) {
            $this->setNext($next);
        }
    }
    public function makesDecision()
    {
        return true;
    }
    protected function getDelay(
        $retries,
        RequestInterface $request,
        Response $response = null,
        HttpException $e = null
    ) {
        if ($response
            && $response->getStatusCode() == 400
            && strpos($response->getBody(), self::ERR)
        ) {
            return true;
        }
    }
}
