<?php
namespace Aws\Credentials;
class Credentials implements CredentialsInterface, \Serializable
{
    private $key;
    private $secret;
    private $token;
    private $expires;
    public function __construct($key, $secret, $token = null, $expires = null)
    {
        $this->key = trim($key);
        $this->secret = trim($secret);
        $this->token = $token;
        $this->expires = $expires;
    }
    public static function __set_state(array $state)
    {
        return new self(
            $state['key'],
            $state['secret'],
            $state['token'],
            $state['expires']
        );
    }
    public function getAccessKeyId()
    {
        return $this->key;
    }
    public function getSecretKey()
    {
        return $this->secret;
    }
    public function getSecurityToken()
    {
        return $this->token;
    }
    public function getExpiration()
    {
        return $this->expires;
    }
    public function isExpired()
    {
        return $this->expires !== null && time() >= $this->expires;
    }
    public function toArray()
    {
        return [
            'key'     => $this->key,
            'secret'  => $this->secret,
            'token'   => $this->token,
            'expires' => $this->expires
        ];
    }
    public function serialize()
    {
        return json_encode($this->toArray());
    }
    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);
        $this->key = $data['key'];
        $this->secret = $data['secret'];
        $this->token = $data['token'];
        $this->expires = $data['expires'];
    }
}
