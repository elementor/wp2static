<?php
namespace Monolog\Handler;
use RollbarNotifier;
use Exception;
use Monolog\Logger;
class RollbarHandler extends AbstractProcessingHandler
{
    protected $rollbarNotifier;
    private $hasRecords = false;
    public function __construct(RollbarNotifier $rollbarNotifier, $level = Logger::ERROR, $bubble = true)
    {
        $this->rollbarNotifier = $rollbarNotifier;
        parent::__construct($level, $bubble);
    }
    protected function write(array $record)
    {
        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof Exception) {
            $context = $record['context'];
            $exception = $context['exception'];
            unset($context['exception']);
            $payload = array();
            if (isset($context['payload'])) {
                $payload = $context['payload'];
                unset($context['payload']);
            }
            $this->rollbarNotifier->report_exception($exception, $context, $payload);
        } else {
            $extraData = array(
                'level' => $record['level'],
                'channel' => $record['channel'],
                'datetime' => $record['datetime']->format('U'),
            );
            $context = $record['context'];
            $payload = array();
            if (isset($context['payload'])) {
                $payload = $context['payload'];
                unset($context['payload']);
            }
            $this->rollbarNotifier->report_message(
                $record['message'],
                $record['level_name'],
                array_merge($record['context'], $record['extra'], $extraData),
                $payload
            );
        }
        $this->hasRecords = true;
    }
    public function close()
    {
        if ($this->hasRecords) {
            $this->rollbarNotifier->flush();
            $this->hasRecords = false;
        }
    }
}
