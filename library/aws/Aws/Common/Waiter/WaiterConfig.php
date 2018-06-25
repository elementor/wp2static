<?php
namespace Aws\Common\Waiter;
use Guzzle\Common\Collection;
class WaiterConfig extends Collection
{
    const WAITER_NAME = 'name';
    const MAX_ATTEMPTS = 'max_attempts';
    const INTERVAL = 'interval';
    const OPERATION = 'operation';
    const IGNORE_ERRORS = 'ignore_errors';
    const DESCRIPTION = 'description';
    const SUCCESS_TYPE = 'success.type';
    const SUCCESS_PATH = 'success.path';
    const SUCCESS_VALUE = 'success.value';
    const FAILURE_TYPE = 'failure.type';
    const FAILURE_PATH = 'failure.path';
    const FAILURE_VALUE = 'failure.value';
    public function __construct(array $data = array())
    {
        $this->data = $data;
        $this->extractConfig();
    }
    protected function extractConfig()
    {
        foreach ($this->data as $key => $value) {
            if (substr($key, 0, 9) == 'acceptor.') {
                $name = substr($key, 9);
                if (!isset($this->data["success.{$name}"])) {
                    $this->data["success.{$name}"] = $value;
                }
                if (!isset($this->data["failure.{$name}"])) {
                    $this->data["failure.{$name}"] = $value;
                }
                unset($this->data[$key]);
            }
        }
    }
}
