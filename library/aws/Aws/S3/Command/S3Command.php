<?php
namespace Aws\S3\Command;
use Guzzle\Service\Command\OperationCommand;
use Guzzle\Service\Resource\Model;
use Guzzle\Common\Event;
class S3Command extends OperationCommand
{
    public function createPresignedUrl($expires)
    {
        return $this->client->createPresignedUrl($this->prepare(), $expires);
    }
    protected function process()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        if ($response->getStatusCode() == 301) {
            $this->getClient()->getEventDispatcher()->dispatch('request.error', new Event(array(
                'request'  => $this->getRequest(),
                'response' => $response
            )));
        }
        parent::process();
        if ($this->result instanceof Model && $this->getName() == 'PutObject') {
            $this->result->set('ObjectURL', $request->getUrl());
        }
    }
}
