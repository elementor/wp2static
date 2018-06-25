<?php
namespace Aws\Api;
class ShapeMap
{
    private $definitions;
    private $simple;
    public function __construct(array $shapeModels)
    {
        $this->definitions = $shapeModels;
    }
    public function getShapeNames()
    {
        return array_keys($this->definitions);
    }
    public function resolve(array $shapeRef)
    {
        $shape = $shapeRef['shape'];
        if (!isset($this->definitions[$shape])) {
            throw new \InvalidArgumentException('Shape not found: ' . $shape);
        }
        $isSimple = count($shapeRef) == 1;
        if ($isSimple && isset($this->simple[$shape])) {
            return $this->simple[$shape];
        }
        $definition = $shapeRef + $this->definitions[$shape];
        $definition['name'] = $definition['shape'];
        unset($definition['shape']);
        $result = Shape::create($definition, $this);
        if ($isSimple) {
            $this->simple[$shape] = $result;
        }
        return $result;
    }
}
