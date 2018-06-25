<?php
namespace Symfony\Component\EventDispatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
class ContainerAwareEventDispatcher extends EventDispatcher
{
    private $container;
    private $listenerIds = array();
    private $listeners = array();
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    public function addListenerService($eventName, $callback, $priority = 0)
    {
        if (!is_array($callback) || 2 !== count($callback)) {
            throw new \InvalidArgumentException('Expected an array("service", "method") argument');
        }
        $this->listenerIds[$eventName][] = array($callback[0], $callback[1], $priority);
    }
    public function removeListener($eventName, $listener)
    {
        $this->lazyLoad($eventName);
        if (isset($this->listenerIds[$eventName])) {
            foreach ($this->listenerIds[$eventName] as $i => $args) {
                list($serviceId, $method, $priority) = $args;
                $key = $serviceId.'.'.$method;
                if (isset($this->listeners[$eventName][$key]) && $listener === array($this->listeners[$eventName][$key], $method)) {
                    unset($this->listeners[$eventName][$key]);
                    if (empty($this->listeners[$eventName])) {
                        unset($this->listeners[$eventName]);
                    }
                    unset($this->listenerIds[$eventName][$i]);
                    if (empty($this->listenerIds[$eventName])) {
                        unset($this->listenerIds[$eventName]);
                    }
                }
            }
        }
        parent::removeListener($eventName, $listener);
    }
    public function hasListeners($eventName = null)
    {
        if (null === $eventName) {
            return (bool) count($this->listenerIds) || (bool) count($this->listeners);
        }
        if (isset($this->listenerIds[$eventName])) {
            return true;
        }
        return parent::hasListeners($eventName);
    }
    public function getListeners($eventName = null)
    {
        if (null === $eventName) {
            foreach ($this->listenerIds as $serviceEventName => $args) {
                $this->lazyLoad($serviceEventName);
            }
        } else {
            $this->lazyLoad($eventName);
        }
        return parent::getListeners($eventName);
    }
    public function getListenerPriority($eventName, $listener)
    {
        $this->lazyLoad($eventName);
        return parent::getListenerPriority($eventName, $listener);
    }
    public function addSubscriberService($serviceId, $class)
    {
        foreach ($class::getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->listenerIds[$eventName][] = array($serviceId, $params, 0);
            } elseif (is_string($params[0])) {
                $this->listenerIds[$eventName][] = array($serviceId, $params[0], isset($params[1]) ? $params[1] : 0);
            } else {
                foreach ($params as $listener) {
                    $this->listenerIds[$eventName][] = array($serviceId, $listener[0], isset($listener[1]) ? $listener[1] : 0);
                }
            }
        }
    }
    public function getContainer()
    {
        return $this->container;
    }
    protected function lazyLoad($eventName)
    {
        if (isset($this->listenerIds[$eventName])) {
            foreach ($this->listenerIds[$eventName] as $args) {
                list($serviceId, $method, $priority) = $args;
                $listener = $this->container->get($serviceId);
                $key = $serviceId.'.'.$method;
                if (!isset($this->listeners[$eventName][$key])) {
                    $this->addListener($eventName, array($listener, $method), $priority);
                } elseif ($listener !== $this->listeners[$eventName][$key]) {
                    parent::removeListener($eventName, array($this->listeners[$eventName][$key], $method));
                    $this->addListener($eventName, array($listener, $method), $priority);
                }
                $this->listeners[$eventName][$key] = $listener;
            }
        }
    }
}
