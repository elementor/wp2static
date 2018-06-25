<?php
namespace Aws;
class Command implements CommandInterface
{
    use HasDataTrait;
    private $name;
    private $handlerList;
    public function __construct($name, array $args = [], HandlerList $list = null)
    {
        $this->name = $name;
        $this->data = $args;
        $this->handlerList = $list ?: new HandlerList();
        if (!isset($this->data['@http'])) {
            $this->data['@http'] = [];
        }
    }
    public function __clone()
    {
        $this->handlerList = clone $this->handlerList;
    }
    public function getName()
    {
        return $this->name;
    }
    public function hasParam($name)
    {
        return array_key_exists($name, $this->data);
    }
    public function getHandlerList()
    {
        return $this->handlerList;
    }
    public function get($name)
    {
        return $this[$name];
    }
}
