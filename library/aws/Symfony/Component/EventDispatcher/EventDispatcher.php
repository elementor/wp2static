<?php
namespace Symfony\Component\EventDispatcher;
class EventDispatcher implements EventDispatcherInterface
{
    private $listeners = array();
    private $sorted = array();
    public function dispatch($eventName, Event $event = null)
    {
        if (null === $event) {
            $event = new Event();
        }
        $event->setDispatcher($this);
        $event->setName($eventName);
        if ($listeners = $this->getListeners($eventName)) {
            $this->doDispatch($listeners, $eventName, $event);
        }
        return $event;
    }
    public function getListeners($eventName = null)
    {
        if (null !== $eventName) {
            if (!isset($this->listeners[$eventName])) {
                return array();
            }
            if (!isset($this->sorted[$eventName])) {
                $this->sortListeners($eventName);
            }
            return $this->sorted[$eventName];
        }
        foreach ($this->listeners as $eventName => $eventListeners) {
            if (!isset($this->sorted[$eventName])) {
                $this->sortListeners($eventName);
            }
        }
        return array_filter($this->sorted);
    }
    public function getListenerPriority($eventName, $listener)
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }
        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            if (false !== ($key = array_search($listener, $listeners, true))) {
                return $priority;
            }
        }
    }
    public function hasListeners($eventName = null)
    {
        return (bool) count($this->getListeners($eventName));
    }
    public function addListener($eventName, $listener, $priority = 0)
    {
        $this->listeners[$eventName][$priority][] = $listener;
        unset($this->sorted[$eventName]);
    }
    public function removeListener($eventName, $listener)
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }
        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            if (false !== ($key = array_search($listener, $listeners, true))) {
                unset($this->listeners[$eventName][$priority][$key], $this->sorted[$eventName]);
            }
        }
    }
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->addListener($eventName, array($subscriber, $params));
            } elseif (is_string($params[0])) {
                $this->addListener($eventName, array($subscriber, $params[0]), isset($params[1]) ? $params[1] : 0);
            } else {
                foreach ($params as $listener) {
                    $this->addListener($eventName, array($subscriber, $listener[0]), isset($listener[1]) ? $listener[1] : 0);
                }
            }
        }
    }
    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (is_array($params) && is_array($params[0])) {
                foreach ($params as $listener) {
                    $this->removeListener($eventName, array($subscriber, $listener[0]));
                }
            } else {
                $this->removeListener($eventName, array($subscriber, is_string($params) ? $params : $params[0]));
            }
        }
    }
    protected function doDispatch($listeners, $eventName, Event $event)
    {
        foreach ($listeners as $listener) {
            if ($event->isPropagationStopped()) {
                break;
            }
            call_user_func($listener, $event, $eventName, $this);
        }
    }
    private function sortListeners($eventName)
    {
        krsort($this->listeners[$eventName]);
        $this->sorted[$eventName] = call_user_func_array('array_merge', $this->listeners[$eventName]);
    }
}
