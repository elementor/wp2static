<?php
namespace Aws\Common\Credentials;
use Aws\Common\InstanceMetadata\InstanceMetadataClient;
use Aws\Common\Exception\InstanceProfileCredentialsException;
class RefreshableInstanceProfileCredentials extends AbstractRefreshableCredentials
{
    protected $client;
    private $customClient;
    public function __construct(CredentialsInterface $credentials, InstanceMetadataClient $client = null)
    {
        parent::__construct($credentials);
        $this->setClient($client);
    }
    public function setClient(InstanceMetadataClient $client = null)
    {
        $this->customClient = null !== $client;
        $this->client = $client ?: InstanceMetadataClient::factory();
    }
    public function serialize()
    {
        $serializable = array(
            'credentials' => parent::serialize(),
            'customClient' => $this->customClient,
        );
        if ($this->customClient) {
            $serializable['client'] = serialize($this->client);
        }
        return json_encode($serializable);
    }
    public function unserialize($value)
    {
        $serialized = json_decode($value, true);
        parent::unserialize($serialized['credentials']);
        $this->customClient = $serialized['customClient'];
        $this->client = $this->customClient ?
            unserialize($serialized['client'])
            : InstanceMetadataClient::factory();
    }
    protected function refresh()
    {
        $credentials = $this->client->getInstanceProfileCredentials();
        $this->credentials->setAccessKeyId($credentials->getAccessKeyId())
            ->setSecretKey($credentials->getSecretKey())
            ->setSecurityToken($credentials->getSecurityToken())
            ->setExpiration($credentials->getExpiration() - 300);
    }
}
