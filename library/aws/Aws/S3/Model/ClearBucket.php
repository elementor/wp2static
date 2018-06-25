<?php
namespace Aws\S3\Model;
use Aws\Common\Client\AwsClientInterface;
use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Batch\FlushingBatch;
use Guzzle\Batch\ExceptionBufferingBatch;
use Guzzle\Batch\NotifyingBatch;
use Guzzle\Common\Exception\ExceptionCollection;
class ClearBucket extends AbstractHasDispatcher
{
    const AFTER_DELETE = 'clear_bucket.after_delete';
    const BEFORE_CLEAR = 'clear_bucket.before_clear';
    const AFTER_CLEAR = 'clear_bucket.after_clear';
    protected $client;
    protected $iterator;
    protected $mfa;
    public function __construct(AwsClientInterface $client, $bucket)
    {
        $this->client = $client;
        $this->bucket = $bucket;
    }
    public static function getAllEvents()
    {
        return array(self::AFTER_DELETE, self::BEFORE_CLEAR, self::AFTER_CLEAR);
    }
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;
        return $this;
    }
    public function getIterator()
    {
        if (!$this->iterator) {
            $this->iterator = $this->client->getIterator('ListObjectVersions', array(
                'Bucket' => $this->bucket
            ));
        }
        return $this->iterator;
    }
    public function setIterator(\Iterator $iterator)
    {
        $this->iterator = $iterator;
        return $this;
    }
    public function setMfa($mfa)
    {
        $this->mfa = $mfa;
        return $this;
    }
    public function clear()
    {
        $that = $this;
        $batch = DeleteObjectsBatch::factory($this->client, $this->bucket, $this->mfa);
        $batch = new NotifyingBatch($batch, function ($items) use ($that) {
            $that->dispatch(ClearBucket::AFTER_DELETE, array('keys' => $items));
        });
        $batch = new FlushingBatch(new ExceptionBufferingBatch($batch), 1000);
        $this->dispatch(self::BEFORE_CLEAR, array(
            'iterator' => $this->getIterator(),
            'batch'    => $batch,
            'mfa'      => $this->mfa
        ));
        $deleted = 0;
        foreach ($this->getIterator() as $object) {
            if (isset($object['VersionId'])) {
                $versionId = $object['VersionId'] == 'null' ? null : $object['VersionId'];
            } else {
                $versionId = null;
            }
            $batch->addKey($object['Key'], $versionId);
            $deleted++;
        }
        $batch->flush();
        if (count($batch->getExceptions())) {
            $e = new ExceptionCollection();
            foreach ($batch->getExceptions() as $exception) {
                $e->add($exception->getPrevious());
            }
            throw $e;
        }
        $this->dispatch(self::AFTER_CLEAR, array('deleted' => $deleted));
        return $deleted;
    }
}
