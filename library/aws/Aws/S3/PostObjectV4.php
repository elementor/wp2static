<?php
namespace Aws\S3;
use Aws\Credentials\CredentialsInterface;
use GuzzleHttp\Psr7\Uri;
use Aws\Signature\SignatureTrait;
use Aws\Signature\SignatureV4 as SignatureV4;
use Aws\Api\TimestampShape as TimestampShape;
class PostObjectV4
{
    use SignatureTrait;
    private $client;
    private $bucket;
    private $formAttributes;
    private $formInputs;
    public function __construct(
        S3ClientInterface $client,
        $bucket,
        array $formInputs,
        array $options = [],
        $expiration = '+1 hours'
    ) {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->formAttributes = [
            'action'  => $this->generateUri(),
            'method'  => 'POST',
            'enctype' => 'multipart/form-data'
        ];
        $credentials   = $this->client->getCredentials()->wait();
        if ($securityToken = $credentials->getSecurityToken()) {
            array_push($options, ['x-amz-security-token' => $securityToken]);
            $formInputs['X-Amz-Security-Token'] = $securityToken;
        }
        $policy = [
            'expiration' => TimestampShape::format($expiration, 'iso8601'),
            'conditions' => $options,
        ];
        $this->formInputs = $formInputs + ['key' => '${filename}'];
        $this->formInputs += $this->getPolicyAndSignature(
            $credentials,
            $policy
        );
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
    private function generateUri()
    {
        $uri = new Uri($this->client->getEndpoint());
        if ($this->client->getConfig('use_path_style_endpoint') === true
            || ($uri->getScheme() === 'https'
            && strpos($this->bucket, '.') !== false)
        ) {
            $uri = $uri->withPath("/{$this->bucket}");
        } else {
            if (strpos($uri->getHost(), $this->bucket . '.') !== 0) {
                $uri = $uri->withHost($this->bucket . '.' . $uri->getHost());
            }
        }
        return (string) $uri;
    }
    protected function getPolicyAndSignature(
        CredentialsInterface $credentials,
        array $policy
    ){
        $ldt = gmdate(SignatureV4::ISO8601_BASIC);
        $sdt = substr($ldt, 0, 8);
        $policy['conditions'][] = ['X-Amz-Date' => $ldt];
        $region = $this->client->getRegion();
        $scope = $this->createScope($sdt, $region, 's3');
        $creds = "{$credentials->getAccessKeyId()}/$scope";
        $policy['conditions'][] = ['X-Amz-Credential' => $creds];
        $policy['conditions'][] = ['X-Amz-Algorithm' => "AWS4-HMAC-SHA256"];
        $jsonPolicy64 = base64_encode(json_encode($policy));
        $key = $this->getSigningKey(
            $sdt,
            $region,
            's3',
            $credentials->getSecretKey()
        );
        return [
            'X-Amz-Credential' => $creds,
            'X-Amz-Algorithm' => "AWS4-HMAC-SHA256",
            'X-Amz-Date' => $ldt,
            'Policy'           => $jsonPolicy64,
            'X-Amz-Signature'  => bin2hex(
                hash_hmac('sha256', $jsonPolicy64, $key, true)
            ),
        ];
    }
}
