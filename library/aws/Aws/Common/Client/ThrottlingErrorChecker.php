<?php
namespace Aws\Common\Client;
use Aws\Common\Exception\Parser\ExceptionParserInterface;
use Guzzle\Http\Exception\HttpException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Backoff\BackoffStrategyInterface;
use Guzzle\Plugin\Backoff\AbstractBackoffStrategy;
class ThrottlingErrorChecker extends AbstractBackoffStrategy
{
    protected static $throttlingExceptions = array(
        'RequestLimitExceeded'                   => true,
        'Throttling'                             => true,
        'ThrottlingException'                    => true,
        'ProvisionedThroughputExceededException' => true,
        'RequestThrottled'                       => true,
    );
    protected $exceptionParser;
    public function __construct(ExceptionParserInterface $exceptionParser, BackoffStrategyInterface $next = null)
    {
        $this->exceptionParser = $exceptionParser;
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
        if ($response && $response->isClientError()) {
            $parts = $this->exceptionParser->parse($request, $response);
            return isset(self::$throttlingExceptions[$parts['code']]) ? true : null;
        }
    }
}
