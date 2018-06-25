<?php
namespace Symfony\Component\EventDispatcher\Tests\DependencyInjection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
class RegisterListenersPassTest extends \PHPUnit_Framework_TestCase
{
    public function testEventSubscriberWithoutInterface()
    {
        $services = array(
            'my_event_subscriber' => array(0 => array()),
        );
        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');
        $definition->expects($this->atLeastOnce())
            ->method('isPublic')
            ->will($this->returnValue(true));
        $definition->expects($this->atLeastOnce())
            ->method('getClass')
            ->will($this->returnValue('stdClass'));
        $builder = $this->getMock(
            'Symfony\Component\DependencyInjection\ContainerBuilder',
            array('hasDefinition', 'findTaggedServiceIds', 'getDefinition')
        );
        $builder->expects($this->any())
            ->method('hasDefinition')
            ->will($this->returnValue(true));
        $builder->expects($this->atLeastOnce())
            ->method('findTaggedServiceIds')
            ->will($this->onConsecutiveCalls(array(), $services));
        $builder->expects($this->atLeastOnce())
            ->method('getDefinition')
            ->will($this->returnValue($definition));
        $registerListenersPass = new RegisterListenersPass();
        $registerListenersPass->process($builder);
    }
    public function testValidEventSubscriber()
    {
        $services = array(
            'my_event_subscriber' => array(0 => array()),
        );
        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');
        $definition->expects($this->atLeastOnce())
            ->method('isPublic')
            ->will($this->returnValue(true));
        $definition->expects($this->atLeastOnce())
            ->method('getClass')
            ->will($this->returnValue('Symfony\Component\EventDispatcher\Tests\DependencyInjection\SubscriberService'));
        $builder = $this->getMock(
            'Symfony\Component\DependencyInjection\ContainerBuilder',
            array('hasDefinition', 'findTaggedServiceIds', 'getDefinition', 'findDefinition')
        );
        $builder->expects($this->any())
            ->method('hasDefinition')
            ->will($this->returnValue(true));
        $builder->expects($this->atLeastOnce())
            ->method('findTaggedServiceIds')
            ->will($this->onConsecutiveCalls(array(), $services));
        $builder->expects($this->atLeastOnce())
            ->method('getDefinition')
            ->will($this->returnValue($definition));
        $builder->expects($this->atLeastOnce())
            ->method('findDefinition')
            ->will($this->returnValue($definition));
        $registerListenersPass = new RegisterListenersPass();
        $registerListenersPass->process($builder);
    }
    public function testPrivateEventListener()
    {
        $container = new ContainerBuilder();
        $container->register('foo', 'stdClass')->setPublic(false)->addTag('kernel.event_listener', array());
        $container->register('event_dispatcher', 'stdClass');
        $registerListenersPass = new RegisterListenersPass();
        $registerListenersPass->process($container);
    }
    public function testPrivateEventSubscriber()
    {
        $container = new ContainerBuilder();
        $container->register('foo', 'stdClass')->setPublic(false)->addTag('kernel.event_subscriber', array());
        $container->register('event_dispatcher', 'stdClass');
        $registerListenersPass = new RegisterListenersPass();
        $registerListenersPass->process($container);
    }
    public function testAbstractEventListener()
    {
        $container = new ContainerBuilder();
        $container->register('foo', 'stdClass')->setAbstract(true)->addTag('kernel.event_listener', array());
        $container->register('event_dispatcher', 'stdClass');
        $registerListenersPass = new RegisterListenersPass();
        $registerListenersPass->process($container);
    }
    public function testAbstractEventSubscriber()
    {
        $container = new ContainerBuilder();
        $container->register('foo', 'stdClass')->setAbstract(true)->addTag('kernel.event_subscriber', array());
        $container->register('event_dispatcher', 'stdClass');
        $registerListenersPass = new RegisterListenersPass();
        $registerListenersPass->process($container);
    }
    public function testEventSubscriberResolvableClassName()
    {
        $container = new ContainerBuilder();
        $container->setParameter('subscriber.class', 'Symfony\Component\EventDispatcher\Tests\DependencyInjection\SubscriberService');
        $container->register('foo', '%subscriber.class%')->addTag('kernel.event_subscriber', array());
        $container->register('event_dispatcher', 'stdClass');
        $registerListenersPass = new RegisterListenersPass();
        $registerListenersPass->process($container);
        $definition = $container->getDefinition('event_dispatcher');
        $expected_calls = array(
            array(
                'addSubscriberService',
                array(
                    'foo',
                    'Symfony\Component\EventDispatcher\Tests\DependencyInjection\SubscriberService',
                ),
            ),
        );
        $this->assertSame($expected_calls, $definition->getMethodCalls());
    }
    public function testEventSubscriberUnresolvableClassName()
    {
        $container = new ContainerBuilder();
        $container->register('foo', '%subscriber.class%')->addTag('kernel.event_subscriber', array());
        $container->register('event_dispatcher', 'stdClass');
        $registerListenersPass = new RegisterListenersPass();
        $registerListenersPass->process($container);
    }
}
class SubscriberService implements \Symfony\Component\EventDispatcher\EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
    }
}
