<?php
namespace Aws;
use Aws\Exception\AwsException;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\RejectedPromise;
use Psr\Http\Message\RequestInterface;
class MockHandler implements \Countable
{
    private $queue;
    private $lastCommand;
    private $lastRequest;
    private $onFulfilled;
    private $onRejected;
    public function __construct(
        array $resultOrQueue = [],
        callable $onFulfilled = null,
        callable $onRejected = null
    ) {
        $this->onFulfilled = $onFulfilled;
        $this->onRejected = $onRejected;
        if ($resultOrQueue) {
            call_user_func_array([$this, 'append'], $resultOrQueue);
        }
    }
    public function append()
    {
        foreach (func_get_args() as $value) {
            if ($value instanceof ResultInterface
                || $value instanceof AwsException
                || is_callable($value)
            ) {
                $this->queue[] = $value;
            } else {
                throw new \InvalidArgumentException('Expected an Aws\ResultInterface or Aws\Exception\AwsException.');
            }
        }
    }
    public function __invoke(
        CommandInterface $command,
        RequestInterface $request
    ) {
        if (!$this->queue) {
            $last = $this->lastCommand
                ? ' The last command sent was ' . $this->lastCommand->getName() . '.'
                : '';
            throw new \RuntimeException('Mock queue is empty. Trying to send a '
                . $command->getName() . ' command failed.' . $last);
        }
        $this->lastCommand = $command;
        $this->lastRequest = $request;
        $result = array_shift($this->queue);
        if (is_callable($result)) {
            $result = $result($command, $request);
        }
        if ($result instanceof \Exception) {
            $result = new RejectedPromise($result);
        } else {
            $meta = $result['@metadata'];
            if (!isset($meta['effectiveUri'])) {
                $meta['effectiveUri'] = (string) $request->getUri();
            }
            if (!isset($meta['statusCode'])) {
                $meta['statusCode'] = 200;
            }
            $result['@metadata'] = $meta;
            $result = Promise\promise_for($result);
        }
        $result->then($this->onFulfilled, $this->onRejected);
        return $result;
    }
    public function getLastRequest()
    {
        return $this->lastRequest;
    }
    public function getLastCommand()
    {
        return $this->lastCommand;
    }
    public function count()
    {
        return count($this->queue);
    }
}
