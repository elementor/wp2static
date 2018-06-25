<?php
namespace Aws\Common\Facade;
use Aws\Common\Aws;
abstract class Facade implements FacadeInterface
{
    protected static $serviceBuilder;
    public static function mountFacades(Aws $serviceBuilder, $targetNamespace = null)
    {
        self::$serviceBuilder = $serviceBuilder;
        require_once __DIR__ . '/facade-classes.php';
        foreach ($serviceBuilder->getConfig() as $service) {
            if (isset($service['alias'], $service['class'])) {
                $facadeClass = __NAMESPACE__ . '\\' . $service['alias'];
                $facadeAlias = ltrim($targetNamespace . '\\' . $service['alias'], '\\');
                if (!class_exists($facadeAlias) && class_exists($facadeClass)) {
                    class_alias($facadeClass, $facadeAlias);
                }
            }
        }
    }
    public static function getClient()
    {
        return self::$serviceBuilder->get(static::getServiceBuilderKey());
    }
    public static function __callStatic($method, $args)
    {
        return call_user_func_array(array(self::getClient(), $method), $args);
    }
}
