<?php
namespace Aws\Api;
use Aws\Exception\UnresolvedApiException;
class ApiProvider
{
    private static $typeMap = [
        'api'       => 'api-2',
        'paginator' => 'paginators-1',
        'waiter'    => 'waiters-2',
        'docs'      => 'docs-2',
    ];
    private $manifest;
    private $modelsDir;
    public static function resolve(callable $provider, $type, $service, $version)
    {
        $result = $provider($type, $service, $version);
        if (is_array($result)) {
            if (!isset($result['metadata']['serviceIdentifier'])) {
                $result['metadata']['serviceIdentifier'] = $service;
            }
            return $result;
        }
        if (!isset(self::$typeMap[$type])) {
            $msg = "The type must be one of: " . implode(', ', self::$typeMap);
        } elseif ($service) {
            $msg = "The {$service} service does not have version: {$version}.";
        } else {
            $msg = "You must specify a service name to retrieve its API data.";
        }
        throw new UnresolvedApiException($msg);
    }
    public static function defaultProvider()
    {
        return new self(__DIR__ . '/../data', \Aws\manifest());
    }
    public static function manifest($dir, array $manifest)
    {
        return new self($dir, $manifest);
    }
    public static function filesystem($dir)
    {
        return new self($dir);
    }
    public function getVersions($service)
    {
        if (!isset($this->manifest)) {
            $this->buildVersionsList($service);
        }
        if (!isset($this->manifest[$service]['versions'])) {
            return [];
        }
        return array_values(array_unique($this->manifest[$service]['versions']));
    }
    public function __invoke($type, $service, $version)
    {
        if (isset(self::$typeMap[$type])) {
            $type = self::$typeMap[$type];
        } else {
            return null;
        }
        if (!isset($this->manifest)) {
            $this->buildVersionsList($service);
        }
        if (!isset($this->manifest[$service]['versions'][$version])) {
            return null;
        }
        $version = $this->manifest[$service]['versions'][$version];
        $path = "{$this->modelsDir}/{$service}/{$version}/{$type}.json";
        try {
            return \Aws\load_compiled_json($path);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }
    private function __construct($modelsDir, array $manifest = null)
    {
        $this->manifest = $manifest;
        $this->modelsDir = rtrim($modelsDir, '/');
        if (!is_dir($this->modelsDir)) {
            throw new \InvalidArgumentException(
                "The specified models directory, {$modelsDir}, was not found."
            );
        }
    }
    private function buildVersionsList($service)
    {
        $dir = "{$this->modelsDir}/{$service}/";
        if (!is_dir($dir)) {
            return;
        }
        $results = array_diff(scandir($dir, SCANDIR_SORT_DESCENDING), ['..', '.']);
        if (!$results) {
            $this->manifest[$service] = ['versions' => []];
        } else {
            $this->manifest[$service] = [
                'versions' => [
                    'latest' => $results[0]
                ]
            ];
            $this->manifest[$service]['versions'] += array_combine($results, $results);
        }
    }
}
