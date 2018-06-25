<?php
namespace Aws\S3;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\S3\Model\Acp;
use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
class AcpListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array('command.before_prepare' => array('onCommandBeforePrepare', -255));
    }
    public function onCommandBeforePrepare(Event $event)
    {
        $command = $event['command'];
        $operation = $command->getOperation();
        if ($operation->hasParam('ACP') && $command->hasKey('ACP')) {
            if ($acp = $command->get('ACP')) {
                if (!($acp instanceof Acp)) {
                    throw new InvalidArgumentException('ACP must be an instance of Aws\S3\Model\Acp');
                }
                if ($command->hasKey('Grants')) {
                    throw new InvalidArgumentException(
                        'Use either the ACP parameter or the Grants parameter. Do not use both.'
                    );
                }
                if ($operation->hasParam('Grants')) {
                    $command->overwriteWith($acp->toArray());
                } else {
                    $acp->updateCommand($command);
                }
            }
            $command->remove('ACP');
        }
    }
}
