<?php
namespace Aws\Common\Credentials;
class AbstractCredentialsDecorator implements CredentialsInterface
{
    protected $credentials;
    public function __construct(CredentialsInterface $credentials)
    {
        $this->credentials = $credentials;
    }
    public function serialize()
    {
        return $this->credentials->serialize();
    }
    public function unserialize($serialized)
    {
        $this->credentials = new Credentials('', '');
        $this->credentials->unserialize($serialized);
    }
    public function getAccessKeyId()
    {
        return $this->credentials->getAccessKeyId();
    }
    public function getSecretKey()
    {
        return $this->credentials->getSecretKey();
    }
    public function getSecurityToken()
    {
        return $this->credentials->getSecurityToken();
    }
    public function getExpiration()
    {
        return $this->credentials->getExpiration();
    }
    public function isExpired()
    {
        return $this->credentials->isExpired();
    }
    public function setAccessKeyId($key)
    {
        $this->credentials->setAccessKeyId($key);
        return $this;
    }
    public function setSecretKey($secret)
    {
        $this->credentials->setSecretKey($secret);
        return $this;
    }
    public function setSecurityToken($token)
    {
        $this->credentials->setSecurityToken($token);
        return $this;
    }
    public function setExpiration($timestamp)
    {
        $this->credentials->setExpiration($timestamp);
        return $this;
    }
}
