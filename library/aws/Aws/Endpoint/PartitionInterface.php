<?php
namespace Aws\Endpoint;
interface PartitionInterface
{
    public function getName();
    public function isRegionMatch($region, $service);
    public function getAvailableEndpoints(
        $service,
        $allowNonRegionalEndpoints = false
    );
    public function __invoke(array $args = []);
}
