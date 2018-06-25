<?php
namespace Aws\S3;
use Aws\Common\Exception\RuntimeException;
use Guzzle\Common\Event;
use Guzzle\Service\Command\CommandInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
class SseCpkListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array('command.before_prepare' => 'onCommandBeforePrepare');
    }
    public function onCommandBeforePrepare(Event $event)
    {
        $command = $event['command'];
        if ($command['SSECustomerKey'] ||
            $command['CopySourceSSECustomerKey']
        ) {
            $this->validateScheme($command);
        }
        if ($command['SSECustomerKey']) {
            $this->prepareSseParams($command);
        }
        if ($command['CopySourceSSECustomerKey']) {
            $this->prepareSseParams($command, true);
        }
    }
    private function validateScheme(CommandInterface $command)
    {
        if ($command->getClient()->getConfig('scheme') !== 'https') {
            throw new RuntimeException('You must configure your S3 client to '
                . 'use HTTPS in order to use the SSE-C features.');
        }
    }
    private function prepareSseParams(
        CommandInterface $command,
        $isCopy = false
    ) {
        $prefix = $isCopy ? 'CopySource' : '';
        $key = $command[$prefix . 'SSECustomerKey'];
        $command[$prefix . 'SSECustomerKey'] = base64_encode($key);
        if ($md5 = $command[$prefix . 'SSECustomerKeyMD5']) {
            $command[$prefix . 'SSECustomerKeyMD5'] = base64_encode($md5);
        } else {
            $command[$prefix . 'SSECustomerKeyMD5'] = base64_encode(md5($key, true));
        }
    }
}
