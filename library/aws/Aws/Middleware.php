<?php
namespace Aws;
use Aws\Api\Service;
use Aws\Api\Validator;
use Aws\Credentials\CredentialsInterface;
use Aws\Exception\AwsException;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\LazyOpenStream;
use Psr\Http\Message\RequestInterface;
final class Middleware
{
    public static function sourceFile(
        Service $api,
        $bodyParameter = 'Body',
        $sourceParameter = 'SourceFile'
    ) {
        return function (callable $handler) use (
            $api,
            $bodyParameter,
            $sourceParameter
        ) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null)
            use (
                $handler,
                $api,
                $bodyParameter,
                $sourceParameter
            ) {
                $operation = $api->getOperation($command->getName());
                $source = $command[$sourceParameter];
                if ($source !== null
                    && $operation->getInput()->hasMember($bodyParameter)
                ) {
                    $command[$bodyParameter] = new LazyOpenStream($source, 'r');
                    unset($command[$sourceParameter]);
                }
                return $handler($command, $request);
            };
        };
    }
    public static function validation(Service $api, Validator $validator = null)
    {
        $validator = $validator ?: new Validator();
        return function (callable $handler) use ($api, $validator) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use ($api, $validator, $handler) {
                $operation = $api->getOperation($command->getName());
                $validator->validate(
                    $command->getName(),
                    $operation->getInput(),
                    $command->toArray()
                );
                return $handler($command, $request);
            };
        };
    }
    public static function requestBuilder(callable $serializer)
    {
        return function (callable $handler) use ($serializer) {
            return function (CommandInterface $command) use ($serializer, $handler) {
                return $handler($command, $serializer($command));
            };
        };
    }
    public static function signer(callable $credProvider, callable $signatureFunction)
    {
        return function (callable $handler) use ($signatureFunction, $credProvider) {
            return function (
                CommandInterface $command,
                RequestInterface $request
            ) use ($handler, $signatureFunction, $credProvider) {
                $signer = $signatureFunction($command);
                return $credProvider()->then(
                    function (CredentialsInterface $creds)
                    use ($handler, $command, $signer, $request) {
                        return $handler(
                            $command,
                            $signer->signRequest($request, $creds)
                        );
                    }
                );
            };
        };
    }
    public static function tap(callable $fn)
    {
        return function (callable $handler) use ($fn) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use ($handler, $fn) {
                $fn($command, $request);
                return $handler($command, $request);
            };
        };
    }
    public static function retry(
        callable $decider = null,
        callable $delay = null,
        $stats = false
    ) {
        $decider = $decider ?: RetryMiddleware::createDefaultDecider();
        $delay = $delay ?: [RetryMiddleware::class, 'exponentialDelay'];
        return function (callable $handler) use ($decider, $delay, $stats) {
            return new RetryMiddleware($decider, $delay, $handler, $stats);
        };
    }
    public static function invocationId()
    {
        return function (callable $handler) {
            return function (
                CommandInterface $command,
                RequestInterface $request
            ) use ($handler){
                return $handler($command, $request->withHeader(
                    'aws-sdk-invocation-id',
                    md5(uniqid(gethostname(), true))
                ));
            };
        };
    }
    public static function contentType(array $operations)
    {
        return function (callable $handler) use ($operations) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use ($handler, $operations) {
                if (!$request->hasHeader('Content-Type')
                    && in_array($command->getName(), $operations, true)
                    && ($uri = $request->getBody()->getMetadata('uri'))
                ) {
                    $request = $request->withHeader(
                        'Content-Type',
                        Psr7\mimetype_from_filename($uri) ?: 'application/octet-stream'
                    );
                }
                return $handler($command, $request);
            };
        };
    }
    public static function history(History $history)
    {
        return function (callable $handler) use ($history) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use ($handler, $history) {
                $ticket = $history->start($command, $request);
                return $handler($command, $request)
                    ->then(
                        function ($result) use ($history, $ticket) {
                            $history->finish($ticket, $result);
                            return $result;
                        },
                        function ($reason) use ($history, $ticket) {
                            $history->finish($ticket, $reason);
                            return Promise\rejection_for($reason);
                        }
                    );
            };
        };
    }
    public static function mapRequest(callable $f)
    {
        return function (callable $handler) use ($f) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use ($handler, $f) {
                return $handler($command, $f($request));
            };
        };
    }
    public static function mapCommand(callable $f)
    {
        return function (callable $handler) use ($f) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use ($handler, $f) {
                return $handler($f($command), $request);
            };
        };
    }
    public static function mapResult(callable $f)
    {
        return function (callable $handler) use ($f) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use ($handler, $f) {
                return $handler($command, $request)->then($f);
            };
        };
    }
    public static function timer()
    {
        return function (callable $handler) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use ($handler) {
                $start = microtime(true);
                return $handler($command, $request)
                    ->then(
                        function (ResultInterface $res) use ($start) {
                            if (!isset($res['@metadata'])) {
                                $res['@metadata'] = [];
                            }
                            if (!isset($res['@metadata']['transferStats'])) {
                                $res['@metadata']['transferStats'] = [];
                            }
                            $res['@metadata']['transferStats']['total_time']
                                = microtime(true) - $start;
                            return $res;
                        },
                        function ($err) use ($start) {
                            if ($err instanceof AwsException) {
                                $err->setTransferInfo([
                                    'total_time' => microtime(true) - $start,
                                ] + $err->getTransferInfo());
                            }
                            return Promise\rejection_for($err);
                        }
                    );
            };
        };
    }
}
