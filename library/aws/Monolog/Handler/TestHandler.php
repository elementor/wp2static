<?php
namespace Monolog\Handler;
class TestHandler extends AbstractProcessingHandler
{
    protected $records = array();
    protected $recordsByLevel = array();
    public function getRecords()
    {
        return $this->records;
    }
    public function clear()
    {
        $this->records = array();
        $this->recordsByLevel = array();
    }
    protected function hasRecordRecords($level)
    {
        return isset($this->recordsByLevel[$level]);
    }
    protected function hasRecord($record, $level)
    {
        if (is_array($record)) {
            $record = $record['message'];
        }
        return $this->hasRecordThatPasses(function ($rec) use ($record) {
            return $rec['message'] === $record;
        }, $level);
    }
    public function hasRecordThatContains($message, $level)
    {
        return $this->hasRecordThatPasses(function ($rec) use ($message) {
            return strpos($rec['message'], $message) !== false;
        }, $level);
    }
    public function hasRecordThatMatches($regex, $level)
    {
        return $this->hasRecordThatPasses(function ($rec) use ($regex) {
            return preg_match($regex, $rec['message']) > 0;
        }, $level);
    }
    public function hasRecordThatPasses($predicate, $level)
    {
        if (!is_callable($predicate)) {
            throw new \InvalidArgumentException("Expected a callable for hasRecordThatSucceeds");
        }
        if (!isset($this->recordsByLevel[$level])) {
            return false;
        }
        foreach ($this->recordsByLevel[$level] as $i => $rec) {
            if (call_user_func($predicate, $rec, $i)) {
                return true;
            }
        }
        return false;
    }
    protected function write(array $record)
    {
        $this->recordsByLevel[$record['level']][] = $record;
        $this->records[] = $record;
    }
    public function __call($method, $args)
    {
        if (preg_match('/(.*)(Debug|Info|Notice|Warning|Error|Critical|Alert|Emergency)(.*)/', $method, $matches) > 0) {
            $genericMethod = $matches[1] . 'Record' . $matches[3];
            $level = constant('Monolog\Logger::' . strtoupper($matches[2]));
            if (method_exists($this, $genericMethod)) {
                $args[] = $level;
                return call_user_func_array(array($this, $genericMethod), $args);
            }
        }
        throw new \BadMethodCallException('Call to undefined method ' . get_class($this) . '::' . $method . '()');
    }
}
