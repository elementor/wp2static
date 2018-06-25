<?php
namespace Aws\Common\Credentials;
abstract class AbstractRefreshableCredentials extends AbstractCredentialsDecorator
{
    public function getCredentials()
    {
        if ($this->credentials->isExpired()) {
            $this->refresh();
        }
        return new Credentials(
            $this->credentials->getAccessKeyId(),
            $this->credentials->getSecretKey(),
            $this->credentials->getSecurityToken(),
            $this->credentials->getExpiration()
        );
    }
    public function getAccessKeyId()
    {
        if ($this->credentials->isExpired()) {
            $this->refresh();
        }
        return $this->credentials->getAccessKeyId();
    }
    public function getSecretKey()
    {
        if ($this->credentials->isExpired()) {
            $this->refresh();
        }
        return $this->credentials->getSecretKey();
    }
    public function getSecurityToken()
    {
        if ($this->credentials->isExpired()) {
            $this->refresh();
        }
        return $this->credentials->getSecurityToken();
    }
    public function serialize()
    {
        if ($this->credentials->isExpired()) {
            $this->refresh();
        }
        return $this->credentials->serialize();
    }
    abstract protected function refresh();
}
