<?php
namespace Aws;
interface CacheInterface
{
    public function get($key);
    public function set($key, $value, $ttl = 0);
    public function remove($key);
}
