<?php
namespace Aws;
class Sdk
{
    const VERSION = '3.61.3';
    private $args;
    public function __construct(array $args = [])
    {
        $this->args = $args;
        if (!isset($args['handler']) && !isset($args['http_handler'])) {
            $this->args['http_handler'] = default_http_handler();
        }
    }
    public function __call($name, array $args)
    {
        $args = isset($args[0]) ? $args[0] : [];
        if (strpos($name, 'createMultiRegion') === 0) {
            return $this->createMultiRegionClient(substr($name, 17), $args);
        }
        if (strpos($name, 'create') === 0) {
            return $this->createClient(substr($name, 6), $args);
        }
        throw new \BadMethodCallException("Unknown method: {$name}.");
    }
    public function createClient($name, array $args = [])
    {
        $service = manifest($name);
        $namespace = $service['namespace'];
        $client = "Aws\\{$namespace}\\{$namespace}Client";
        return new $client($this->mergeArgs($namespace, $service, $args));
    }
    public function createMultiRegionClient($name, array $args = [])
    {
        $service = manifest($name);
        $namespace = $service['namespace'];
        $klass = "Aws\\{$namespace}\\{$namespace}MultiRegionClient";
        $klass = class_exists($klass) ? $klass : 'Aws\\MultiRegionClient';
        return new $klass($this->mergeArgs($namespace, $service, $args));
    }
    private function mergeArgs($namespace, array $manifest, array $args = [])
    {
        if (isset($this->args[$namespace])) {
            $args += $this->args[$namespace];
        }
        if (!isset($args['service'])) {
            $args['service'] = $manifest['endpoint'];
        }
        return $args + $this->args;
    }
    public static function getEndpointPrefix($name)
    {
        return manifest($name)['endpoint'];
    }
}
