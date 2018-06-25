<?php
namespace Aws\Common\Client;
use Aws\Common\Exception\InvalidArgumentException;
use Guzzle\Common\Event;
use Guzzle\Http\EntityBody;
use Guzzle\Service\Command\AbstractCommand as Command;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
class UploadBodyListener implements EventSubscriberInterface
{
    protected $commands;
    protected $bodyParameter;
    protected $sourceParameter;
    public function __construct(array $commands, $bodyParameter = 'Body', $sourceParameter = 'SourceFile')
    {
        $this->commands = $commands;
        $this->bodyParameter = (string) $bodyParameter;
        $this->sourceParameter = (string) $sourceParameter;
    }
    public static function getSubscribedEvents()
    {
        return array('command.before_prepare' => array('onCommandBeforePrepare'));
    }
    public function onCommandBeforePrepare(Event $event)
    {
        $command = $event['command'];
        if (in_array($command->getName(), $this->commands)) {
            $source = $command->get($this->sourceParameter);
            $body = $command->get($this->bodyParameter);
            if (is_string($source) && file_exists($source)) {
                $body = fopen($source, 'r');
            }
            if (null !== $body) {
                $command->remove($this->sourceParameter);
                $command->set($this->bodyParameter, EntityBody::factory($body));
            } else {
                throw new InvalidArgumentException("You must specify a non-null value for the {$this->bodyParameter} or {$this->sourceParameter} parameters.");
            }
        }
    }
}
