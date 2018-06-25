<?php
namespace Aws\Api;
use Aws\Api\Serializer\QuerySerializer;
use Aws\Api\Serializer\Ec2ParamBuilder;
use Aws\Api\Parser\QueryParser;
class Service extends AbstractModel
{
    private $apiProvider;
    private $serviceName;
    private $apiVersion;
    private $operations = [];
    private $paginators = null;
    private $waiters = null;
    public function __construct(array $definition, callable $provider)
    {
        static $defaults = [
            'operations' => [],
            'shapes'     => [],
            'metadata'   => []
        ], $defaultMeta = [
            'apiVersion'       => null,
            'serviceFullName'  => null,
            'endpointPrefix'   => null,
            'signingName'      => null,
            'signatureVersion' => null,
            'protocol'         => null,
            'uid'              => null
        ];
        $definition += $defaults;
        $definition['metadata'] += $defaultMeta;
        $this->definition = $definition;
        $this->apiProvider = $provider;
        parent::__construct($definition, new ShapeMap($definition['shapes']));
        if (isset($definition['metadata']['serviceIdentifier'])) {
            $this->serviceName = $this->getServiceName();
        } else {
            $this->serviceName = $this->getEndpointPrefix();
        }
        $this->apiVersion = $this->getApiVersion();
    }
    public static function createSerializer(Service $api, $endpoint)
    {
        static $mapping = [
            'json'      => 'Aws\Api\Serializer\JsonRpcSerializer',
            'query'     => 'Aws\Api\Serializer\QuerySerializer',
            'rest-json' => 'Aws\Api\Serializer\RestJsonSerializer',
            'rest-xml'  => 'Aws\Api\Serializer\RestXmlSerializer'
        ];
        $proto = $api->getProtocol();
        if (isset($mapping[$proto])) {
            return new $mapping[$proto]($api, $endpoint);
        }
        if ($proto == 'ec2') {
            return new QuerySerializer($api, $endpoint, new Ec2ParamBuilder());
        }
        throw new \UnexpectedValueException(
            'Unknown protocol: ' . $api->getProtocol()
        );
    }
    public static function createErrorParser($protocol)
    {
        static $mapping = [
            'json'      => 'Aws\Api\ErrorParser\JsonRpcErrorParser',
            'query'     => 'Aws\Api\ErrorParser\XmlErrorParser',
            'rest-json' => 'Aws\Api\ErrorParser\RestJsonErrorParser',
            'rest-xml'  => 'Aws\Api\ErrorParser\XmlErrorParser',
            'ec2'       => 'Aws\Api\ErrorParser\XmlErrorParser'
        ];
        if (isset($mapping[$protocol])) {
            return new $mapping[$protocol]();
        }
        throw new \UnexpectedValueException("Unknown protocol: $protocol");
    }
    public static function createParser(Service $api)
    {
        static $mapping = [
            'json'      => 'Aws\Api\Parser\JsonRpcParser',
            'query'     => 'Aws\Api\Parser\QueryParser',
            'rest-json' => 'Aws\Api\Parser\RestJsonParser',
            'rest-xml'  => 'Aws\Api\Parser\RestXmlParser'
        ];
        $proto = $api->getProtocol();
        if (isset($mapping[$proto])) {
            return new $mapping[$proto]($api);
        }
        if ($proto == 'ec2') {
            return new QueryParser($api, null, false);
        }
        throw new \UnexpectedValueException(
            'Unknown protocol: ' . $api->getProtocol()
        );
    }
    public function getServiceFullName()
    {
        return $this->definition['metadata']['serviceFullName'];
    }
    public function getApiVersion()
    {
        return $this->definition['metadata']['apiVersion'];
    }
    public function getEndpointPrefix()
    {
        return $this->definition['metadata']['endpointPrefix'];
    }
    public function getSigningName()
    {
        return $this->definition['metadata']['signingName']
            ?: $this->definition['metadata']['endpointPrefix'];
    }
    public function getServiceName()
    {
        return $this->definition['metadata']['serviceIdentifier'];
    }
    public function getSignatureVersion()
    {
        return $this->definition['metadata']['signatureVersion'] ?: 'v4';
    }
    public function getProtocol()
    {
        return $this->definition['metadata']['protocol'];
    }
    public function getUid()
    {
        return $this->definition['metadata']['uid'];
    }
    public function hasOperation($name)
    {
        return isset($this['operations'][$name]);
    }
    public function getOperation($name)
    {
        if (!isset($this->operations[$name])) {
            if (!isset($this->definition['operations'][$name])) {
                throw new \InvalidArgumentException("Unknown operation: $name");
            }
            $this->operations[$name] = new Operation(
                $this->definition['operations'][$name],
                $this->shapeMap
            );
        }
        return $this->operations[$name];
    }
    public function getOperations()
    {
        $result = [];
        foreach ($this->definition['operations'] as $name => $definition) {
            $result[$name] = $this->getOperation($name);
        }
        return $result;
    }
    public function getMetadata($key = null)
    {
        if (!$key) {
            return $this['metadata'];
        }
        if (isset($this->definition['metadata'][$key])) {
            return $this->definition['metadata'][$key];
        }
        return null;
    }
    public function getPaginators()
    {
        if (!isset($this->paginators)) {
            $res = call_user_func(
                $this->apiProvider,
                'paginator',
                $this->serviceName,
                $this->apiVersion
            );
            $this->paginators = isset($res['pagination'])
                ? $res['pagination']
                : [];
        }
        return $this->paginators;
    }
    public function hasPaginator($name)
    {
        return isset($this->getPaginators()[$name]);
    }
    public function getPaginatorConfig($name)
    {
        static $defaults = [
            'input_token'  => null,
            'output_token' => null,
            'limit_key'    => null,
            'result_key'   => null,
            'more_results' => null,
        ];
        if ($this->hasPaginator($name)) {
            return $this->paginators[$name] + $defaults;
        }
        throw new \UnexpectedValueException("There is no {$name} "
            . "paginator defined for the {$this->serviceName} service.");
    }
    public function getWaiters()
    {
        if (!isset($this->waiters)) {
            $res = call_user_func(
                $this->apiProvider,
                'waiter',
                $this->serviceName,
                $this->apiVersion
            );
            $this->waiters = isset($res['waiters'])
                ? $res['waiters']
                : [];
        }
        return $this->waiters;
    }
    public function hasWaiter($name)
    {
        return isset($this->getWaiters()[$name]);
    }
    public function getWaiterConfig($name)
    {
        if ($this->hasWaiter($name)) {
            return $this->waiters[$name];
        }
        throw new \UnexpectedValueException("There is no {$name} waiter "
            . "defined for the {$this->serviceName} service.");
    }
    public function getShapeMap()
    {
        return $this->shapeMap;
    }
}
