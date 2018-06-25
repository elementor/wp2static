<?php
namespace Aws\S3;
use Aws\Credentials\CredentialsInterface;
use GuzzleHttp\Psr7\Uri;
class PostObject
{
    private $client;
    private $bucket;
    private $formAttributes;
    private $formInputs;
    private $jsonPolicy;
    public function __construct(
        S3ClientInterface $client,
        $bucket,
        array $formInputs,
        $jsonPolicy
    ) {
        $this->client = $client;
        $this->bucket = $bucket;
        if (is_array($jsonPolicy)) {
            $jsonPolicy = json_encode($jsonPolicy);
        }
        $this->jsonPolicy = $jsonPolicy;
        $this->formAttributes = [
            'action'  => $this->generateUri(),
            'method'  => 'POST',
            'enctype' => 'multipart/form-data'
        ];
        $this->formInputs = $formInputs + ['key' => '${filename}'];
        $credentials = $client->getCredentials()->wait();
        $this->formInputs += $this->getPolicyAndSignature($credentials);
    }
    public function getClient()
    {
        return $this->client;
    }
    public function getBucket()
    {
        return $this->bucket;
    }
    public function getFormAttributes()
    {
        return $this->formAttributes;
    }
    public function setFormAttribute($attribute, $value)
    {
        $this->formAttributes[$attribute] = $value;
    }
    public function getFormInputs()
    {
        return $this->formInputs;
    }
    public function setFormInput($field, $value)
    {
        $this->formInputs[$field] = $value;
    }
    public function getJsonPolicy()
    {
        return $this->jsonPolicy;
    }
    private function generateUri()
    {
        $uri = new Uri($this->client->getEndpoint());
        if ($this->client->getConfig('use_path_style_endpoint') === true
            || ($uri->getScheme() === 'https'
            && strpos($this->bucket, '.') !== false)
        ) {
            $uri = $uri->withPath("/{$this->bucket}");
        } else {
            $uri = $uri->withHost($this->bucket . '.' . $uri->getHost());
        }
        return (string) $uri;
    }
    protected function getPolicyAndSignature(CredentialsInterface $creds)
    {
        $jsonPolicy64 = base64_encode($this->jsonPolicy);
        return [
            'AWSAccessKeyId' => $creds->getAccessKeyId(),
            'policy'    => $jsonPolicy64,
            'signature' => base64_encode(hash_hmac(
                'sha1',
                $jsonPolicy64,
                $creds->getSecretKey(),
                true
            ))
        ];
    }
}
