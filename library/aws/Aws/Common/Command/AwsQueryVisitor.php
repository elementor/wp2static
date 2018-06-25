<?php
namespace Aws\Common\Command;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Service\Description\Parameter;
use Guzzle\Service\Command\CommandInterface;
use Guzzle\Service\Command\LocationVisitor\Request\AbstractRequestVisitor;
class AwsQueryVisitor extends AbstractRequestVisitor
{
    private $fqname;
    public function visit(CommandInterface $command, RequestInterface $request, Parameter $param, $value)
    {
        $this->fqname = $command->getName();
        $query = array();
        $this->customResolver($value, $param, $query, $param->getWireName());
        $request->addPostFields($query);
    }
    protected function customResolver($value, Parameter $param, array &$query, $prefix = '')
    {
        switch ($param->getType()) {
            case 'object':
                $this->resolveObject($param, $value, $prefix, $query);
                break;
            case 'array':
                $this->resolveArray($param, $value, $prefix, $query);
                break;
            default:
                $query[$prefix] = $param->filter($value);
        }
    }
    protected function resolveObject(Parameter $param, array $value, $prefix, array &$query)
    {
        $hasAdditionalProperties = ($param->getAdditionalProperties() instanceof Parameter);
        $additionalPropertyCount = 0;
        foreach ($value as $name => $v) {
            if ($subParam = $param->getProperty($name)) {
                $key = $prefix . '.' . $subParam->getWireName();
                $this->customResolver($v, $subParam, $query, $key);
            } elseif ($hasAdditionalProperties) {
                $additionalPropertyCount++;
                $data = $param->getData();
                $keyName = isset($data['keyName']) ? $data['keyName'] : 'key';
                $valueName = isset($data['valueName']) ? $data['valueName'] : 'value';
                $query["{$prefix}.{$additionalPropertyCount}.{$keyName}"] = $name;
                $newPrefix = "{$prefix}.{$additionalPropertyCount}.{$valueName}";
                if (is_array($v)) {
                    $this->customResolver($v, $param->getAdditionalProperties(), $query, $newPrefix);
                } else {
                    $query[$newPrefix] = $param->filter($v);
                }
            }
        }
    }
    protected function resolveArray(Parameter $param, array $value, $prefix, array &$query)
    {
        static $serializeEmpty = array(
            'SetLoadBalancerPoliciesForBackendServer' => 1,
            'SetLoadBalancerPoliciesOfListener' => 1,
            'UpdateStack' => 1
        );
        if (!$value) {
            if (isset($serializeEmpty[$this->fqname])) {
                if (substr($prefix, -7) === '.member') {
                    $prefix = substr($prefix, 0, -7);
                }
                $query[$prefix] = '';
            }
            return;
        }
        $offset = $param->getData('offset') ?: 1;
        foreach ($value as $index => $v) {
            $index += $offset;
            if (is_array($v) && $items = $param->getItems()) {
                $this->customResolver($v, $items, $query, $prefix . '.' . $index);
            } else {
                $query[$prefix . '.' . $index] = $param->filter($v);
            }
        }
    }
}
