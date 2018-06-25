<?php
namespace Aws\Api;
class StructureShape extends Shape
{
    private $members;
    public function __construct(array $definition, ShapeMap $shapeMap)
    {
        $definition['type'] = 'structure';
        if (!isset($definition['members'])) {
            $definition['members'] = [];
        }
        parent::__construct($definition, $shapeMap);
    }
    public function getMembers()
    {
        if (empty($this->members)) {
            $this->generateMembersHash();
        }
        return $this->members;
    }
    public function hasMember($name)
    {
        return isset($this->definition['members'][$name]);
    }
    public function getMember($name)
    {
        $members = $this->getMembers();
        if (!isset($members[$name])) {
            throw new \InvalidArgumentException('Unknown member ' . $name);
        }
        return $members[$name];
    }
    private function generateMembersHash()
    {
        $this->members = [];
        foreach ($this->definition['members'] as $name => $definition) {
            $this->members[$name] = $this->shapeFor($definition);
        }
    }
}
