<?php
namespace Symfony\Component\EventDispatcher\Tests;
use Symfony\Component\EventDispatcher\GenericEvent;
class GenericEventTest extends \PHPUnit_Framework_TestCase
{
    private $event;
    private $subject;
    protected function setUp()
    {
        parent::setUp();
        $this->subject = new \stdClass();
        $this->event = new GenericEvent($this->subject, array('name' => 'Event'));
    }
    protected function tearDown()
    {
        $this->subject = null;
        $this->event = null;
        parent::tearDown();
    }
    public function testConstruct()
    {
        $this->assertEquals($this->event, new GenericEvent($this->subject, array('name' => 'Event')));
    }
    public function testGetArguments()
    {
        $this->assertSame(array('name' => 'Event'), $this->event->getArguments());
    }
    public function testSetArguments()
    {
        $result = $this->event->setArguments(array('foo' => 'bar'));
        $this->assertAttributeSame(array('foo' => 'bar'), 'arguments', $this->event);
        $this->assertSame($this->event, $result);
    }
    public function testSetArgument()
    {
        $result = $this->event->setArgument('foo2', 'bar2');
        $this->assertAttributeSame(array('name' => 'Event', 'foo2' => 'bar2'), 'arguments', $this->event);
        $this->assertEquals($this->event, $result);
    }
    public function testGetArgument()
    {
        $this->assertEquals('Event', $this->event->getArgument('name'));
    }
    public function testGetArgException()
    {
        $this->event->getArgument('nameNotExist');
    }
    public function testOffsetGet()
    {
        $this->assertEquals('Event', $this->event['name']);
        $this->setExpectedException('InvalidArgumentException');
        $this->assertFalse($this->event['nameNotExist']);
    }
    public function testOffsetSet()
    {
        $this->event['foo2'] = 'bar2';
        $this->assertAttributeSame(array('name' => 'Event', 'foo2' => 'bar2'), 'arguments', $this->event);
    }
    public function testOffsetUnset()
    {
        unset($this->event['name']);
        $this->assertAttributeSame(array(), 'arguments', $this->event);
    }
    public function testOffsetIsset()
    {
        $this->assertTrue(isset($this->event['name']));
        $this->assertFalse(isset($this->event['nameNotExist']));
    }
    public function testHasArgument()
    {
        $this->assertTrue($this->event->hasArgument('name'));
        $this->assertFalse($this->event->hasArgument('nameNotExist'));
    }
    public function testGetSubject()
    {
        $this->assertSame($this->subject, $this->event->getSubject());
    }
    public function testHasIterator()
    {
        $data = array();
        foreach ($this->event as $key => $value) {
            $data[$key] = $value;
        }
        $this->assertEquals(array('name' => 'Event'), $data);
    }
}
