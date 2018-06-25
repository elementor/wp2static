<?php
namespace Aws\S3;
use Aws\CacheInterface;
use Aws\CommandInterface;
use Aws\LruArrayCache;
use Aws\MultiRegionClient as BaseClient;
use Aws\Exception\AwsException;
use Aws\S3\Exception\PermanentRedirectException;
use GuzzleHttp\Promise;
class S3MultiRegionClient extends BaseClient implements S3ClientInterface
{
    use S3ClientTrait;
    private $cache;
    public static function getArguments()
    {
        $args = parent::getArguments();
        $regionDef = $args['region'] + ['default' => function (array &$args) {
            $availableRegions = array_keys($args['partition']['regions']);
            return end($availableRegions);
        }];
        unset($args['region']);
        return $args + [
            'bucket_region_cache' => [
                'type' => 'config',
                'valid' => [CacheInterface::class],
                'doc' => 'Cache of regions in which given buckets are located.',
                'default' => function () { return new LruArrayCache; },
            ],
            'region' => $regionDef,
        ];
    }
    public function __construct(array $args)
    {
        parent::__construct($args);
        $this->cache = $this->getConfig('bucket_region_cache');
        $this->getHandlerList()->prependInit(
            $this->determineRegionMiddleware(),
            'determine_region'
        );
    }
    private function determineRegionMiddleware()
    {
        return function (callable $handler) {
            return function (CommandInterface $command) use ($handler) {
                $cacheKey = $this->getCacheKey($command['Bucket']);
                if (
                    empty($command['@region']) &&
                    $region = $this->cache->get($cacheKey)
                ) {
                    $command['@region'] = $region;
                }
                return Promise\coroutine(function () use (
                    $handler,
                    $command,
                    $cacheKey
                ) {
                    try {
                        yield $handler($command);
                    } catch (PermanentRedirectException $e) {
                        if (empty($command['Bucket'])) {
                            throw $e;
                        }
                        $result = $e->getResult();
                        $region = null;
                        if (isset($result['@metadata']['headers']['x-amz-bucket-region'])) {
                            $region = $result['@metadata']['headers']['x-amz-bucket-region'];
                            $this->cache->set($cacheKey, $region);
                        } else {
                            $region = (yield $this->determineBucketRegionAsync(
                                $command['Bucket']
                            ));
                        }
                        $command['@region'] = $region;
                        yield $handler($command);
                    } catch (AwsException $e) {
                        if ($e->getAwsErrorCode() === 'AuthorizationHeaderMalformed') {
                            $region = $this->determineBucketRegionFromExceptionBody(
                                $e->getResponse()->getBody()
                            );
                            if (!empty($region)) {
                                $this->cache->set($cacheKey, $region);
                                $command['@region'] = $region;
                                yield $handler($command);
                            } else {
                                throw $e;
                            }
                        } else {
                            throw $e;
                        }
                    }
                });
            };
        };
    }
    public function createPresignedRequest(CommandInterface $command, $expires)
    {
        if (empty($command['Bucket'])) {
            throw new \InvalidArgumentException('The S3\\MultiRegionClient'
                . ' cannot create presigned requests for commands without a'
                . ' specified bucket.');
        }
        $client = $this->getClientFromPool(
            $this->determineBucketRegion($command['Bucket'])
        );
        return $client->createPresignedRequest(
            $client->getCommand($command->getName(), $command->toArray()),
            $expires
        );
    }
    public function getObjectUrl($bucket, $key)
    {
        $regionalClient = $this->getClientFromPool(
            $this->determineBucketRegion($bucket)
        );
        return $regionalClient->getObjectUrl($bucket, $key);
    }
    public function determineBucketRegionAsync($bucketName)
    {
        $cacheKey = $this->getCacheKey($bucketName);
        if ($cached = $this->cache->get($cacheKey)) {
            return Promise\promise_for($cached);
        }
        $regionalClient = $this->getClientFromPool();
        return $regionalClient->determineBucketRegionAsync($bucketName)
            ->then(
                function ($region) use ($cacheKey) {
                    $this->cache->set($cacheKey, $region);
                    return $region;
                }
            );
    }
    private function getCacheKey($bucketName)
    {
        return "aws:s3:{$bucketName}:location";
    }
}
