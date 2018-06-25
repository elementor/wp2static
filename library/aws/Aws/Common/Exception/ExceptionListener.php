<?php
namespace Aws\Common\Exception;
use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
class ExceptionListener implements EventSubscriberInterface
{
    protected $factory;
    public function __construct(ExceptionFactoryInterface $factory)
    {
        $this->factory = $factory;
    }
    public static function getSubscribedEvents()
    {
        return array('request.error' => array('onRequestError', -1));
    }
    public function onRequestError(Event $event)
    {
        $e = $this->factory->fromResponse($event['request'], $event['response']);
        $event->stopPropagation();
        throw $e;
    }
}
