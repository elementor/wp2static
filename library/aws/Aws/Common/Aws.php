<?php
namespace Aws\Common;
use Aws\Common\Facade\Facade;
use Guzzle\Service\Builder\ServiceBuilder;
use Guzzle\Service\Builder\ServiceBuilderLoader;
class Aws extends ServiceBuilder
{
    const VERSION = '2.8.31';
    public static function factory($config = null, array $globalParameters = array())
    {
        if (!$config) {
            $config = self::getDefaultServiceDefinition();
        } elseif (is_array($config)) {
            $globalParameters = $config;
            $config = self::getDefaultServiceDefinition();
        }
        $loader = new ServiceBuilderLoader();
        $loader->addAlias('_aws', self::getDefaultServiceDefinition())
            ->addAlias('_sdk1', __DIR__  . '/Resources/sdk1-config.php');
        return $loader->load($config, $globalParameters);
    }
    public static function getDefaultServiceDefinition()
    {
        return __DIR__  . '/Resources/aws-config.php';
    }
    public function getConfig()
    {
        return $this->builderConfig;
    }
    public function enableFacades($namespace = null)
    {
        Facade::mountFacades($this, $namespace);
        return $this;
    }
}
