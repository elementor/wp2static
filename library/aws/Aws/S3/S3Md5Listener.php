<?php
namespace Aws\S3;
use Aws\Common\Signature\SignatureV4;
use Aws\Common\Signature\SignatureInterface;
use Guzzle\Common\Event;
use Guzzle\Service\Command\CommandInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
class S3Md5Listener implements EventSubscriberInterface
{
    private $signature;
    public static function getSubscribedEvents()
    {
        return array('command.after_prepare' => 'onCommandAfterPrepare');
    }
    public function __construct(SignatureInterface $signature)
    {
        $this->signature = $signature;
    }
    public function onCommandAfterPrepare(Event $event)
    {
        $command = $event['command'];
        $operation = $command->getOperation();
        if ($operation->getData('contentMd5')) {
            $this->addMd5($command);
        } elseif ($operation->hasParam('ContentMD5')) {
            $value = $command['ContentMD5'];
            if ($value === true ||
                ($value === null && !($this->signature instanceof SignatureV4))
            ) {
                $this->addMd5($command);
            }
        }
    }
    private function addMd5(CommandInterface $command)
    {
        $request = $command->getRequest();
        $body = $request->getBody();
        if ($body && $body->getSize() > 0) {
            if (false !== ($md5 = $body->getContentMd5(true, true))) {
                $request->setHeader('Content-MD5', $md5);
            }
        }
    }
}
