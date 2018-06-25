<?php
namespace Aws\Common\Credentials;
use Guzzle\Cache\CacheAdapterInterface;
class CacheableCredentials extends AbstractRefreshableCredentials
{
    protected $cache;
    protected $cacheKey;
    public function __construct(CredentialsInterface $credentials, CacheAdapterInterface $cache, $cacheKey)
    {
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
        parent::__construct($credentials);
    }
    protected function refresh()
    {
        if (!$cache = $this->cache->fetch($this->cacheKey)) {
            $this->credentials->getAccessKeyId();
            if (!$this->credentials->isExpired()) {
                $this->cache->save($this->cacheKey, $this->credentials, $this->credentials->getExpiration() - time());
            }
        } else {
            if (!$cache->isExpired()) {
                $this->credentials->setAccessKeyId($cache->getAccessKeyId());
                $this->credentials->setSecretKey($cache->getSecretKey());
                $this->credentials->setSecurityToken($cache->getSecurityToken());
                $this->credentials->setExpiration($cache->getExpiration());
            }
        }
    }
}
