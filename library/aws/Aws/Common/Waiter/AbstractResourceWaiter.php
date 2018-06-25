<?php
namespace Aws\Common\Waiter;
use Aws\Common\Client\AwsClientInterface;
use Aws\Common\Exception\RuntimeException;
abstract class AbstractResourceWaiter extends AbstractWaiter implements ResourceWaiterInterface
{
    protected $client;
    public function setClient(AwsClientInterface $client)
    {
        $this->client = $client;
        return $this;
    }
    public function wait()
    {
        if (!$this->client) {
            throw new RuntimeException('No client has been specified on the waiter');
        }
        parent::wait();
    }
}
