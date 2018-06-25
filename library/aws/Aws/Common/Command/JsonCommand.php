<?php
namespace Aws\Common\Command;
use Guzzle\Service\Command\OperationCommand;
use Guzzle\Http\Curl\CurlHandle;
class JsonCommand extends OperationCommand
{
    protected function build()
    {
        parent::build();
        if (!$this->request->getBody()) {
            $this->request->setBody('{}');
        }
        $this->request->removeHeader('Expect');
        $this->request->getCurlOptions()->set(CurlHandle::BODY_AS_STRING, true);
    }
}
