<?php
namespace Aws\S3;
use GuzzleHttp\Promise\PromisorInterface;
use GuzzleHttp\Psr7;
use Psr\Http\Message\StreamInterface;
class ObjectUploader implements PromisorInterface
{
    const DEFAULT_MULTIPART_THRESHOLD = 16777216;
    private $client;
    private $bucket;
    private $key;
    private $body;
    private $acl;
    private $options;
    private static $defaults = [
        'before_upload' => null,
        'concurrency'   => 3,
        'mup_threshold' => self::DEFAULT_MULTIPART_THRESHOLD,
        'params'        => [],
        'part_size'     => null,
    ];
    public function __construct(
        S3ClientInterface $client,
        $bucket,
        $key,
        $body,
        $acl = 'private',
        array $options = []
    ) {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->key = $key;
        $this->body = Psr7\stream_for($body);
        $this->acl = $acl;
        $this->options = $options + self::$defaults;
    }
    public function promise()
    {
        $mup_threshold = $this->options['mup_threshold'];
        if ($this->requiresMultipart($this->body, $mup_threshold)) {
            return (new MultipartUploader($this->client, $this->body, [
                    'bucket' => $this->bucket,
                    'key'    => $this->key,
                    'acl'    => $this->acl
                ] + $this->options))->promise();
        }
        $command = $this->client->getCommand('PutObject', [
                'Bucket' => $this->bucket,
                'Key'    => $this->key,
                'Body'   => $this->body,
                'ACL'    => $this->acl,
            ] + $this->options['params']);
        if (is_callable($this->options['before_upload'])) {
            $this->options['before_upload']($command);
        }
        return $this->client->executeAsync($command);
    }
    public function upload()
    {
        return $this->promise()->wait();
    }
    private function requiresMultipart(StreamInterface &$body, $threshold)
    {
        if ($body->getSize() !== null) {
            return $body->getSize() >= $threshold;
        }
        $buffer = Psr7\stream_for();
        Psr7\copy_to_stream($body, $buffer, MultipartUploader::PART_MIN_SIZE);
        if ($buffer->getSize() < MultipartUploader::PART_MIN_SIZE) {
            $buffer->seek(0);
            $body = $buffer;
            return false;
        }
        if ($body->isSeekable()) {
            $body->seek(0);
        } else {
            $buffer->seek(0);
            $body = new Psr7\AppendStream([$buffer, $body]);
        }
        return true;
    }
}
