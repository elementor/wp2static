<?php
namespace Aws\S3;
use Guzzle\Http\Exception\HttpException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Backoff\BackoffStrategyInterface;
use Guzzle\Plugin\Backoff\AbstractBackoffStrategy;
class IncompleteMultipartUploadChecker extends AbstractBackoffStrategy
{
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
        if ($response && $request->getMethod() === 'POST'
            && $request instanceof EntityEnclosingRequestInterface
            && $response->getStatusCode() == 200
            && strpos($request->getBody(), '<CompleteMultipartUpload xmlns') !== false
            && strpos($response->getBody(), '<CompleteMultipartUploadResult xmlns') === false
        ) {
            return true;
        }
    }
}
