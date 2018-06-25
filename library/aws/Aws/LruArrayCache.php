<?php
namespace Aws;
class LruArrayCache implements CacheInterface, \Countable
{
    private $maxItems;
    private $items = array();
    public function __construct($maxItems = 1000)
    {
        $this->maxItems = $maxItems;
    }
    public function get($key)
    {
        if (!isset($this->items[$key])) {
            return null;
        }
        $entry = $this->items[$key];
        if (!$entry[1] || time() < $entry[1]) {
            unset($this->items[$key]);
            $this->items[$key] = $entry;
            return $entry[0];
        }
        unset($this->items[$key]);
        return null;
    }
    public function set($key, $value, $ttl = 0)
    {
        $ttl = $ttl ? time() + $ttl : 0;
        $this->items[$key] = [$value, $ttl];
        $diff = count($this->items) - $this->maxItems;
        if ($diff > 0) {
            reset($this->items);
            for ($i = 0; $i < $diff; $i++) {
                unset($this->items[key($this->items)]);
                next($this->items);
            }
        }
    }
    public function remove($key)
    {
        unset($this->items[$key]);
    }
    public function count()
    {
        return count($this->items);
    }
}
