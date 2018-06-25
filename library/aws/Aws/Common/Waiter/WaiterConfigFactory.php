<?php
namespace Aws\Common\Waiter;
use Aws\Common\Exception\InvalidArgumentException;
use Guzzle\Inflection\Inflector;
use Guzzle\Inflection\InflectorInterface;
class WaiterConfigFactory implements WaiterFactoryInterface
{
    protected $config;
    protected $inflector;
    public function __construct(
        array $config,
        InflectorInterface $inflector = null
    ) {
        $this->config = $config;
        $this->inflector = $inflector ?: Inflector::getDefault();
    }
    public function build($waiter)
    {
        return new ConfigResourceWaiter($this->getWaiterConfig($waiter));
    }
    public function canBuild($waiter)
    {
        return isset($this->config[$waiter]) || isset($this->config[$this->inflector->camel($waiter)]);
    }
    protected function getWaiterConfig($name)
    {
        if (!$this->canBuild($name)) {
            throw new InvalidArgumentException('No waiter found matching "' . $name . '"');
        }
        $name = isset($this->config[$name]) ? $name : $this->inflector->camel($name);
        $waiter = new WaiterConfig($this->config[$name]);
        $waiter['name'] = $name;
        if (isset($this->config['__default__'])) {
            $parentWaiter = new WaiterConfig($this->config['__default__']);
            $waiter = $parentWaiter->overwriteWith($waiter);
        }
        if (isset($this->config[$name]['extends'])) {
            $waiter = $this->getWaiterConfig($this->config[$name]['extends'])->overwriteWith($waiter);
        }
        return $waiter;
    }
}
