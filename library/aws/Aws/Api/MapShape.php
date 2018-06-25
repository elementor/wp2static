<?php
namespace Aws\Api;
class MapShape extends Shape
{
    private $value;
    private $key;
    public function __construct(array $definition, ShapeMap $shapeMap)
    {
        $definition['type'] = 'map';
        parent::__construct($definition, $shapeMap);
    }
    public function getValue()
    {
        if (!$this->value) {
            if (!isset($this->definition['value'])) {
                throw new \RuntimeException('No value specified');
            }
            $this->value = Shape::create(
                $this->definition['value'],
                $this->shapeMap
            );
        }
        return $this->value;
    }
    public function getKey()
    {
        if (!$this->key) {
            $this->key = isset($this->definition['key'])
                ? Shape::create($this->definition['key'], $this->shapeMap)
                : new Shape(['type' => 'string'], $this->shapeMap);
        }
        return $this->key;
    }
}
