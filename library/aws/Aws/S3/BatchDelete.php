<?php
namespace Aws\S3;
use Aws\AwsClientInterface;
use Aws\S3\Exception\DeleteMultipleObjectsException;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromisorInterface;
use GuzzleHttp\Promise\PromiseInterface;
class BatchDelete implements PromisorInterface
{
    private $bucket;
    private $client;
    private $before;
    private $cachedPromise;
    private $promiseCreator;
    private $batchSize = 1000;
    private $queue = [];
    public static function fromListObjects(
        AwsClientInterface $client,
        array $listObjectsParams,
        array $options = []
    ) {
        $iter = $client->getPaginator('ListObjects', $listObjectsParams);
        $bucket = $listObjectsParams['Bucket'];
        $fn = function (BatchDelete $that) use ($iter) {
            return $iter->each(function ($result) use ($that) {
                $promises = [];
                if (is_array($result['Contents'])) {
                    foreach ($result['Contents'] as $object) {
                        if ($promise = $that->enqueue($object)) {
                            $promises[] = $promise;
                        }
                    }
                }
                return $promises ? Promise\all($promises) : null;
            });
        };
        return new self($client, $bucket, $fn, $options);
    }
    public static function fromIterator(
        AwsClientInterface $client,
        $bucket,
        \Iterator $iter,
        array $options = []
    ) {
        $fn = function (BatchDelete $that) use ($iter) {
            return Promise\coroutine(function () use ($that, $iter) {
                foreach ($iter as $obj) {
                    if ($promise = $that->enqueue($obj)) {
                        yield $promise;
                    }
                }
            });
        };
        return new self($client, $bucket, $fn, $options);
    }
    public function promise()
    {
        if (!$this->cachedPromise) {
            $this->cachedPromise = $this->createPromise();
        }
        return $this->cachedPromise;
    }
    public function delete()
    {
        $this->promise()->wait();
    }
    private function __construct(
        AwsClientInterface $client,
        $bucket,
        callable $promiseFn,
        array $options = []
    ) {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->promiseCreator = $promiseFn;
        if (isset($options['before'])) {
            if (!is_callable($options['before'])) {
                throw new \InvalidArgumentException('before must be callable');
            }
            $this->before = $options['before'];
        }
        if (isset($options['batch_size'])) {
            if ($options['batch_size'] <= 0) {
                throw new \InvalidArgumentException('batch_size is not > 0');
            }
            $this->batchSize = min($options['batch_size'], 1000);
        }
    }
    private function enqueue(array $obj)
    {
        $this->queue[] = $obj;
        return count($this->queue) >= $this->batchSize
            ? $this->flushQueue()
            : null;
    }
    private function flushQueue()
    {
        static $validKeys = ['Key' => true, 'VersionId' => true];
        if (count($this->queue) === 0) {
            return null;
        }
        $batch = [];
        while ($obj = array_shift($this->queue)) {
            $batch[] = array_intersect_key($obj, $validKeys);
        }
        $command = $this->client->getCommand('DeleteObjects', [
            'Bucket' => $this->bucket,
            'Delete' => ['Objects' => $batch]
        ]);
        if ($this->before) {
            call_user_func($this->before, $command);
        }
        return $this->client->executeAsync($command)
            ->then(function ($result) {
                if (!empty($result['Errors'])) {
                    throw new DeleteMultipleObjectsException(
                        $result['Deleted'] ?: [],
                        $result['Errors']
                    );
                }
                return $result;
            });
    }
    private function createPromise()
    {
        $promise = call_user_func($this->promiseCreator, $this);
        $this->promiseCreator = null;
        $cleanup = function () {
            $this->before = $this->client = $this->queue = null;
        };
        return $promise->then(
            function () use ($cleanup)  {
                return Promise\promise_for($this->flushQueue())
                    ->then($cleanup);
            },
            function ($reason) use ($cleanup)  {
                $cleanup();
                return Promise\rejection_for($reason);
            }
        );
    }
}
