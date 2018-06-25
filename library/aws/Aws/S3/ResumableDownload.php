<?php
namespace Aws\S3;
use Aws\Common\Exception\RuntimeException;
use Aws\Common\Exception\UnexpectedValueException;
use Guzzle\Http\EntityBody;
use Guzzle\Http\ReadLimitEntityBody;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Service\Resource\Model;
class ResumableDownload
{
    protected $client;
    protected $meta;
    protected $params;
    protected $target;
    public function __construct(S3Client $client, $bucket, $key, $target, array $params = array())
    {
        $this->params = $params;
        $this->client = $client;
        $this->params['Bucket'] = $bucket;
        $this->params['Key'] = $key;
        if (is_string($target)) {
            if (!($target = fopen($target, 'a+'))) {
                throw new RuntimeException("Unable to open {$target} for writing");
            }
            fseek($target, 0, SEEK_END);
        }
        $this->target = EntityBody::factory($target);
    }
    public function getBucket()
    {
        return $this->params['Bucket'];
    }
    public function getKey()
    {
        return $this->params['Key'];
    }
    public function getFilename()
    {
        return $this->target->getUri();
    }
    public function __invoke()
    {
        $command = $this->client->getCommand('HeadObject', $this->params);
        $this->meta = $command->execute();
        if ($this->target->ftell() >= $this->meta['ContentLength']) {
            return false;
        }
        $this->meta['ContentMD5'] = (string) $command->getResponse()->getHeader('Content-MD5');
        $this->params['SaveAs'] = new ReadLimitEntityBody(
            $this->target,
            $this->meta['ContentLength'],
            $this->target->ftell()
        );
        $result = $this->getRemaining();
        $this->checkIntegrity();
        return $result;
    }
    protected function getRemaining()
    {
        $current = $this->target->ftell();
        $targetByte = $this->meta['ContentLength'] - 1;
        $this->params['Range'] = "bytes={$current}-{$targetByte}";
        $this->params['SaveAs']->setOffset($current);
        $command = $this->client->getCommand('GetObject', $this->params);
        return $command->execute();
    }
    protected function checkIntegrity()
    {
        if ($this->target->isReadable() && $expected = $this->meta['ContentMD5']) {
            $actual = $this->target->getContentMd5();
            if ($actual != $expected) {
                throw new UnexpectedValueException(
                    "Message integrity check failed. Expected {$expected} but got {$actual}."
                );
            }
        }
    }
}
