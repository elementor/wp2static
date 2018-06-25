<?php
namespace Aws\Common\Model\MultipartUpload;
use Aws\Common\Exception\InvalidArgumentException;
abstract class AbstractUploadId implements UploadIdInterface
{
    protected static $expectedValues = array();
    protected $data = array();
    public static function fromParams($data)
    {
        $uploadId = new static();
        $uploadId->loadData($data);
        return $uploadId;
    }
    public function toParams()
    {
        return $this->data;
    }
    public function serialize()
    {
        return serialize($this->data);
    }
    public function unserialize($serialized)
    {
        $this->loadData(unserialize($serialized));
    }
    protected function loadData($data)
    {
        $data = array_replace(static::$expectedValues, array_intersect_key($data, static::$expectedValues));
        foreach ($data as $key => $value) {
            if (isset($data[$key])) {
                $this->data[$key] = $data[$key];
            } else {
                throw new InvalidArgumentException("A required key [$key] was missing from the UploadId.");
            }
        }
    }
}
