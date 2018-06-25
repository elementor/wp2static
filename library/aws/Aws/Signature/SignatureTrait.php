<?php
namespace Aws\Signature;
trait SignatureTrait
{
    private $cache = [];
    private $cacheSize = 0;
    private function createScope($shortDate, $region, $service)
    {
        return "$shortDate/$region/$service/aws4_request";
    }
    private function getSigningKey($shortDate, $region, $service, $secretKey)
    {
        $k = $shortDate . '_' . $region . '_' . $service . '_' . $secretKey;
        if (!isset($this->cache[$k])) {
            if (++$this->cacheSize > 50) {
                $this->cache = [];
                $this->cacheSize = 0;
            }
            $dateKey = hash_hmac(
                'sha256',
                $shortDate,
                "AWS4{$secretKey}",
                true
            );
            $regionKey = hash_hmac('sha256', $region, $dateKey, true);
            $serviceKey = hash_hmac('sha256', $service, $regionKey, true);
            $this->cache[$k] = hash_hmac(
                'sha256',
                'aws4_request',
                $serviceKey,
                true
            );
        }
        return $this->cache[$k];
    }
}
