<?php
namespace Aws;
use GuzzleHttp\Promise;
class ResultPaginator implements \Iterator
{
    private $client;
    private $operation;
    private $args;
    private $config;
    private $result;
    private $nextToken;
    private $requestCount = 0;
    public function __construct(
        AwsClientInterface $client,
        $operation,
        array $args,
        array $config
    ) {
        $this->client = $client;
        $this->operation = $operation;
        $this->args = $args;
        $this->config = $config;
    }
    public function each(callable $handleResult)
    {
        return Promise\coroutine(function () use ($handleResult) {
            $nextToken = null;
            do {
                $command = $this->createNextCommand($this->args, $nextToken);
                $result = (yield $this->client->executeAsync($command));
                $nextToken = $this->determineNextToken($result);
                $retVal = $handleResult($result);
                if ($retVal !== null) {
                    yield Promise\promise_for($retVal);
                }
            } while ($nextToken);
        });
    }
    public function search($expression)
    {
        return flatmap($this, function (Result $result) use ($expression) {
            return (array) $result->search($expression);
        });
    }
    public function current()
    {
        return $this->valid() ? $this->result : false;
    }
    public function key()
    {
        return $this->valid() ? $this->requestCount - 1 : null;
    }
    public function next()
    {
        $this->result = null;
    }
    public function valid()
    {
        if ($this->result) {
            return true;
        }
        if ($this->nextToken || !$this->requestCount) {
            $this->result = $this->client->execute(
                $this->createNextCommand($this->args, $this->nextToken)
            );
            $this->nextToken = $this->determineNextToken($this->result);
            $this->requestCount++;
            return true;
        }
        return false;
    }
    public function rewind()
    {
        $this->requestCount = 0;
        $this->nextToken = null;
        $this->result = null;
    }
    private function createNextCommand(array $args, array $nextToken = null)
    {
        return $this->client->getCommand($this->operation, array_merge($args, ($nextToken ?: [])));
    }
    private function determineNextToken(Result $result)
    {
        if (!$this->config['output_token']) {
            return null;
        }
        if ($this->config['more_results']
            && !$result->search($this->config['more_results'])
        ) {
            return null;
        }
        $nextToken = is_scalar($this->config['output_token'])
            ? [$this->config['input_token'] => $this->config['output_token']]
            : array_combine($this->config['input_token'], $this->config['output_token']);
        return array_filter(array_map(function ($outputToken) use ($result) {
            return $result->search($outputToken);
        }, $nextToken));
    }
}
