<?php
namespace Aws\Common\Credentials;
interface CredentialsInterface extends \Serializable
{
    public function getAccessKeyId();
    public function getSecretKey();
    public function getSecurityToken();
    public function getExpiration();
    public function setAccessKeyId($key);
    public function setSecretKey($secret);
    public function setSecurityToken($token);
    public function setExpiration($timestamp);
    public function isExpired();
}
