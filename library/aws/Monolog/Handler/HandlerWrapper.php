<?php
namespace Monolog\Handler;
use Monolog\Formatter\FormatterInterface;
class HandlerWrapper implements HandlerInterface
{
    protected $handler;
    public function __construct(HandlerInterface $handler)
    {
        $this->handler = $handler;
    }
    public function isHandling(array $record)
    {
        return $this->handler->isHandling($record);
    }
    public function handle(array $record)
    {
        return $this->handler->handle($record);
    }
    public function handleBatch(array $records)
    {
        return $this->handler->handleBatch($records);
    }
    public function pushProcessor($callback)
    {
        $this->handler->pushProcessor($callback);
        return $this;
    }
    public function popProcessor()
    {
        return $this->handler->popProcessor();
    }
    public function setFormatter(FormatterInterface $formatter)
    {
        $this->handler->setFormatter($formatter);
        return $this;
    }
    public function getFormatter()
    {
        return $this->handler->getFormatter();
    }
}
