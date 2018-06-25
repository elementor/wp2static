<?php
namespace Aws;
trait HasDataTrait
{
    private $data = [];
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
    public function & offsetGet($offset)
    {
        if (isset($this->data[$offset])) {
            return $this->data[$offset];
        }
        $value = null;
        return $value;
    }
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
    public function toArray()
    {
        return $this->data;
    }
    public function count()
    {
        return count($this->data);
    }
}
