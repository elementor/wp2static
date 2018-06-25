<?php
namespace Aws;
use Aws\Endpoint\PartitionEndpointProvider;
use Aws\Endpoint\PartitionInterface;
class MultiRegionClient implements AwsClientInterface
{
    use AwsClientTrait;
    private $clientPool = [];
    private $factory;
    private $partition;
    private $args;
    private $config;
    private $handlerList;
    public static function getArguments()
    {
        $args = array_intersect_key(
            ClientResolver::getDefaultArguments(),
            ['service' => true, 'region' => true]
        );
        $args['region']['required'] = false;
        return $args + [
            'client_factory' => [
                'type' => 'config',
                'valid' => ['callable'],
                'doc' => 'A callable that takes an array of client'
                    . ' configuration arguments and returns a regionalized'
                    . ' client.',
                'required' => true,
                'internal' => true,
                'default' => function (array $args) {
                    $namespace = manifest($args['service'])['namespace'];
                    $klass = "Aws\\{$namespace}\\{$namespace}Client";
                    $region = isset($args['region']) ? $args['region'] : null;
                    return function (array $args) use ($klass, $region) {
                        if ($region && empty($args['region'])) {
                            $args['region'] = $region;
                        }
                        return new $klass($args);
                    };
                },
            ],
            'partition' => [
                'type'    => 'config',
                'valid'   => ['string', PartitionInterface::class],
                'doc'     => 'AWS partition to connect to. Valid partitions'
                    . ' include "aws," "aws-cn," and "aws-us-gov." Used to'
                    . ' restrict the scope of the mapRegions method.',
                'default' => function (array $args) {
                    $region = isset($args['region']) ? $args['region'] : '';
                    return PartitionEndpointProvider::defaultProvider()
                        ->getPartition($region, $args['service']);
                },
                'fn'      => function ($value, array &$args) {
                    if (is_string($value)) {
                        $value = PartitionEndpointProvider::defaultProvider()
                            ->getPartitionByName($value);
                    }
                    if (!$value instanceof PartitionInterface) {
                        throw new \InvalidArgumentException('No valid partition'
                            . ' was provided. Provide a concrete partition or'
                            . ' the name of a partition (e.g., "aws," "aws-cn,"'
                            . ' or "aws-us-gov").'
                        );
                    }
                    $args['partition'] = $value;
                    $args['endpoint_provider'] = $value;
                }
            ],
        ];
    }
    public function __construct(array $args = [])
    {
        if (!isset($args['service'])) {
            $args['service'] = $this->parseClass();
        }
        $this->handlerList = new HandlerList(function (
            CommandInterface $command
        ) {
            list($region, $args) = $this->getRegionFromArgs($command->toArray());
            $command = $this->getClientFromPool($region)
                ->getCommand($command->getName(), $args);
            return $this->executeAsync($command);
        });
        $argDefinitions = static::getArguments();
        $resolver = new ClientResolver($argDefinitions);
        $args = $resolver->resolve($args, $this->handlerList);
        $this->config = $args['config'];
        $this->factory = $args['client_factory'];
        $this->partition = $args['partition'];
        $this->args = array_diff_key($args, $args['config']);
    }
    public function getRegion()
    {
        return $this->getClientFromPool()->getRegion();
    }
    public function getCommand($name, array $args = [])
    {
        return new Command($name, $args, clone $this->getHandlerList());
    }
    public function getConfig($option = null)
    {
        if (null === $option) {
            return $this->config;
        }
        if (isset($this->config[$option])) {
            return $this->config[$option];
        }
        return $this->getClientFromPool()->getConfig($option);
    }
    public function getCredentials()
    {
        return $this->getClientFromPool()->getCredentials();
    }
    public function getHandlerList()
    {
        return $this->handlerList;
    }
    public function getApi()
    {
        return $this->getClientFromPool()->getApi();
    }
    public function getEndpoint()
    {
        return $this->getClientFromPool()->getEndpoint();
    }
    protected function getClientFromPool($region = '')
    {
        if (empty($this->clientPool[$region])) {
            $factory = $this->factory;
            $this->clientPool[$region] = $factory(
                array_replace($this->args, array_filter(['region' => $region]))
            );
        }
        return $this->clientPool[$region];
    }
    private function parseClass()
    {
        $klass = get_class($this);
        if ($klass === __CLASS__) {
            return '';
        }
        return strtolower(substr($klass, strrpos($klass, '\\') + 1, -17));
    }
    private function getRegionFromArgs(array $args)
    {
        $region = isset($args['@region'])
            ? $args['@region']
            : $this->getRegion();
        unset($args['@region']);
        return [$region, $args];
    }
}
