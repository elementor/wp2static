<?php
namespace Aws\Common\Signature;
use Aws\Common\Credentials\AbstractRefreshableCredentials;
use Aws\Common\Credentials\CredentialsInterface;
use Aws\Common\Credentials\NullCredentials;
use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
class SignatureListener implements EventSubscriberInterface
{
    protected $credentials;
    protected $signature;
    public function __construct(CredentialsInterface $credentials, SignatureInterface $signature)
    {
        $this->credentials = $credentials;
        $this->signature = $signature;
    }
    public static function getSubscribedEvents()
    {
        return array(
            'request.before_send'        => array('onRequestBeforeSend', -255),
            'client.credentials_changed' => array('onCredentialsChanged')
        );
    }
    public function onCredentialsChanged(Event $event)
    {
        $this->credentials = $event['credentials'];
    }
    public function onRequestBeforeSend(Event $event)
    {
        $creds = $this->credentials instanceof AbstractRefreshableCredentials
            ? $this->credentials->getCredentials()
            : $this->credentials;
        if(!$creds instanceof NullCredentials) {
            $this->signature->signRequest($event['request'], $creds);
        }
    }
}
