<?php
namespace Aws\S3;
use Aws\Exception\MultipartUploadException;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Promise\PromisorInterface;
use InvalidArgumentException;
class ObjectCopier implements PromisorInterface
{
    const DEFAULT_MULTIPART_THRESHOLD = MultipartUploader::PART_MAX_SIZE;
    private $client;
    private $source;
    private $destination;
    private $acl;
    private $options;
    private static $defaults = [
        'before_lookup' => null,
        'before_upload' => null,
        'concurrency'   => 5,
        'mup_threshold' => self::DEFAULT_MULTIPART_THRESHOLD,
        'params'        => [],
        'part_size'     => null,
        'version_id'    => null,
    ];
    public function __construct(
        S3ClientInterface $client,
        array $source,
        array $destination,
        $acl = 'private',
        array $options = []
    ) {
        $this->validateLocation($source);
        $this->validateLocation($destination);
        $this->client = $client;
        $this->source = $source;
        $this->destination = $destination;
        $this->acl = $acl;
        $this->options = $options + self::$defaults;
    }
    public function promise()
    {
        return \GuzzleHttp\Promise\coroutine(function () {
            $headObjectCommand = $this->client->getCommand(
                'HeadObject',
                $this->options['params'] + $this->source
            );
            if (is_callable($this->options['before_lookup'])) {
                $this->options['before_lookup']($headObjectCommand);
            }
            $objectStats = (yield $this->client->executeAsync(
                $headObjectCommand
            ));
            if ($objectStats['ContentLength'] > $this->options['mup_threshold']) {
                $mup = new MultipartCopy(
                    $this->client,
                    $this->getSourcePath(),
                    ['source_metadata' => $objectStats, 'acl' => $this->acl]
                        + $this->destination
                        + $this->options
                );
                yield $mup->promise();
            } else {
                $defaults = [
                    'ACL' => $this->acl,
                    'MetadataDirective' => 'COPY',
                    'CopySource' => $this->getSourcePath(),
                ];
                $params = array_diff_key($this->options, self::$defaults)
                    + $this->destination + $defaults + $this->options['params'];
                yield $this->client->executeAsync(
                    $this->client->getCommand('CopyObject', $params)
                );
            }
        });
    }
    public function copy()
    {
        return $this->promise()->wait();
    }
    private function validateLocation(array $location)
    {
        if (empty($location['Bucket']) || empty($location['Key'])) {
            throw new \InvalidArgumentException('Locations provided to an'
                . ' Aws\S3\ObjectCopier must have a non-empty Bucket and Key');
        }
    }
    private function getSourcePath()
    {
        $sourcePath = "/{$this->source['Bucket']}/"
            . rawurlencode($this->source['Key']);
        if (isset($this->source['VersionId'])) {
            $sourcePath .= "?versionId={$this->source['VersionId']}";
        }
        return $sourcePath;
    }
}
