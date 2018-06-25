<?php
namespace Aws\Common\Waiter;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Exception\RuntimeException;
class CallableWaiter extends AbstractWaiter
{
    protected $callable;
    protected $context = array();
    public function setCallable($callable)
    {
        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Value is not callable');
        }
        $this->callable = $callable;
        return $this;
    }
    public function setContext(array $context)
    {
        $this->context = $context;
        return $this;
    }
    public function doWait()
    {
        if (!$this->callable) {
            throw new RuntimeException('No callable was specified for the wait method');
        }
        return call_user_func($this->callable, $this->attempts, $this->context);
    }
}
