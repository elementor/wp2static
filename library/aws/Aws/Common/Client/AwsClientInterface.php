<?php
namespace Aws\Common\Client;
use Aws\Common\Credentials\CredentialsInterface;
use Aws\Common\Signature\SignatureInterface;
use Aws\Common\Waiter\WaiterFactoryInterface;
use Aws\Common\Waiter\WaiterInterface;
use Guzzle\Service\ClientInterface;
interface AwsClientInterface extends ClientInterface
{
    public function getCredentials();
    public function setCredentials(CredentialsInterface $credentials);
    public function getSignature();
    public function getRegions();
    public function getRegion();
    public function setRegion($region);
    public function getWaiterFactory();
    public function setWaiterFactory(WaiterFactoryInterface $waiterFactory);
    public function waitUntil($waiter, array $input = array());
    public function getWaiter($waiter, array $input = array());
    public function getApiVersion();
}
