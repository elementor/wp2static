<?php
namespace Aws;
use Aws\Api\Parser\Exception\ParserException;
use GuzzleHttp\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
class WrappedHttpHandler
{
    private $httpHandler;
    private $parser;
    private $errorParser;
    private $exceptionClass;
    private $collectStats;
    public function __construct(
        callable $httpHandler,
        callable $parser,
        callable $errorParser,
        $exceptionClass = 'Aws\Exception\AwsException',
        $collectStats = false
    ) {
        $this->httpHandler = $httpHandler;
        $this->parser = $parser;
        $this->errorParser = $errorParser;
        $this->exceptionClass = $exceptionClass;
        $this->collectStats = $collectStats;
    }
    public function __invoke(
        CommandInterface $command,
        RequestInterface $request
    ) {
        $fn = $this->httpHandler;
        $options = $command['@http'] ?: [];
        $stats = [];
        if ($this->collectStats) {
            $options['http_stats_receiver'] = static function (
                array $transferStats
            ) use (&$stats) {
                $stats = $transferStats;
            };
        } elseif (isset($options['http_stats_receiver'])) {
            throw new \InvalidArgumentException('Providing a custom HTTP stats'
                . ' receiver to Aws\WrappedHttpHandler is not supported.');
        }
        return Promise\promise_for($fn($request, $options))
            ->then(
                function (
                    ResponseInterface $res
                ) use ($command, $request, &$stats) {
                    return $this->parseResponse($command, $request, $res, $stats);
                },
                function ($err) use ($request, $command, &$stats) {
                    if (is_array($err)) {
                        $err = $this->parseError(
                            $err,
                            $request,
                            $command,
                            $stats
                        );
                    }
                    return new Promise\RejectedPromise($err);
                }
            );
    }
    private function parseResponse(
        CommandInterface $command,
        RequestInterface $request,
        ResponseInterface $response,
        array $stats
    ) {
        $parser = $this->parser;
        $status = $response->getStatusCode();
        $result = $status < 300
            ? $parser($command, $response)
            : new Result();
        $metadata = [
            'statusCode'    => $status,
            'effectiveUri'  => (string) $request->getUri(),
            'headers'       => [],
            'transferStats' => [],
        ];
        if (!empty($stats)) {
            $metadata['transferStats']['http'] = [$stats];
        }
        foreach ($response->getHeaders() as $name => $values) {
            $metadata['headers'][strtolower($name)] = $values[0];
        }
        $result['@metadata'] = $metadata;
        return $result;
    }
    private function parseError(
        array $err,
        RequestInterface $request,
        CommandInterface $command,
        array $stats
    ) {
        if (!isset($err['exception'])) {
            throw new \RuntimeException('The HTTP handler was rejected without an "exception" key value pair.');
        }
        $serviceError = "AWS HTTP error: " . $err['exception']->getMessage();
        if (!isset($err['response'])) {
            $parts = ['response' => null];
        } else {
            try {
                $parts = call_user_func($this->errorParser, $err['response']);
                $serviceError .= " {$parts['code']} ({$parts['type']}): "
                    . "{$parts['message']} - " . $err['response']->getBody();
            } catch (ParserException $e) {
                $parts = [];
                $serviceError .= ' Unable to parse error information from '
                    . "response - {$e->getMessage()}";
            }
            $parts['response'] = $err['response'];
        }
        $parts['exception'] = $err['exception'];
        $parts['request'] = $request;
        $parts['connection_error'] = !empty($err['connection_error']);
        $parts['transfer_stats'] = $stats;
        return new $this->exceptionClass(
            sprintf(
                'Error executing "%s" on "%s"; %s',
                $command->getName(),
                $request->getUri(),
                $serviceError
            ),
            $command,
            $parts,
            $err['exception']
        );
    }
}
