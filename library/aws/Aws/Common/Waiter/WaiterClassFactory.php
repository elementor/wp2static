<?php
namespace Aws\Common\Waiter;
use Aws\Common\Exception\InvalidArgumentException;
use Guzzle\Inflection\Inflector;
use Guzzle\Inflection\InflectorInterface;
class WaiterClassFactory implements WaiterFactoryInterface
{
    protected $namespaces;
    protected $inflector;
    public function __construct($namespaces = array(), InflectorInterface $inflector = null)
    {
        $this->namespaces = (array) $namespaces;
        $this->inflector = $inflector ?: Inflector::getDefault();
    }
    public function registerNamespace($namespace)
    {
        array_unshift($this->namespaces, $namespace);
        return $this;
    }
    public function build($waiter)
    {
        if (!($className = $this->getClassName($waiter))) {
            throw new InvalidArgumentException("Waiter was not found matching {$waiter}.");
        }
        return new $className();
    }
    public function canBuild($waiter)
    {
        return $this->getClassName($waiter) !== null;
    }
    protected function getClassName($waiter)
    {
        $waiterName = $this->inflector->camel($waiter);
        $className = null;
        foreach ($this->namespaces as $namespace) {
            $potentialClassName = $namespace . '\\' . $waiterName;
            if (class_exists($potentialClassName)) {
                return $potentialClassName;
            }
        }
        return null;
    }
}
