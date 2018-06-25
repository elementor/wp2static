<?php
namespace Aws\Endpoint;
class PartitionEndpointProvider
{
    private $partitions;
    private $defaultPartition;
    public function __construct(array $partitions, $defaultPartition = 'aws')
    {
        $this->partitions = array_map(function (array $definition) {
            return new Partition($definition);
        }, array_values($partitions));
        $this->defaultPartition = $defaultPartition;
    }
    public function __invoke(array $args = [])
    {
        $partition = $this->getPartition(
            isset($args['region']) ? $args['region'] : '',
            isset($args['service']) ? $args['service'] : ''
        );
        return $partition($args);
    }
    public function getPartition($region, $service)
    {
        foreach ($this->partitions as $partition) {
            if ($partition->isRegionMatch($region, $service)) {
                return $partition;
            }
        }
        return $this->getPartitionByName($this->defaultPartition);
    }
    public function getPartitionByName($name)
    {
        foreach ($this->partitions as $partition) {
            if ($name === $partition->getName()) {
                return $partition;
            }
        }
    }
    public static function defaultProvider()
    {
        $data = \Aws\load_compiled_json(__DIR__ . '/../data/endpoints.json');
        return new self($data['partitions']);
    }
}
