<?php
namespace Aws\Common\InstanceMetadata\Waiter;
use Aws\Common\Waiter\AbstractResourceWaiter;
use Guzzle\Http\Exception\CurlException;
class ServiceAvailable extends AbstractResourceWaiter
{
    protected $interval = 5;
    protected $maxAttempts = 4;
    public function doWait()
    {
        $request = $this->client->get();
        try {
            $request->getCurlOptions()->set(CURLOPT_CONNECTTIMEOUT, 10)
                ->set(CURLOPT_TIMEOUT, 10);
            $request->send();
            return true;
        } catch (CurlException $e) {
            return false;
        }
    }
}
