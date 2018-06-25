<?php
namespace Aws\Credentials;
use Aws\Exception\CredentialsException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
class EcsCredentialProvider
{
    const SERVER_URI = 'http:
    const ENV_URI = "AWS_CONTAINER_CREDENTIALS_RELATIVE_URI";
    private $client;
    public function __construct(array $config = [])
    {
        $this->timeout = isset($config['timeout']) ? $config['timeout'] : 1.0;
        $this->client = isset($config['client'])
            ? $config['client']
            : \Aws\default_http_handler();
    }
    public function __invoke()
    {
        $client = $this->client;
        $request = new Request('GET', self::getEcsUri());
        return $client(
            $request,
            [
                'timeout' => $this->timeout,
                'proxy' => '',
            ]
        )->then(function (ResponseInterface $response) {
            $result = $this->decodeResult((string) $response->getBody());
            return new Credentials(
                $result['AccessKeyId'],
                $result['SecretAccessKey'],
                $result['Token'],
                strtotime($result['Expiration'])
            );
        })->otherwise(function ($reason) {
            $reason = is_array($reason) ? $reason['exception'] : $reason;
            $msg = $reason->getMessage();
            throw new CredentialsException(
                "Error retrieving credential from ECS ($msg)"
            );
        });
    }
    private function getEcsUri()
    {
        $creds_uri = getenv(self::ENV_URI);
        return self::SERVER_URI . $creds_uri;
    }
    private function decodeResult($response)
    {
        $result = json_decode($response, true);
        if (!isset($result['AccessKeyId'])) {
            throw new CredentialsException('Unexpected ECS credential value');
        }
        return $result;
    }
}
