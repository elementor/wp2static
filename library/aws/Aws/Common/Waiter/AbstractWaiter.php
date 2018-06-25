<?php
namespace Aws\Common\Waiter;
use Aws\Common\Exception\RuntimeException;
use Guzzle\Common\AbstractHasDispatcher;
abstract class AbstractWaiter extends AbstractHasDispatcher implements WaiterInterface
{
    protected $attempts = 0;
    protected $config = array();
    public static function getAllEvents()
    {
        return array(
            'waiter.before_attempt',
            'waiter.before_wait',
        );
    }
    public function getMaxAttempts()
    {
        return isset($this->config[self::MAX_ATTEMPTS]) ? $this->config[self::MAX_ATTEMPTS] : 10;
    }
    public function getInterval()
    {
        return isset($this->config[self::INTERVAL]) ? $this->config[self::INTERVAL] : 0;
    }
    public function setMaxAttempts($maxAttempts)
    {
        $this->config[self::MAX_ATTEMPTS] = $maxAttempts;
        return $this;
    }
    public function setInterval($interval)
    {
        $this->config[self::INTERVAL] = $interval;
        return $this;
    }
    public function setConfig(array $config)
    {
        if (isset($config['waiter.before_attempt'])) {
            $this->getEventDispatcher()->addListener('waiter.before_attempt', $config['waiter.before_attempt']);
            unset($config['waiter.before_attempt']);
        }
        if (isset($config['waiter.before_wait'])) {
            $this->getEventDispatcher()->addListener('waiter.before_wait', $config['waiter.before_wait']);
            unset($config['waiter.before_wait']);
        }
        $this->config = $config;
        return $this;
    }
    public function wait()
    {
        $this->attempts = 0;
        do {
            $this->dispatch('waiter.before_attempt', array(
                'waiter' => $this,
                'config' => $this->config,
            ));
            if ($this->doWait()) {
                break;
            }
            if (++$this->attempts >= $this->getMaxAttempts()) {
                throw new RuntimeException('Wait method never resolved to true after ' . $this->attempts . ' attempts');
            }
            $this->dispatch('waiter.before_wait', array(
                'waiter' => $this,
                'config' => $this->config,
            ));
            if ($this->getInterval()) {
                usleep($this->getInterval() * 1000000);
            }
        } while (1);
    }
    abstract protected function doWait();
}
