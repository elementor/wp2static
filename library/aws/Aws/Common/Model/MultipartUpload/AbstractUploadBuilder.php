<?php
namespace Aws\Common\Model\MultipartUpload;
use Aws\Common\Client\AwsClientInterface;
use Aws\Common\Exception\InvalidArgumentException;
use Guzzle\Http\EntityBody;
abstract class AbstractUploadBuilder
{
    protected $client;
    protected $state;
    protected $source;
    protected $headers = array();
    public static function newInstance()
    {
        return new static;
    }
    public function setClient(AwsClientInterface $client)
    {
        $this->client = $client;
        return $this;
    }
    public function resumeFrom($state)
    {
        $this->state = $state;
        return $this;
    }
    public function setSource($source)
    {
        if (is_string($source)) {
            if (!file_exists($source)) {
                throw new InvalidArgumentException("File does not exist: {$source}");
            }
            clearstatcache(true, $source);
            $source = fopen($source, 'r');
        }
        $this->source = EntityBody::factory($source);
        if ($this->source->isSeekable() && $this->source->getSize() == 0) {
            throw new InvalidArgumentException('Empty body provided to upload builder');
        }
        return $this;
    }
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }
    abstract public function build();
    abstract protected function initiateMultipartUpload();
}
