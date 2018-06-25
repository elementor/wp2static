<?php
namespace Aws\Common\Model\MultipartUpload;
use Aws\Common\Exception\RuntimeException;
abstract class AbstractTransferState implements TransferStateInterface
{
    protected $uploadId;
    protected $parts = array();
    protected $aborted = false;
    public function __construct(UploadIdInterface $uploadId)
    {
        $this->uploadId = $uploadId;
    }
    public function getUploadId()
    {
        return $this->uploadId;
    }
    public function getFromId($key)
    {
        $params = $this->uploadId->toParams();
        return isset($params[$key]) ? $params[$key] : null;
    }
    public function getPart($partNumber)
    {
        return isset($this->parts[$partNumber]) ? $this->parts[$partNumber] : null;
    }
    public function addPart(UploadPartInterface $part)
    {
        $partNumber = $part->getPartNumber();
        $this->parts[$partNumber] = $part;
        return $this;
    }
    public function hasPart($partNumber)
    {
        return isset($this->parts[$partNumber]);
    }
    public function getPartNumbers()
    {
        return array_keys($this->parts);
    }
    public function setAborted($aborted)
    {
        $this->aborted = (bool) $aborted;
        return $this;
    }
    public function isAborted()
    {
        return $this->aborted;
    }
    public function count()
    {
        return count($this->parts);
    }
    public function getIterator()
    {
        return new \ArrayIterator($this->parts);
    }
    public function serialize()
    {
        return serialize(get_object_vars($this));
    }
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        foreach (get_object_vars($this) as $property => $oldValue) {
            if (array_key_exists($property, $data)) {
                $this->{$property} = $data[$property];
            } else {
                throw new RuntimeException("The {$property} property could be restored during unserialization.");
            }
        }
    }
}
