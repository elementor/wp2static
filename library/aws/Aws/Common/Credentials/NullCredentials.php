<?php
namespace Aws\Common\Credentials;
class NullCredentials implements CredentialsInterface
{
    public function getAccessKeyId()
    {
        return '';
    }
    public function getSecretKey()
    {
        return '';
    }
    public function getSecurityToken()
    {
        return null;
    }
    public function getExpiration()
    {
        return null;
    }
    public function isExpired()
    {
        return false;
    }
    public function serialize()
    {
        return 'N;';
    }
    public function unserialize($serialized)
    {
    }
    public function setAccessKeyId($key)
    {
    }
    public function setSecretKey($secret)
    {
    }
    public function setSecurityToken($token)
    {
    }
    public function setExpiration($timestamp)
    {
    }
}
