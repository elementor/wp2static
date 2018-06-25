<?php
namespace Aws;
use Aws\Exception\AwsException;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromisorInterface;
use GuzzleHttp\Promise\RejectedPromise;
class Waiter implements PromisorInterface
{
    private $client;
    private $name;
    private $args;
    private $config;
    private static $defaults = ['initDelay' => 0, 'before' => null];
    private static $required = [
        'acceptors',
        'delay',
        'maxAttempts',
        'operation',
    ];
    public function __construct(
        AwsClientInterface $client,
        $name,
        array $args = [],
        array $config = []
    ) {
        $this->client = $client;
        $this->name = $name;
        $this->args = $args;
        $this->config = $config + self::$defaults;
        foreach (self::$required as $key) {
            if (!isset($this->config[$key])) {
                throw new \InvalidArgumentException(
                    'The provided waiter configuration was incomplete.'
                );
            }
        }
        if ($this->config['before'] && !is_callable($this->config['before'])) {
            throw new \InvalidArgumentException(
                'The provided "before" callback is not callable.'
            );
        }
    }
    public function promise()
    {
        return Promise\coroutine(function () {
            $name = $this->config['operation'];
            for ($state = 'retry', $attempt = 1; $state === 'retry'; $attempt++) {
                $args = $this->getArgsForAttempt($attempt);
                $command = $this->client->getCommand($name, $args);
                try {
                    if ($this->config['before']) {
                        $this->config['before']($command, $attempt);
                    }
                    $result = (yield $this->client->executeAsync($command));
                } catch (AwsException $e) {
                    $result = $e;
                }
                $state = $this->determineState($result);
                if ($state === 'success') {
                    yield $command;
                } elseif ($state === 'failed') {
                    $msg = "The {$this->name} waiter entered a failure state.";
                    if ($result instanceof \Exception) {
                        $msg .= ' Reason: ' . $result->getMessage();
                    }
                    yield new RejectedPromise(new \RuntimeException($msg));
                } elseif ($state === 'retry'
                    && $attempt >= $this->config['maxAttempts']
                ) {
                    $state = 'failed';
                    yield new RejectedPromise(new \RuntimeException(
                        "The {$this->name} waiter failed after attempt #{$attempt}."
                    ));
                }
            }
        });
    }
    private function getArgsForAttempt($attempt)
    {
        $args = $this->args;
        $delay = ($attempt === 1)
            ? $this->config['initDelay']
            : $this->config['delay'];
        if (is_callable($delay)) {
            $delay = $delay($attempt);
        }
        if (!isset($args['@http'])) {
            $args['@http'] = [];
        }
        $args['@http']['delay'] = $delay * 1000;
        return $args;
    }
    private function determineState($result)
    {
        foreach ($this->config['acceptors'] as $acceptor) {
            $matcher = 'matches' . ucfirst($acceptor['matcher']);
            if ($this->{$matcher}($result, $acceptor)) {
                return $acceptor['state'];
            }
        }
        return $result instanceof \Exception ? 'failed' : 'retry';
    }
    private function matchesPath($result, array $acceptor)
    {
        return !($result instanceof ResultInterface)
            ? false
            : $acceptor['expected'] == $result->search($acceptor['argument']);
    }
    private function matchesPathAll($result, array $acceptor)
    {
        if (!($result instanceof ResultInterface)) {
            return false;
        }
        $actuals = $result->search($acceptor['argument']) ?: [];
        foreach ($actuals as $actual) {
            if ($actual != $acceptor['expected']) {
                return false;
            }
        }
        return true;
    }
    private function matchesPathAny($result, array $acceptor)
    {
        if (!($result instanceof ResultInterface)) {
            return false;
        }
        $actuals = $result->search($acceptor['argument']) ?: [];
        foreach ($actuals as $actual) {
            if ($actual == $acceptor['expected']) {
                return true;
            }
        }
        return false;
    }
    private function matchesStatus($result, array $acceptor)
    {
        if ($result instanceof ResultInterface) {
            return $acceptor['expected'] == $result['@metadata']['statusCode'];
        }
        if ($result instanceof AwsException && $response = $result->getResponse()) {
            return $acceptor['expected'] == $response->getStatusCode();
        }
        return false;
    }
    private function matchesError($result, array $acceptor)
    {
        if ($result instanceof AwsException) {
            return $result->isConnectionError()
                || $result->getAwsErrorCode() == $acceptor['expected'];
        }
        return false;
    }
}
