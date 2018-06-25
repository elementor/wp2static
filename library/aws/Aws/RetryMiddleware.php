<?php
namespace Aws;
use Aws\Exception\AwsException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise;
class RetryMiddleware
{
    private static $retryStatusCodes = [
        500 => true,
        502 => true,
        503 => true,
        504 => true
    ];
    private static $retryCodes = [
        'RequestLimitExceeded'                   => true,
        'Throttling'                             => true,
        'ThrottlingException'                    => true,
        'ThrottledException'                     => true,
        'ProvisionedThroughputExceededException' => true,
        'RequestThrottled'                       => true,
        'BandwidthLimitExceeded'                 => true,
        'RequestThrottledException'              => true,
    ];
    private $decider;
    private $delay;
    private $nextHandler;
    private $collectStats;
    public function __construct(
        callable $decider,
        callable $delay,
        callable $nextHandler,
        $collectStats = false
    ) {
        $this->decider = $decider;
        $this->delay = $delay;
        $this->nextHandler = $nextHandler;
        $this->collectStats = (bool) $collectStats;
    }
    public static function createDefaultDecider($maxRetries = 3)
    {
        $retryCurlErrors = [];
        if (extension_loaded('curl')) {
            $retryCurlErrors[CURLE_RECV_ERROR] = true;
        }
        return function (
            $retries,
            CommandInterface $command,
            RequestInterface $request,
            ResultInterface $result = null,
            $error = null
        ) use ($maxRetries, $retryCurlErrors) {
            $maxRetries = null !== $command['@retries'] ?
                $command['@retries']
                : $maxRetries;
            if ($retries >= $maxRetries) {
                return false;
            }
            if (!$error) {
                return isset(self::$retryStatusCodes[$result['@metadata']['statusCode']]);
            }
            if (!($error instanceof AwsException)) {
                return false;
            }
            if ($error->isConnectionError()) {
                return true;
            }
            if (isset(self::$retryCodes[$error->getAwsErrorCode()])) {
                return true;
            }
            if (isset(self::$retryStatusCodes[$error->getStatusCode()])) {
                return true;
            }
            if (count($retryCurlErrors)
                && ($previous = $error->getPrevious())
                && $previous instanceof RequestException
            ) {
                if (method_exists($previous, 'getHandlerContext')) {
                    $context = $previous->getHandlerContext();
                    return !empty($context['errno'])
                        && isset($retryCurlErrors[$context['errno']]);
                }
                $message = $previous->getMessage();
                foreach (array_keys($retryCurlErrors) as $curlError) {
                    if (strpos($message, 'cURL error ' . $curlError . ':') === 0) {
                        return true;
                    }
                }
            }
            return false;
        };
    }
    public static function exponentialDelay($retries)
    {
        return mt_rand(0, (int) min(20000, (int) pow(2, $retries) * 100));
    }
    public function __invoke(
        CommandInterface $command,
        RequestInterface $request = null
    ) {
        $retries = 0;
        $requestStats = [];
        $handler = $this->nextHandler;
        $decider = $this->decider;
        $delay = $this->delay;
        $request = $this->addRetryHeader($request, 0, 0);
        $g = function ($value) use (
            $handler,
            $decider,
            $delay,
            $command,
            $request,
            &$retries,
            &$requestStats,
            &$g
        ) {
            $this->updateHttpStats($value, $requestStats);
            if ($value instanceof \Exception || $value instanceof \Throwable) {
                if (!$decider($retries, $command, $request, null, $value)) {
                    return Promise\rejection_for(
                        $this->bindStatsToReturn($value, $requestStats)
                    );
                }
            } elseif ($value instanceof ResultInterface
                && !$decider($retries, $command, $request, $value, null)
            ) {
                return $this->bindStatsToReturn($value, $requestStats);
            }
            $delayBy = $delay($retries++);
            $command['@http']['delay'] = $delayBy;
            if ($this->collectStats) {
                $this->updateStats($retries, $delayBy, $requestStats);
            }
            $request = $this->addRetryHeader($request, $retries, $delayBy);
            return $handler($command, $request)->then($g, $g);
        };
        return $handler($command, $request)->then($g, $g);
    }
    private function addRetryHeader($request, $retries, $delayBy)
    {
        return $request->withHeader('aws-sdk-retry', "{$retries}/{$delayBy}");
    }
    private function updateStats($retries, $delay, array &$stats)
    {
        if (!isset($stats['total_retry_delay'])) {
            $stats['total_retry_delay'] = 0;
        }
        $stats['total_retry_delay'] += $delay;
        $stats['retries_attempted'] = $retries;
    }
    private function updateHttpStats($value, array &$stats)
    {
        if (empty($stats['http'])) {
            $stats['http'] = [];
        }
        if ($value instanceof AwsException) {
            $resultStats = isset($value->getTransferInfo('http')[0])
                ? $value->getTransferInfo('http')[0]
                : [];
            $stats['http'] []= $resultStats;
        } elseif ($value instanceof ResultInterface) {
            $resultStats = isset($value['@metadata']['transferStats']['http'][0])
                ? $value['@metadata']['transferStats']['http'][0]
                : [];
            $stats['http'] []= $resultStats;
        }
    }
    private function bindStatsToReturn($return, array $stats)
    {
        if ($return instanceof ResultInterface) {
            if (!isset($return['@metadata'])) {
                $return['@metadata'] = [];
            }
            $return['@metadata']['transferStats'] = $stats;
        } elseif ($return instanceof AwsException) {
            $return->setTransferInfo($stats);
        }
        return $return;
    }
}
