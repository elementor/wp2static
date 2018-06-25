<?php
namespace Aws\Common\Command;
use Guzzle\Service\Description\Operation;
use Guzzle\Service\Command\CommandInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Service\Description\Parameter;
use Guzzle\Service\Command\LocationVisitor\Response\XmlVisitor;
class XmlResponseLocationVisitor extends XmlVisitor
{
    public function before(CommandInterface $command, array &$result)
    {
        parent::before($command, $result);
        $operation = $command->getOperation();
        if ($operation->getServiceDescription()->getData('resultWrapped')) {
            $wrappingNode = $operation->getName() . 'Result';
            if (isset($result[$wrappingNode])) {
                $result = $result[$wrappingNode] + $result;
                unset($result[$wrappingNode]);
            }
        }
    }
    public function visit(
        CommandInterface $command,
        Response $response,
        Parameter $param,
        &$value,
        $context =  null
    ) {
        parent::visit($command, $response, $param, $value, $context);
        if ($param->getData('wrapper')) {
            $wireName = $param->getWireName();
            $value += $value[$wireName];
            unset($value[$wireName]);
        }
    }
    public static function xmlMap($value, $entryName, $keyName, $valueName)
    {
        $result = array();
        foreach ($value as $entry) {
            $result[$entry[$keyName]] = $entry[$valueName];
        }
        return $result;
    }
}
