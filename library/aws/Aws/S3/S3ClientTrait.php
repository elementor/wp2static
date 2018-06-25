<?php
namespace Aws\S3;
use Aws\Api\Parser\PayloadParserTrait;
use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\HandlerList;
use Aws\ResultInterface;
use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
trait S3ClientTrait
{
    use PayloadParserTrait;
    public function upload(
        $bucket,
        $key,
        $body,
        $acl = 'private',
        array $options = []
    ) {
        return $this
            ->uploadAsync($bucket, $key, $body, $acl, $options)
            ->wait();
    }
    public function uploadAsync(
        $bucket,
        $key,
        $body,
        $acl = 'private',
        array $options = []
    ) {
        return (new ObjectUploader($this, $bucket, $key, $body, $acl, $options))
            ->promise();
    }
    public function copy(
        $fromB,
        $fromK,
        $destB,
        $destK,
        $acl = 'private',
        array $opts = []
    ) {
        return $this->copyAsync($fromB, $fromK, $destB, $destK, $acl, $opts)
            ->wait();
    }
    public function copyAsync(
        $fromB,
        $fromK,
        $destB,
        $destK,
        $acl = 'private',
        array $opts = []
    ) {
        $source = [
            'Bucket' => $fromB,
            'Key' => $fromK,
        ];
        if (isset($opts['version_id'])) {
            $source['VersionId'] = $opts['version_id'];
        }
        $destination = [
            'Bucket' => $destB,
            'Key' => $destK
        ];
        return (new ObjectCopier($this, $source, $destination, $acl, $opts))
            ->promise();
    }
    public function registerStreamWrapper()
    {
        StreamWrapper::register($this);
    }
    public function deleteMatchingObjects(
        $bucket,
        $prefix = '',
        $regex = '',
        array $options = []
    ) {
        $this->deleteMatchingObjectsAsync($bucket, $prefix, $regex, $options)
            ->wait();
    }
    public function deleteMatchingObjectsAsync(
        $bucket,
        $prefix = '',
        $regex = '',
        array $options = []
    ) {
        if (!$prefix && !$regex) {
            return new RejectedPromise(
                new \RuntimeException('A prefix or regex is required.')
            );
        }
        $params = ['Bucket' => $bucket, 'Prefix' => $prefix];
        $iter = $this->getIterator('ListObjects', $params);
        if ($regex) {
            $iter = \Aws\filter($iter, function ($c) use ($regex) {
                return preg_match($regex, $c['Key']);
            });
        }
        return BatchDelete::fromIterator($this, $bucket, $iter, $options)
            ->promise();
    }
    public function uploadDirectory(
        $directory,
        $bucket,
        $keyPrefix = null,
        array $options = []
    ) {
        $this->uploadDirectoryAsync($directory, $bucket, $keyPrefix, $options)
            ->wait();
    }
    public function uploadDirectoryAsync(
        $directory,
        $bucket,
        $keyPrefix = null,
        array $options = []
    ) {
        $d = "s3:
        return (new Transfer($this, $directory, $d, $options))->promise();
    }
    public function downloadBucket(
        $directory,
        $bucket,
        $keyPrefix = '',
        array $options = []
    ) {
        $this->downloadBucketAsync($directory, $bucket, $keyPrefix, $options)
            ->wait();
    }
    public function downloadBucketAsync(
        $directory,
        $bucket,
        $keyPrefix = '',
        array $options = []
    ) {
        $s = "s3:
        return (new Transfer($this, $s, $directory, $options))->promise();
    }
    public function determineBucketRegion($bucketName)
    {
        return $this->determineBucketRegionAsync($bucketName)->wait();
    }
    public function determineBucketRegionAsync($bucketName)
    {
        $command = $this->getCommand('HeadBucket', ['Bucket' => $bucketName]);
        $handlerList = clone $this->getHandlerList();
        $handlerList->remove('s3.permanent_redirect');
        $handlerList->remove('signer');
        $handler = $handlerList->resolve();
        return $handler($command)
            ->then(static function (ResultInterface $result) {
                return $result['@metadata']['headers']['x-amz-bucket-region'];
            }, function (AwsException $e) {
                $response = $e->getResponse();
                if ($response === null) {
                    throw $e;
                }
                if ($e->getAwsErrorCode() === 'AuthorizationHeaderMalformed') {
                    $region = $this->determineBucketRegionFromExceptionBody(
                        $response->getBody()
                    );
                    if (!empty($region)) {
                        return $region;
                    }
                    throw $e;
                }
                return $response->getHeaderLine('x-amz-bucket-region');
            });
    }
    private function determineBucketRegionFromExceptionBody($responseBody)
    {
        try {
            $element = $this->parseXml($responseBody);
            if (!empty($element->Region)) {
                return (string)$element->Region;
            }
        } catch (\Exception $e) {
        }
        return false;
    }
    public function doesBucketExist($bucket)
    {
        return $this->checkExistenceWithCommand(
            $this->getCommand('HeadBucket', ['Bucket' => $bucket])
        );
    }
    public function doesObjectExist($bucket, $key, array $options = [])
    {
        return $this->checkExistenceWithCommand(
            $this->getCommand('HeadObject', [
                    'Bucket' => $bucket,
                    'Key'    => $key
                ] + $options)
        );
    }
    private function checkExistenceWithCommand(CommandInterface $command)
    {
        try {
            $this->execute($command);
            return true;
        } catch (S3Exception $e) {
            if ($e->getAwsErrorCode() == 'AccessDenied') {
                return true;
            }
            if ($e->getStatusCode() >= 500) {
                throw $e;
            }
            return false;
        }
    }
    abstract public function execute(CommandInterface $command);
    abstract public function getCommand($name, array $args = []);
    abstract public function getHandlerList();
    abstract public function getIterator($name, array $args = []);
}
