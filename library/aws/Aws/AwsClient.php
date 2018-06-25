<?php
namespace Aws;
use Aws\Api\ApiProvider;
use Aws\Api\DocModel;
use Aws\Api\Service;
use Aws\Signature\SignatureProvider;
use GuzzleHttp\Psr7\Uri;
class AwsClient implements AwsClientInterface
{
    use AwsClientTrait;
    private $config;
    private $region;
    private $endpoint;
    private $api;
    private $signatureProvider;
    private $credentialProvider;
    private $handlerList;
    private $defaultRequestOptions;
    public static function getArguments()
    {
        return ClientResolver::getDefaultArguments();
    }
    public function __construct(array $args)
    {
        list($service, $exceptionClass) = $this->parseClass();
        if (!isset($args['service'])) {
            $args['service'] = manifest($service)['endpoint'];
        }
        if (!isset($args['exception_class'])) {
            $args['exception_class'] = $exceptionClass;
        }
        $this->handlerList = new HandlerList();
        $resolver = new ClientResolver(static::getArguments());
        $config = $resolver->resolve($args, $this->handlerList);
        $this->api = $config['api'];
        $this->signatureProvider = $config['signature_provider'];
        $this->endpoint = new Uri($config['endpoint']);
        $this->credentialProvider = $config['credentials'];
        $this->region = isset($config['region']) ? $config['region'] : null;
        $this->config = $config['config'];
        $this->defaultRequestOptions = $config['http'];
        $this->addSignatureMiddleware();
        $this->addInvocationId();
        if (isset($args['with_resolved'])) {
            $args['with_resolved']($config);
        }
    }
    public function getHandlerList()
    {
        return $this->handlerList;
    }
    public function getConfig($option = null)
    {
        return $option === null
            ? $this->config
            : (isset($this->config[$option])
                ? $this->config[$option]
                : null);
    }
    public function getCredentials()
    {
        $fn = $this->credentialProvider;
        return $fn();
    }
    public function getEndpoint()
    {
        return $this->endpoint;
    }
    public function getRegion()
    {
        return $this->region;
    }
    public function getApi()
    {
        return $this->api;
    }
    public function getCommand($name, array $args = [])
    {
        if (!isset($this->getApi()['operations'][$name])) {
            $name = ucfirst($name);
            if (!isset($this->getApi()['operations'][$name])) {
                throw new \InvalidArgumentException("Operation not found: $name");
            }
        }
        if (!isset($args['@http'])) {
            $args['@http'] = $this->defaultRequestOptions;
        } else {
            $args['@http'] += $this->defaultRequestOptions;
        }
        return new Command($name, $args, clone $this->getHandlerList());
    }
    public function __sleep()
    {
        throw new \RuntimeException('Instances of ' . static::class
            . ' cannot be serialized');
    }
    final protected function getSignatureProvider()
    {
        return $this->signatureProvider;
    }
    private function parseClass()
    {
        $klass = get_class($this);
        if ($klass === __CLASS__) {
            return ['', 'Aws\Exception\AwsException'];
        }
        $service = substr($klass, strrpos($klass, '\\') + 1, -6);
        return [
            strtolower($service),
            "Aws\\{$service}\\Exception\\{$service}Exception"
        ];
    }
    private function addSignatureMiddleware()
    {
        $api = $this->getApi();
        $provider = $this->signatureProvider;
        $version = $this->config['signature_version'];
        $name = $this->config['signing_name'];
        $region = $this->config['signing_region'];
        $resolver = static function (
            CommandInterface $c
        ) use ($api, $provider, $name, $region, $version) {
            $authType = $api->getOperation($c->getName())['authtype'];
            switch ($authType){
                case 'none':
                    $version = 'anonymous';
                    break;
                case 'v4-unsigned-body':
                    $version = 'v4-unsigned-body';
                    break;
            }
            return SignatureProvider::resolve($provider, $version, $name, $region);
        };
        $this->handlerList->appendSign(
            Middleware::signer($this->credentialProvider, $resolver),
            'signer'
        );
    }
    private function addInvocationId()
    {
        $this->handlerList->prependSign(Middleware::invocationId(), 'invocation-id');
    }
    public static function applyDocFilters(array $api, array $docs)
    {
        return [
            new Service($api, ApiProvider::defaultProvider()),
            new DocModel($docs)
        ];
    }
    public static function factory(array $config = [])
    {
        return new static($config);
    }
}
