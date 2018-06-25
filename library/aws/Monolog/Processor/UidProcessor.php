<?php
namespace Monolog\Processor;
class UidProcessor
{
    private $uid;
    public function __construct($length = 7)
    {
        if (!is_int($length) || $length > 32 || $length < 1) {
            throw new \InvalidArgumentException('The uid length must be an integer between 1 and 32');
        }
        $this->uid = substr(hash('md5', uniqid('', true)), 0, $length);
    }
    public function __invoke(array $record)
    {
        $record['extra']['uid'] = $this->uid;
        return $record;
    }
    public function getUid()
    {
        return $this->uid;
    }
}
