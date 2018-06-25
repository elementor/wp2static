<?php
namespace Aws\S3\Exception;
use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\Multipart\UploadState;
class S3MultipartUploadException extends \Aws\Exception\MultipartUploadException
{
    private $bucket;
    private $key;
    private $filename;
    public function __construct(UploadState $state, $prev = null) {
        if (is_array($prev) && $error = $prev[key($prev)]) {
            $this->collectPathInfo($error->getCommand());
        } elseif ($prev instanceof AwsException) {
            $this->collectPathInfo($prev->getCommand());
        }
        parent::__construct($state, $prev);
    }
    public function getBucket()
    {
        return $this->bucket;
    }
    public function getKey()
    {
        return $this->key;
    }
    public function getSourceFileName()
    {
        return $this->filename;
    }
    private function collectPathInfo(CommandInterface $cmd)
    {
        if (empty($this->bucket) && isset($cmd['Bucket'])) {
            $this->bucket = $cmd['Bucket'];
        }
        if (empty($this->key) && isset($cmd['Key'])) {
            $this->key = $cmd['Key'];
        }
        if (empty($this->filename) && isset($cmd['Body'])) {
            $this->filename = $cmd['Body']->getMetadata('uri');
        }
    }
}
