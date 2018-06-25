<?php
namespace Aws\Common\Waiter;
use Aws\Common\Client\AwsClientInterface;
interface ResourceWaiterInterface extends WaiterInterface
{
    public function setClient(AwsClientInterface $client);
}
