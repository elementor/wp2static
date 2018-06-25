<?php
namespace Aws\Common\Client;
use Aws\Common\Credentials\Credentials;
use Aws\Common\Credentials\CredentialsInterface;
use Aws\Common\Credentials\NullCredentials;
use Aws\Common\Enum\ClientOptions as Options;
use Aws\Common\Exception\ExceptionListener;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Exception\NamespaceExceptionFactory;
use Aws\Common\Exception\Parser\DefaultXmlExceptionParser;
use Aws\Common\Exception\Parser\ExceptionParserInterface;
use Aws\Common\Iterator\AwsResourceIteratorFactory;
use Aws\Common\RulesEndpointProvider;
use Aws\Common\Signature\EndpointSignatureInterface;
use Aws\Common\Signature\SignatureInterface;
use Aws\Common\Signature\SignatureV2;
use Aws\Common\Signature\SignatureV3Https;
use Aws\Common\Signature\SignatureV4;
use Guzzle\Common\Collection;
use Guzzle\Plugin\Backoff\BackoffPlugin;
use Guzzle\Plugin\Backoff\CurlBackoffStrategy;
use Guzzle\Plugin\Backoff\ExponentialBackoffStrategy;
use Guzzle\Plugin\Backoff\HttpBackoffStrategy;
use Guzzle\Plugin\Backoff\TruncatedBackoffStrategy;
use Guzzle\Service\Description\ServiceDescription;
use Guzzle\Service\Resource\ResourceIteratorClassFactory;
use Guzzle\Log\LogAdapterInterface;
use Guzzle\Log\ClosureLogAdapter;
use Guzzle\Plugin\Backoff\BackoffLogger;
class ClientBuilder
{
    protected static $commonConfigDefaults = array('scheme' => 'https');
    protected static $commonConfigRequirements = array(Options::SERVICE_DESCRIPTION);
    protected $clientNamespace;
    protected $config = array();
    protected $configDefaults = array();
    protected $configRequirements = array();
    protected $exceptionParser;
    protected $iteratorsConfig = array();
    private $clientClass;
    private $serviceName;
    public static function factory($namespace = null)
    {
        return new static($namespace);
    }
    public function __construct($namespace = null)
    {
        $this->clientNamespace = $namespace;
        $this->clientClass = 'Aws\Common\Client\DefaultClient';
        if ($this->clientNamespace) {
            $this->serviceName = substr($this->clientNamespace, strrpos($this->clientNamespace, '\\') + 1);
            $this->clientClass = $this->clientNamespace . '\\' . $this->serviceName . 'Client';
        }
    }
    public function setConfig($config)
    {
        $this->config = $this->processArray($config);
        return $this;
    }
    public function setConfigDefaults($defaults)
    {
        $this->configDefaults = $this->processArray($defaults);
        return $this;
    }
    public function setConfigRequirements($required)
    {
        $this->configRequirements = $this->processArray($required);
        return $this;
    }
    public function setExceptionParser(ExceptionParserInterface $parser)
    {
        $this->exceptionParser = $parser;
        return $this;
    }
    public function setIteratorsConfig(array $config)
    {
        $this->iteratorsConfig = $config;
        return $this;
    }
    public function build()
    {
        $config = Collection::fromConfig(
            $this->config,
            array_merge(self::$commonConfigDefaults, $this->configDefaults),
            (self::$commonConfigRequirements + $this->configRequirements)
        );
        if ($config[Options::VERSION] === 'latest') {
            $config[Options::VERSION] = constant("{$this->clientClass}::LATEST_API_VERSION");
        }
        if (!isset($config['endpoint_provider'])) {
            $config['endpoint_provider'] = RulesEndpointProvider::fromDefaults();
        }
        $description = $this->updateConfigFromDescription($config);
        $signature = $this->getSignature($description, $config);
        $credentials = $this->getCredentials($config);
        $this->extractHttpConfig($config);
        if (!$this->exceptionParser) {
            $this->exceptionParser = new DefaultXmlExceptionParser();
        }
        $backoff = $config->get(Options::BACKOFF);
        if ($backoff === null) {
            $retries = isset($config[Options::BACKOFF_RETRIES]) ? $config[Options::BACKOFF_RETRIES] : 3;
            $backoff = $this->createDefaultBackoff($retries);
            $config->set(Options::BACKOFF, $backoff);
        }
        if ($backoff) {
            $this->addBackoffLogger($backoff, $config);
        }
        $client = new $this->clientClass($credentials, $signature, $config);
        $client->setDescription($description);
        if ($this->clientNamespace) {
            $exceptionFactory = new NamespaceExceptionFactory(
                $this->exceptionParser,
                "{$this->clientNamespace}\\Exception",
                "{$this->clientNamespace}\\Exception\\{$this->serviceName}Exception"
            );
            $client->addSubscriber(new ExceptionListener($exceptionFactory));
        }
        $client->addSubscriber(new UserAgentListener());
        $client->getConfig()->set(
            'params.cache.key_filter',
            'header=date,x-amz-date,x-amz-security-token,x-amzn-authorization'
        );
        $client->setResourceIteratorFactory(new AwsResourceIteratorFactory(
            $this->iteratorsConfig,
            new ResourceIteratorClassFactory($this->clientNamespace . '\\Iterator')
        ));
        if ($config->get(Options::VALIDATION) === false) {
            $params = $config->get('command.params') ?: array();
            $params['command.disable_validation'] = true;
            $config->set('command.params', $params);
        }
        return $client;
    }
    protected function addBackoffLogger(BackoffPlugin $plugin, Collection $config)
    {
        if ($logger = $config->get(Options::BACKOFF_LOGGER)) {
            $format = $config->get(Options::BACKOFF_LOGGER_TEMPLATE);
            if ($logger === 'debug') {
                $logger = new ClosureLogAdapter(function ($message) {
                    trigger_error($message . "\n");
                });
            } elseif (!($logger instanceof LogAdapterInterface)) {
                throw new InvalidArgumentException(
                    Options::BACKOFF_LOGGER . ' must be set to `debug` or an instance of '
                        . 'Guzzle\\Common\\Log\\LogAdapterInterface'
                );
            }
            $logPlugin = new BackoffLogger($logger);
            if ($format) {
                $logPlugin->setTemplate($format);
            }
            $plugin->addSubscriber($logPlugin);
        }
    }
    protected function processArray($array)
    {
        if ($array instanceof Collection) {
            $array = $array->getAll();
        }
        if (!is_array($array)) {
            throw new InvalidArgumentException('The config must be provided as an array or Collection.');
        }
        return $array;
    }
    protected function updateConfigFromDescription(Collection $config)
    {
        $description = $config->get(Options::SERVICE_DESCRIPTION);
        if (!($description instanceof ServiceDescription)) {
            if (is_string($description)) {
                $description = sprintf($description, $config->get(Options::VERSION));
            }
            $description = ServiceDescription::factory($description);
            $config->set(Options::SERVICE_DESCRIPTION, $description);
        }
        if (!$config->get(Options::SERVICE)) {
            $config->set(Options::SERVICE, $description->getData('endpointPrefix'));
        }
        if ($iterators = $description->getData('iterators')) {
            $this->setIteratorsConfig($iterators);
        }
        $this->handleRegion($config);
        $this->handleEndpoint($config);
        return $description;
    }
    protected function getSignature(ServiceDescription $description, Collection $config)
    {
        $signature = $config->get(Options::SIGNATURE) ?: $description->getData('signatureVersion');
        if (is_string($signature)) {
            if ($signature == 'v4') {
                $signature = new SignatureV4();
            } elseif ($signature == 'v2') {
                $signature = new SignatureV2();
            } elseif ($signature == 'v3https') {
                $signature = new SignatureV3Https();
            } else {
                throw new InvalidArgumentException("Invalid signature type: {$signature}");
            }
        } elseif (!($signature instanceof SignatureInterface)) {
            throw new InvalidArgumentException('The provided signature is not '
                . 'a signature version string or an instance of '
                . 'Aws\\Common\\Signature\\SignatureInterface');
        }
        if ($signature instanceof EndpointSignatureInterface) {
            $signature->setServiceName($config->get(Options::SIGNATURE_SERVICE)
                ?: $description->getData('signingName')
                ?: $description->getData('endpointPrefix'));
            $signature->setRegionName($config->get(Options::SIGNATURE_REGION) ?: $config->get(Options::REGION));
        }
        return $signature;
    }
    protected function getCredentials(Collection $config)
    {
        $credentials = $config->get(Options::CREDENTIALS);
        if (is_array($credentials)) {
            $credentials = Credentials::factory($credentials);
        } elseif ($credentials === false) {
            $credentials = new NullCredentials();
        } elseif (!$credentials instanceof CredentialsInterface) {
            $credentials = Credentials::factory($config);
        }
        return $credentials;
    }
    private function handleRegion(Collection $config)
    {
        $region = $config[Options::REGION];
        $description = $config[Options::SERVICE_DESCRIPTION];
        $global = $description->getData('globalEndpoint');
        if (!$global && !$region) {
            throw new InvalidArgumentException(
                'A region is required when using ' . $description->getData('serviceFullName')
            );
        } elseif ($global && !$region) {
            $config[Options::REGION] = 'us-east-1';
        }
    }
    private function handleEndpoint(Collection $config)
    {
        if ($config['endpoint']) {
            $config[Options::BASE_URL] = $config['endpoint'];
            return;
        }
        if ($config[Options::BASE_URL]) {
            return;
        }
        $endpoint = call_user_func(
            $config['endpoint_provider'],
            array(
                'scheme'  => $config[Options::SCHEME],
                'region'  => $config[Options::REGION],
                'service' => $config[Options::SERVICE]
            )
        );
        $config[Options::BASE_URL] = $endpoint['endpoint'];
        if (!$config->hasKey(Options::SIGNATURE)
            && isset($endpoint['signatureVersion'])
        ) {
            $config->set(Options::SIGNATURE, $endpoint['signatureVersion']);
        }
        if (isset($endpoint['credentialScope'])) {
            $scope = $endpoint['credentialScope'];
            if (isset($scope['region'])) {
                $config->set(Options::SIGNATURE_REGION, $scope['region']);
            }
        }
    }
    private function createDefaultBackoff($retries = 3)
    {
        return new BackoffPlugin(
            new TruncatedBackoffStrategy($retries,
                new ThrottlingErrorChecker($this->exceptionParser,
                    new CurlBackoffStrategy(null,
                        new HttpBackoffStrategy(array(500, 503, 509),
                            new ExpiredCredentialsChecker($this->exceptionParser,
                                new ExponentialBackoffStrategy()
                            )
                        )
                    )
                )
            )
        );
    }
    private function extractHttpConfig(Collection $config)
    {
        $http = $config['http'];
        if (!is_array($http)) {
            return;
        }
        if (isset($http['verify'])) {
            $config[Options::SSL_CERT] = $http['verify'];
        }
    }
}
