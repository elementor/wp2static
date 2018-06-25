<?php
namespace Aws\Common\Client;
use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
class UserAgentListener implements EventSubscriberInterface
{
    const OPTION = 'ua.append';
    public static function getSubscribedEvents()
    {
        return array('command.before_send' => 'onBeforeSend');
    }
    public function onBeforeSend(Event $event)
    {
        $command = $event['command'];
        if ($userAgentAppends = $command->get(self::OPTION)) {
            $request = $command->getRequest();
            $userAgent = (string) $request->getHeader('User-Agent');
            foreach ((array) $userAgentAppends as $append) {
                $append = ' ' . $append;
                if (strpos($userAgent, $append) === false) {
                    $userAgent .= $append;
                }
            }
            $request->setHeader('User-Agent', $userAgent);
        }
    }
}
