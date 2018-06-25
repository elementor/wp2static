<?php
namespace Aws\Common\Model\MultipartUpload;
use Aws\Common\Exception\InvalidArgumentException;
abstract class AbstractUploadPart implements UploadPartInterface
{
    protected static $keyMap = array();
    protected $partNumber;
    public static function fromArray($data)
    {
        $part = new static();
        $part->loadData($data);
        return $part;
    }
    public function getPartNumber()
    {
        return $this->partNumber;
    }
    public function toArray()
    {
        $array = array();
        foreach (static::$keyMap as $key => $property) {
            $array[$key] = $this->{$property};
        }
        return $array;
    }
    public function serialize()
    {
        return serialize($this->toArray());
    }
    public function unserialize($serialized)
    {
        $this->loadData(unserialize($serialized));
    }
    protected function loadData($data)
    {
        foreach (static::$keyMap as $key => $property) {
            if (isset($data[$key])) {
                $this->{$property} = $data[$key];
            } else {
                throw new InvalidArgumentException("A required key [$key] was missing from the upload part.");
            }
        }
    }
}
