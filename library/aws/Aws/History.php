<?php
namespace Aws;
use Psr\Http\Message\RequestInterface;
use Aws\Exception\AwsException;
class History implements \Countable, \IteratorAggregate
{
    private $maxEntries;
    private $entries = array();
    public function __construct($maxEntries = 10)
    {
        $this->maxEntries = $maxEntries;
    }
    public function count()
    {
        return count($this->entries);
    }
    public function getIterator()
    {
        return new \ArrayIterator(array_values($this->entries));
    }
    public function getLastCommand()
    {
        if (!$this->entries) {
            throw new \LogicException('No commands received');
        }
        return end($this->entries)['command'];
    }
    public function getLastRequest()
    {
        if (!$this->entries) {
            throw new \LogicException('No requests received');
        }
        return end($this->entries)['request'];
    }
    public function getLastReturn()
    {
        if (!$this->entries) {
            throw new \LogicException('No entries');
        }
        $last = end($this->entries);
        if (isset($last['result'])) {
            return $last['result'];
        }
        if (isset($last['exception'])) {
            return $last['exception'];
        }
        throw new \LogicException('No return value for last entry.');
    }
    public function start(CommandInterface $cmd, RequestInterface $req)
    {
        $ticket = uniqid();
        $this->entries[$ticket] = [
            'command'   => $cmd,
            'request'   => $req,
            'result'    => null,
            'exception' => null,
        ];
        return $ticket;
    }
    public function finish($ticket, $result)
    {
        if (!isset($this->entries[$ticket])) {
            throw new \InvalidArgumentException('Invalid history ticket');
        }
        if (isset($this->entries[$ticket]['result'])
            || isset($this->entries[$ticket]['exception'])
        ) {
            throw new \LogicException('History entry is already finished');
        }
        if ($result instanceof \Exception) {
            $this->entries[$ticket]['exception'] = $result;
        } else {
            $this->entries[$ticket]['result'] = $result;
        }
        if (count($this->entries) >= $this->maxEntries) {
            $this->entries = array_slice($this->entries, -$this->maxEntries, null, true);
        }
    }
    public function clear()
    {
        $this->entries = [];
    }
    public function toArray()
    {
        return array_values($this->entries);
    }
}
