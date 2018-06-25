<?php
namespace Aws\Endpoint;
use ArrayAccess;
use Aws\HasDataTrait;
use InvalidArgumentException as Iae;
final class Partition implements ArrayAccess, PartitionInterface
{
    use HasDataTrait;
    public function __construct(array $definition)
    {
        foreach (['partition', 'regions', 'services', 'dnsSuffix'] as $key) {
            if (!isset($definition[$key])) {
                throw new Iae("Partition missing required $key field");
            }
        }
        $this->data = $definition;
    }
    public function getName()
    {
        return $this->data['partition'];
    }
    public function isRegionMatch($region, $service)
    {
        if (isset($this->data['regions'][$region])
            || isset($this->data['services'][$service]['endpoints'][$region])
        ) {
            return true;
        }
        if (isset($this->data['regionRegex'])) {
            return (bool) preg_match(
                "@{$this->data['regionRegex']}@",
                $region
            );
        }
        return false;
    }
    public function getAvailableEndpoints(
        $service,
        $allowNonRegionalEndpoints = false
    ) {
        if ($this->isServicePartitionGlobal($service)) {
            return [$this->getPartitionEndpoint($service)];
        }
        if (isset($this->data['services'][$service]['endpoints'])) {
            $serviceRegions = array_keys(
                $this->data['services'][$service]['endpoints']
            );
            return $allowNonRegionalEndpoints
                ? $serviceRegions
                : array_intersect($serviceRegions, array_keys(
                    $this->data['regions']
                ));
        }
        return [];
    }
    public function __invoke(array $args = [])
    {
        $service = isset($args['service']) ? $args['service'] : '';
        $region = isset($args['region']) ? $args['region'] : '';
        $scheme = isset($args['scheme']) ? $args['scheme'] : 'https';
        $data = $this->getEndpointData($service, $region);
        return [
            'endpoint' => "{$scheme}:
                    isset($data['hostname']) ? $data['hostname'] : '',
                    $service,
                    $region
                ),
            'signatureVersion' => $this->getSignatureVersion($data),
            'signingRegion' => isset($data['credentialScope']['region'])
                ? $data['credentialScope']['region']
                : $region,
            'signingName' => isset($data['credentialScope']['service'])
                ? $data['credentialScope']['service']
                : $service,
        ];
    }
    private function getEndpointData($service, $region)
    {
        $resolved = $this->resolveRegion($service, $region);
        $data = isset($this->data['services'][$service]['endpoints'][$resolved])
            ? $this->data['services'][$service]['endpoints'][$resolved]
            : [];
        $data += isset($this->data['services'][$service]['defaults'])
            ? $this->data['services'][$service]['defaults']
            : [];
        $data += isset($this->data['defaults'])
            ? $this->data['defaults']
            : [];
        return $data;
    }
    private function getSignatureVersion(array $data)
    {
        static $supportedBySdk = [
            's3v4',
            'v4',
            'anonymous',
        ];
        $possibilities = array_intersect(
            $supportedBySdk,
            isset($data['signatureVersions'])
                ? $data['signatureVersions']
                : ['v4']
        );
        return array_shift($possibilities);
    }
    private function resolveRegion($service, $region)
    {
        if ($this->isServicePartitionGlobal($service)) {
            return $this->getPartitionEndpoint($service);
        }
        return $region;
    }
    private function isServicePartitionGlobal($service)
    {
        return isset($this->data['services'][$service]['isRegionalized'])
            && false === $this->data['services'][$service]['isRegionalized']
            && isset($this->data['services'][$service]['partitionEndpoint']);
    }
    private function getPartitionEndpoint($service)
    {
        return $this->data['services'][$service]['partitionEndpoint'];
    }
    private function formatEndpoint($template, $service, $region)
    {
        return strtr($template, [
            '{service}' => $service,
            '{region}' => $region,
            '{dnsSuffix}' => $this->data['dnsSuffix'],
        ]);
    }
}
