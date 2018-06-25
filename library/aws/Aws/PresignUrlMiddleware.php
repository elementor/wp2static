<?php
namespace Aws;
use Aws\Signature\SignatureV4;
use Aws\Endpoint\EndpointProvider;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
class PresignUrlMiddleware
{
    private $client;
    private $endpointProvider;
    private $nextHandler;
    private $commandPool;
    private $serviceName;
    private $presignParam;
    private $requireDifferentRegion;
    public function __construct(
        array $options,
        callable $endpointProvider,
        AwsClientInterface $client,
        callable $nextHandler
    ) {
        $this->endpointProvider = $endpointProvider;
        $this->client = $client;
        $this->nextHandler = $nextHandler;
        $this->commandPool = $options['operations'];
        $this->serviceName = $options['service'];
        $this->presignParam = $options['presign_param'];
        $this->requireDifferentRegion = !empty($options['require_different_region']);
    }
    public static function wrap(
        AwsClientInterface $client,
        callable $endpointProvider,
        array $options = []
    ) {
        return function (callable $handler) use ($endpointProvider, $client, $options) {
            $f = new PresignUrlMiddleware($options, $endpointProvider, $client, $handler);
            return $f;
        };
    }
    public function __invoke(CommandInterface $cmd, RequestInterface $request = null)
    {
        if (in_array($cmd->getName(), $this->commandPool)
            && (!isset($cmd->{'__skip' . $cmd->getName()}))
        ) {
            $cmd['DestinationRegion'] = $this->client->getRegion();
            if (!$this->requireDifferentRegion
                || (!empty($cmd['SourceRegion'])
                    && $cmd['SourceRegion'] !== $cmd['DestinationRegion'])
            ) {
                $cmd[$this->presignParam] = $this->createPresignedUrl($this->client, $cmd);
            }
        }
        $f = $this->nextHandler;
        return $f($cmd, $request);
    }
    private function createPresignedUrl(
        AwsClientInterface $client,
        CommandInterface $cmd
    ) {
        $cmdName = $cmd->getName();
        $newCmd = $client->getCommand($cmdName, $cmd->toArray());
        $newCmd->{'__skip' . $cmdName} = true;
        $request = \Aws\serialize($newCmd);
        $endpoint = EndpointProvider::resolve($this->endpointProvider, [
            'region'  => $cmd['SourceRegion'],
            'service' => $this->serviceName,
        ])['endpoint'];
        $uri = $request->getUri()->withHost((new Uri($endpoint))->getHost());
        $request = $request->withUri($uri);
        $signer = new SignatureV4($this->serviceName, $cmd['SourceRegion']);
        return (string) $signer->presign(
            SignatureV4::convertPostToGet($request),
            $client->getCredentials()->wait(),
            '+1 hour'
        )->getUri();
    }
}
