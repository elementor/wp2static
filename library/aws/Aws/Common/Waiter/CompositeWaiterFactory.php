<?php
namespace Aws\Common\Waiter;
use Aws\Common\Exception\InvalidArgumentException;
class CompositeWaiterFactory implements WaiterFactoryInterface
{
    protected $factories;
    public function __construct(array $factories)
    {
        $this->factories = $factories;
    }
    public function build($waiter)
    {
        if (!($factory = $this->getFactory($waiter))) {
            throw new InvalidArgumentException("Waiter was not found matching {$waiter}.");
        }
        return $factory->build($waiter);
    }
    public function canBuild($waiter)
    {
        return (bool) $this->getFactory($waiter);
    }
    public function addFactory(WaiterFactoryInterface $factory)
    {
        $this->factories[] = $factory;
        return $this;
    }
    protected function getFactory($waiter)
    {
        foreach ($this->factories as $factory) {
            if ($factory->canBuild($waiter)) {
                return $factory;
            }
        }
        return false;
    }
}
