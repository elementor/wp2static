<?php
namespace Aws\Common\InstanceMetadata;
use Aws\Common\Enum\ClientOptions as Options;
use Aws\Common\Exception\InstanceProfileCredentialsException;
use Aws\Common\Credentials\Credentials;
use Aws\Common\Client\AbstractClient;
use Guzzle\Common\Collection;
use Guzzle\Http\Message\RequestFactory;
class InstanceMetadataClient extends AbstractClient
{
    public static function factory($config = array())
    {
        $config = Collection::fromConfig($config, array(
            Options::BASE_URL => 'http:
            'version'         => 'latest',
            'request.options' => array(
                'connect_timeout' => 5,
                'timeout'         => 10
            )
        ), array('base_url', 'version'));
        return new self($config);
    }
    public function __construct(Collection $config)
    {
        $this->setConfig($config);
        $this->setBaseUrl($config->get(Options::BASE_URL));
        $this->defaultHeaders = new Collection();
        $this->setRequestFactory(RequestFactory::getInstance());
    }
    public function getInstanceProfileCredentials()
    {
        try {
            $request = $this->get('meta-data/iam/security-credentials/');
            $credentials = trim($request->send()->getBody(true));
            $result = $this->get("meta-data/iam/security-credentials/{$credentials}")->send()->json();
        } catch (\Exception $e) {
            $message = sprintf('Error retrieving credentials from the instance profile metadata server. When you are'
                . ' not running inside of Amazon EC2, you must provide your AWS access key ID and secret access key in'
                . ' the "key" and "secret" options when creating a client or provide an instantiated'
                . ' Aws\\Common\\Credentials\\CredentialsInterface object. (%s)', $e->getMessage());
            throw new InstanceProfileCredentialsException($message, $e->getCode());
        }
        if ($result['Code'] !== 'Success') {
            $e = new InstanceProfileCredentialsException('Unexpected response code: ' . $result['Code']);
            $e->setStatusCode($result['Code']);
            throw $e;
        }
        return new Credentials(
            $result['AccessKeyId'],
            $result['SecretAccessKey'],
            $result['Token'],
            strtotime($result['Expiration'])
        );
    }
}
