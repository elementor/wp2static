<?php
namespace Aws\S3\Model;
use Aws\Common\Enum\DateFormat;
use Aws\S3\S3Client;
use Guzzle\Common\Collection;
use Guzzle\Http\Url;
class PostObject extends Collection
{
    protected $client;
    protected $bucket;
    protected $formAttributes;
    protected $formInputs;
    protected $jsonPolicy;
    public function __construct(S3Client $client, $bucket, array $options = array())
    {
        $this->setClient($client);
        $this->setBucket($bucket);
        parent::__construct($options);
    }
    public function prepareData()
    {
        $options = Collection::fromConfig($this->data, array(
            'ttd' => '+1 hour',
            'key' => '^${filename}',
        ));
        $ttd = $options['ttd'];
        $ttd = is_numeric($ttd) ? (int) $ttd : strtotime($ttd);
        unset($options['ttd']);
        $rawJsonPolicy = $options['policy'];
        $policyCallback = $options['policy_callback'];
        unset($options['policy'], $options['policy_callback']);
        $policy = array(
            'expiration' => gmdate(DateFormat::ISO8601_S3, $ttd),
            'conditions' => array(array('bucket' => $this->bucket))
        );
        $url = Url::factory($this->client->getBaseUrl());
        if ($url->getScheme() === 'https' && strpos($this->bucket, '.') !== false) {
            $url->setPath($this->bucket);
        } else {
            $url->setHost($this->bucket . '.' . $url->getHost());
        }
        $this->formAttributes = array(
            'action' => (string) $url,
            'method' => 'POST',
            'enctype' => 'multipart/form-data'
        );
        $this->formInputs = array(
            'AWSAccessKeyId' => $this->client->getCredentials()->getAccessKeyId()
        );
        $status = (int) $options->get('success_action_status');
        if ($status && in_array($status, array(200, 201, 204))) {
            $this->formInputs['success_action_status'] = (string) $status;
            $policy['conditions'][] = array(
                'success_action_status' => (string) $status
            );
            unset($options['success_action_status']);
        }
        foreach ($options as $key => $value) {
            $value = (string) $value;
            if ($value[0] === '^') {
                $value = substr($value, 1);
                $this->formInputs[$key] = $value;
                $value = preg_replace('/\$\{(\w*)\}/', '', $value);
                $policy['conditions'][] = array('starts-with', '$' . $key, $value);
            } else {
                $this->formInputs[$key] = $value;
                $policy['conditions'][] = array($key => $value);
            }
        }
        $policy = is_callable($policyCallback) ? $policyCallback($policy, $this) : $policy;
        $this->jsonPolicy = $rawJsonPolicy ?: json_encode($policy);
        $this->applyPolicy();
        return $this;
    }
    public function setClient(S3Client $client)
    {
        $this->client = $client;
        return $this;
    }
    public function getClient()
    {
        return $this->client;
    }
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;
        return $this;
    }
    public function getBucket()
    {
        return $this->bucket;
    }
    public function getFormAttributes()
    {
        return $this->formAttributes;
    }
    public function getFormInputs()
    {
        return $this->formInputs;
    }
    public function getJsonPolicy()
    {
        return $this->jsonPolicy;
    }
    protected function applyPolicy()
    {
        $jsonPolicy64 = base64_encode($this->jsonPolicy);
        $this->formInputs['policy'] = $jsonPolicy64;
        $this->formInputs['signature'] = base64_encode(hash_hmac(
            'sha1',
            $jsonPolicy64,
            $this->client->getCredentials()->getSecretKey(),
            true
        ));
    }
}
