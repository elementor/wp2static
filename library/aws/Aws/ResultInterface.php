<?php
namespace Aws;
interface ResultInterface extends \ArrayAccess, \IteratorAggregate, \Countable
{
    public function __toString();
    public function toArray();
    public function hasKey($name);
    public function get($key);
    public function search($expression);
};
