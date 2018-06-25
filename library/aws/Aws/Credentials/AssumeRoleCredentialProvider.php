<?php
namespace Aws\Credentials;
use Aws\Exception\CredentialsException;
use Aws\Result;
use Aws\Sts\StsClient;
use GuzzleHttp\Promise\PromiseInterface;
class AssumeRoleCredentialProvider
{
    const ERROR_MSG = "Missing required 'AssumeRoleCredentialProvider' configuration option: ";
    private $client;
    private $assumeRoleParams;
    public function __construct(array $config = [])
    {
        if (!isset($config['assume_role_params'])) {
            throw new \InvalidArgumentException(self::ERROR_MSG . "'assume_role_params'.");
        }
        if (!isset($config['client'])) {
            throw new \InvalidArgumentException(self::ERROR_MSG . "'client'.");
        }
        $this->client = $config['client'];
        $this->assumeRoleParams = $config['assume_role_params'];
    }
    public function __invoke()
    {
        $client = $this->client;
        return $client->assumeRoleAsync($this->assumeRoleParams)
            ->then(function (Result $result) {
                return $this->client->createCredentials($result);
            })->otherwise(function (\RuntimeException $exception) {
                throw new CredentialsException(
                    "Error in retrieving assume role credentials.",
                    0,
                    $exception
                );
            });
    }
}
