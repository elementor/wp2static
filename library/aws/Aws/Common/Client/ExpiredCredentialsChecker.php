<?php
namespace Aws\Common\Client;
use Aws\Common\Credentials\AbstractRefreshableCredentials;
use Aws\Common\Client\AwsClientInterface;
use Aws\Common\Exception\Parser\ExceptionParserInterface;
use Guzzle\Http\Exception\HttpException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Backoff\BackoffStrategyInterface;
use Guzzle\Plugin\Backoff\AbstractBackoffStrategy;
class ExpiredCredentialsChecker extends AbstractBackoffStrategy
{
    protected $retryable = array(
        'RequestExpired' => true,
        'ExpiredTokenException' => true,
        'ExpiredToken' => true
    );
    protected $exceptionParser;
    public function __construct(ExceptionParserInterface $exceptionParser, BackoffStrategyInterface $next = null) {
        $this->exceptionParser = $exceptionParser;
        $this->next = $next;
    }
    public function makesDecision()
    {
        return true;
    }
    protected function getDelay($retries, RequestInterface $request, Response $response = null, HttpException $e = null)
    {
        if ($response && $response->isClientError()) {
            $parts = $this->exceptionParser->parse($request, $response);
            if (!isset($this->retryable[$parts['code']]) || !$request->getClient()) {
                return null;
            }
            $client = $request->getClient();
            if (!($client->getCredentials() instanceof AbstractRefreshableCredentials)) {
                return null;
            }
            $client->getSignature()->signRequest($request, $client->getCredentials()->setExpiration(-1));
            return 0;
        }
    }
}
