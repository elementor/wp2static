<?php
namespace Monolog\Processor;
class PsrLogMessageProcessor
{
    public function __invoke(array $record)
    {
        if (false === strpos($record['message'], '{')) {
            return $record;
        }
        $replacements = array();
        foreach ($record['context'] as $key => $val) {
            if (is_null($val) || is_scalar($val) || (is_object($val) && method_exists($val, "__toString"))) {
                $replacements['{'.$key.'}'] = $val;
            } elseif (is_object($val)) {
                $replacements['{'.$key.'}'] = '[object '.get_class($val).']';
            } else {
                $replacements['{'.$key.'}'] = '['.gettype($val).']';
            }
        }
        $record['message'] = strtr($record['message'], $replacements);
        return $record;
    }
}
