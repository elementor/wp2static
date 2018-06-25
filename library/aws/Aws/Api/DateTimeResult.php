<?php
namespace Aws\Api;
class DateTimeResult extends \DateTime implements \JsonSerializable
{
    public static function fromEpoch($unixTimestamp)
    {
        return new self(gmdate('c', $unixTimestamp));
    }
    public function __toString()
    {
        return $this->format('c');
    }
    public function jsonSerialize()
    {
        return (string) $this;
    }
}
