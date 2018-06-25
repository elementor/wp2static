<?php
namespace Aws\Credentials;
interface CredentialsInterface
{
    public function getAccessKeyId();
    public function getSecretKey();
    public function getSecurityToken();
    public function getExpiration();
    public function isExpired();
    public function toArray();
}
