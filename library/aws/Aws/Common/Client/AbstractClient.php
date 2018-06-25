<?php
namespace Aws\Common\Client;
use Aws\Common\Aws;
use Aws\Common\Credentials\CredentialsInterface;
use Aws\Common\Enum\ClientOptions as Options;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Exception\TransferException;
use Aws\Common\RulesEndpointProvider;
use Aws\Common\Signature\EndpointSignatureInterface;
use Aws\Common\Signature\SignatureInterface;
use Aws\Common\Signature\SignatureListener;
use Aws\Common\Waiter\WaiterClassFactory;
use Aws\Common\Waiter\CompositeWaiterFactory;
use Aws\Common\Waiter\WaiterFactoryInterface;
use Aws\Common\Waiter\WaiterConfigFactory;
use Guzzle\Common\Collection;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\QueryAggregator\DuplicateAggregator;
use Guzzle\Service\Client;
use Guzzle\Service\Description\ServiceDescriptionInterface;
abstract class AbstractClient extends Client implements AwsClientInterface
{
    protected $credentials;
    protected $signature;
    protected $waiterFactory;
    protected $aggregator;
    public static function getAllEvents()
    {
        return array_merge(Client::getAllEvents(), array(
            'client.region_changed',
            'client.credentials_changed',
        ));
    }
    public function __construct(CredentialsInterface $credentials, SignatureInterface $signature, Collection $config)
    {
        parent::__construct($config->get(Options::BASE_URL), $config);
        $this->credentials = $credentials;
        $this->signature = $signature;
        $this->aggregator = new DuplicateAggregator();
        $this->setUserAgent('aws-sdk-php2/' . Aws::VERSION, true);
        $dispatcher = $this->getEventDispatcher();
        $dispatcher->addSubscriber(new SignatureListener($credentials, $signature));
        if ($backoff = $config->get(Options::BACKOFF)) {
            $dispatcher->addSubscriber($backoff, -255);
        }
    }
    public function __call($method, $args)
    {
        if (substr($method, 0, 3) === 'get' && substr($method, -8) === 'Iterator') {
            $commandOptions = isset($args[0]) ? $args[0] : null;
            $iteratorOptions = isset($args[1]) ? $args[1] : array();
            return $this->getIterator(substr($method, 3, -8), $commandOptions, $iteratorOptions);
        } elseif (substr($method, 0, 9) == 'waitUntil') {
            return $this->waitUntil(substr($method, 9), isset($args[0]) ? $args[0]: array());
        } else {
            return parent::__call(ucfirst($method), $args);
        }
    }
    public static function getEndpoint(ServiceDescriptionInterface $description, $region, $scheme)
    {
        try {
            $service = $description->getData('endpointPrefix');
            $provider = RulesEndpointProvider::fromDefaults();
            $result = $provider(array(
                'service' => $service,
                'region'  => $region,
                'scheme'  => $scheme
            ));
            return $result['endpoint'];
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), 0, $e);
        }
    }
    public function getCredentials()
    {
        return $this->credentials;
    }
    public function setCredentials(CredentialsInterface $credentials)
    {
        $formerCredentials = $this->credentials;
        $this->credentials = $credentials;
        $this->dispatch('client.credentials_changed', array(
            'credentials'        => $credentials,
            'former_credentials' => $formerCredentials,
        ));
        return $this;
    }
    public function getSignature()
    {
        return $this->signature;
    }
    public function getRegions()
    {
        return $this->serviceDescription->getData('regions');
    }
    public function getRegion()
    {
        return $this->getConfig(Options::REGION);
    }
    public function setRegion($region)
    {
        $config = $this->getConfig();
        $formerRegion = $config->get(Options::REGION);
        $global = $this->serviceDescription->getData('globalEndpoint');
        $provider = $config->get('endpoint_provider');
        if (!$provider) {
            throw new \RuntimeException('No endpoint provider configured');
        }
        if (!$global || $this->serviceDescription->getData('namespace') === 'S3') {
            $endpoint = call_user_func(
                $provider,
                array(
                    'scheme'  => $config->get(Options::SCHEME),
                    'region'  => $region,
                    'service' => $config->get(Options::SERVICE)
                )
            );
            $this->setBaseUrl($endpoint['endpoint']);
            $config->set(Options::BASE_URL, $endpoint['endpoint']);
            $config->set(Options::REGION, $region);
            $signature = $this->getSignature();
            if ($signature instanceof EndpointSignatureInterface) {
                $signature->setRegionName($region);
            }
            $this->dispatch('client.region_changed', array(
                'region'        => $region,
                'former_region' => $formerRegion,
            ));
        }
        return $this;
    }
    public function waitUntil($waiter, array $input = array())
    {
        $this->getWaiter($waiter, $input)->wait();
        return $this;
    }
    public function getWaiter($waiter, array $input = array())
    {
        return $this->getWaiterFactory()->build($waiter)
            ->setClient($this)
            ->setConfig($input);
    }
    public function setWaiterFactory(WaiterFactoryInterface $waiterFactory)
    {
        $this->waiterFactory = $waiterFactory;
        return $this;
    }
    public function getWaiterFactory()
    {
        if (!$this->waiterFactory) {
            $clientClass = get_class($this);
            $this->waiterFactory = new CompositeWaiterFactory(array(
                new WaiterClassFactory(substr($clientClass, 0, strrpos($clientClass, '\\')) . '\\Waiter')
            ));
            if ($this->getDescription()) {
                $waiterConfig = $this->getDescription()->getData('waiters') ?: array();
                $this->waiterFactory->addFactory(new WaiterConfigFactory($waiterConfig));
            }
        }
        return $this->waiterFactory;
    }
    public function getApiVersion()
    {
        return $this->serviceDescription->getApiVersion();
    }
    public function send($requests)
    {
        try {
            return parent::send($requests);
        } catch (CurlException $e) {
            $wrapped = new TransferException($e->getMessage(), null, $e);
            $wrapped->setCurlHandle($e->getCurlHandle())
                ->setCurlInfo($e->getCurlInfo())
                ->setError($e->getError(), $e->getErrorNo())
                ->setRequest($e->getRequest());
            throw $wrapped;
        }
    }
    public function createRequest(
        $method = 'GET',
        $uri = null,
        $headers = null,
        $body = null,
        array $options = array()
    ) {
        $request = parent::createRequest($method, $uri, $headers, $body, $options);
        $request->getQuery()->setAggregator($this->aggregator);
        return $request;
    }
}
