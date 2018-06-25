<?php
namespace Aws\Common\Waiter;
interface WaiterInterface
{
    const INTERVAL = 'waiter.interval';
    const MAX_ATTEMPTS = 'waiter.max_attempts';
    public function setMaxAttempts($maxAttempts);
    public function setInterval($interval);
    public function setConfig(array $config);
    public function wait();
}
