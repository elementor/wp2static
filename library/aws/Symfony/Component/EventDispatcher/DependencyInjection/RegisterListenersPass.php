<?php
namespace Symfony\Component\EventDispatcher\DependencyInjection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
class RegisterListenersPass implements CompilerPassInterface
{
    protected $dispatcherService;
    protected $listenerTag;
    protected $subscriberTag;
    public function __construct($dispatcherService = 'event_dispatcher', $listenerTag = 'kernel.event_listener', $subscriberTag = 'kernel.event_subscriber')
    {
        $this->dispatcherService = $dispatcherService;
        $this->listenerTag = $listenerTag;
        $this->subscriberTag = $subscriberTag;
    }
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->dispatcherService) && !$container->hasAlias($this->dispatcherService)) {
            return;
        }
        $definition = $container->findDefinition($this->dispatcherService);
        foreach ($container->findTaggedServiceIds($this->listenerTag) as $id => $events) {
            $def = $container->getDefinition($id);
            if (!$def->isPublic()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must be public as event listeners are lazy-loaded.', $id));
            }
            if ($def->isAbstract()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must not be abstract as event listeners are lazy-loaded.', $id));
            }
            foreach ($events as $event) {
                $priority = isset($event['priority']) ? $event['priority'] : 0;
                if (!isset($event['event'])) {
                    throw new \InvalidArgumentException(sprintf('Service "%s" must define the "event" attribute on "%s" tags.', $id, $this->listenerTag));
                }
                if (!isset($event['method'])) {
                    $event['method'] = 'on'.preg_replace_callback(array(
                        '/(?<=\b)[a-z]/i',
                        '/[^a-z0-9]/i',
                    ), function ($matches) { return strtoupper($matches[0]); }, $event['event']);
                    $event['method'] = preg_replace('/[^a-z0-9]/i', '', $event['method']);
                }
                $definition->addMethodCall('addListenerService', array($event['event'], array($id, $event['method']), $priority));
            }
        }
        foreach ($container->findTaggedServiceIds($this->subscriberTag) as $id => $attributes) {
            $def = $container->getDefinition($id);
            if (!$def->isPublic()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must be public as event subscribers are lazy-loaded.', $id));
            }
            if ($def->isAbstract()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must not be abstract as event subscribers are lazy-loaded.', $id));
            }
            $class = $container->getParameterBag()->resolveValue($def->getClass());
            $interface = 'Symfony\Component\EventDispatcher\EventSubscriberInterface';
            if (!is_subclass_of($class, $interface)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
            }
            $definition->addMethodCall('addSubscriberService', array($id, $class));
        }
    }
}
