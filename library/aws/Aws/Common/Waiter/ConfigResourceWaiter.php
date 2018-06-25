<?php
namespace Aws\Common\Waiter;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Exception\RuntimeException;
use Aws\Common\Exception\ServiceResponseException;
use Guzzle\Service\Resource\Model;
use Guzzle\Service\Exception\ValidationException;
class ConfigResourceWaiter extends AbstractResourceWaiter
{
    protected $waiterConfig;
    public function __construct(WaiterConfig $waiterConfig)
    {
        $this->waiterConfig = $waiterConfig;
        $this->setInterval($waiterConfig->get(WaiterConfig::INTERVAL));
        $this->setMaxAttempts($waiterConfig->get(WaiterConfig::MAX_ATTEMPTS));
    }
    public function setConfig(array $config)
    {
        foreach ($config as $key => $value) {
            if (substr($key, 0, 7) == 'waiter.') {
                $this->waiterConfig->set(substr($key, 7), $value);
            }
        }
        if (!isset($config[self::INTERVAL])) {
            $config[self::INTERVAL] = $this->waiterConfig->get(WaiterConfig::INTERVAL);
        }
        if (!isset($config[self::MAX_ATTEMPTS])) {
            $config[self::MAX_ATTEMPTS] = $this->waiterConfig->get(WaiterConfig::MAX_ATTEMPTS);
        }
        return parent::setConfig($config);
    }
    public function getWaiterConfig()
    {
        return $this->waiterConfig;
    }
    protected function doWait()
    {
        $params = $this->config;
        foreach (array_keys($params) as $key) {
            if (substr($key, 0, 7) == 'waiter.') {
                unset($params[$key]);
            }
        }
        $operation = $this->client->getCommand($this->waiterConfig->get(WaiterConfig::OPERATION), $params);
        try {
            return $this->checkResult($this->client->execute($operation));
        } catch (ValidationException $e) {
            throw new InvalidArgumentException(
                $this->waiterConfig->get(WaiterConfig::WAITER_NAME) . ' waiter validation failed:  ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        } catch (ServiceResponseException $e) {
            $transition = $this->checkErrorAcceptor($e);
            if (null !== $transition) {
                return $transition;
            }
            foreach ((array) $this->waiterConfig->get(WaiterConfig::IGNORE_ERRORS) as $ignore) {
                if ($e->getExceptionCode() == $ignore) {
                    return false;
                }
            }
            throw $e;
        }
    }
    protected function checkErrorAcceptor(ServiceResponseException $e)
    {
        if ($this->waiterConfig->get(WaiterConfig::SUCCESS_TYPE) == 'error') {
            if ($e->getExceptionCode() == $this->waiterConfig->get(WaiterConfig::SUCCESS_VALUE)) {
                return true;
            }
        }
        return null;
    }
    protected function checkResult(Model $result)
    {
        if ($this->waiterConfig->get(WaiterConfig::SUCCESS_TYPE) == 'output' &&
            $this->checkPath(
                $result,
                $this->waiterConfig->get(WaiterConfig::SUCCESS_PATH),
                $this->waiterConfig->get(WaiterConfig::SUCCESS_VALUE)
            )
        ) {
            return true;
        }
        if ($this->waiterConfig->get(WaiterConfig::FAILURE_TYPE) == 'output') {
            $failureValue = $this->waiterConfig->get(WaiterConfig::FAILURE_VALUE);
            if ($failureValue) {
                $key = $this->waiterConfig->get(WaiterConfig::FAILURE_PATH);
                if ($this->checkPath($result, $key, $failureValue, false)) {
                    $triggered = array_intersect(
                        (array) $this->waiterConfig->get(WaiterConfig::FAILURE_VALUE),
                        array_unique((array) $result->getPath($key))
                    );
                    throw new RuntimeException(
                        'A resource entered into an invalid state of "'
                        . implode(', ', $triggered) . '" while waiting with the "'
                        . $this->waiterConfig->get(WaiterConfig::WAITER_NAME) . '" waiter.'
                    );
                }
            }
        }
        return false;
    }
    protected function checkPath(Model $model, $key = null, $checkValue = array(), $all = true)
    {
        if (!$key) {
            return true;
        }
        if (!($result = $model->getPath($key))) {
            return false;
        }
        $total = $matches = 0;
        foreach ((array) $result as $value) {
            $total++;
            foreach ((array) $checkValue as $check) {
                if ($value == $check) {
                    $matches++;
                    break;
                }
            }
        }
        if ($all && $total != $matches) {
            return false;
        }
        return $matches > 0;
    }
}
