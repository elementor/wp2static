<?php
namespace Aws\S3;
use Aws\AwsClientInterface;
use Aws\CommandInterface;
use Aws\ResultInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
interface S3ClientInterface extends AwsClientInterface
{
    public function createPresignedRequest(CommandInterface $command, $expires);
    public function getObjectUrl($bucket, $key);
    public function doesBucketExist($bucket);
    public function doesObjectExist($bucket, $key, array $options = []);
    public function registerStreamWrapper();
    public function deleteMatchingObjects(
        $bucket,
        $prefix = '',
        $regex = '',
        array $options = []
    );
    public function deleteMatchingObjectsAsync(
        $bucket,
        $prefix = '',
        $regex = '',
        array $options = []
    );
    public function upload(
        $bucket,
        $key,
        $body,
        $acl = 'private',
        array $options = []
    );
    public function uploadAsync(
        $bucket,
        $key,
        $body,
        $acl = 'private',
        array $options = []
    );
    public function copy(
        $fromBucket,
        $fromKey,
        $destBucket,
        $destKey,
        $acl = 'private',
        array $options = []
    );
    public function copyAsync(
        $fromBucket,
        $fromKey,
        $destBucket,
        $destKey,
        $acl = 'private',
        array $options = []
    );
    public function uploadDirectory(
        $directory,
        $bucket,
        $keyPrefix = null,
        array $options = []
    );
    public function uploadDirectoryAsync(
        $directory,
        $bucket,
        $keyPrefix = null,
        array $options = []
    );
    public function downloadBucket(
        $directory,
        $bucket,
        $keyPrefix = '',
        array $options = []
    );
    public function downloadBucketAsync(
        $directory,
        $bucket,
        $keyPrefix = '',
        array $options = []
    );
    public function determineBucketRegion($bucketName);
    public function determineBucketRegionAsync($bucketName);
}
