<?php
namespace Aws;
use GuzzleHttp\Promise\PromisorInterface;
use GuzzleHttp\Promise\EachPromise;
class CommandPool implements PromisorInterface
{
    private $each;
    public function __construct(
        AwsClientInterface $client,
        $commands,
        array $config = []
    ) {
        if (!isset($config['concurrency'])) {
            $config['concurrency'] = 25;
        }
        $before = $this->getBefore($config);
        $mapFn = function ($commands) use ($client, $before, $config) {
            foreach ($commands as $key => $command) {
                if (!($command instanceof CommandInterface)) {
                    throw new \InvalidArgumentException('Each value yielded by '
                        . 'the iterator must be an Aws\CommandInterface.');
                }
                if ($before) {
                    $before($command, $key);
                }
                if (!empty($config['preserve_iterator_keys'])) {
                    yield $key => $client->executeAsync($command);
                } else {
                    yield $client->executeAsync($command);
                }
            }
        };
        $this->each = new EachPromise($mapFn($commands), $config);
    }
    public function promise()
    {
        return $this->each->promise();
    }
    public static function batch(
        AwsClientInterface $client,
        $commands,
        array $config = []
    ) {
        $results = [];
        self::cmpCallback($config, 'fulfilled', $results);
        self::cmpCallback($config, 'rejected', $results);
        return (new self($client, $commands, $config))
            ->promise()
            ->then(static function () use (&$results) {
                ksort($results);
                return $results;
            })
            ->wait();
    }
    private function getBefore(array $config)
    {
        if (!isset($config['before'])) {
            return null;
        }
        if (is_callable($config['before'])) {
            return $config['before'];
        }
        throw new \InvalidArgumentException('before must be callable');
    }
    private static function cmpCallback(array &$config, $name, array &$results)
    {
        if (!isset($config[$name])) {
            $config[$name] = function ($v, $k) use (&$results) {
                $results[$k] = $v;
            };
        } else {
            $currentFn = $config[$name];
            $config[$name] = function ($v, $k) use (&$results, $currentFn) {
                $currentFn($v, $k);
                $results[$k] = $v;
            };
        }
    }
}
