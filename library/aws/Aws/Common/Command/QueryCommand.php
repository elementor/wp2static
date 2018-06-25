<?php
namespace Aws\Common\Command;
use Guzzle\Service\Command\OperationCommand;
class QueryCommand extends OperationCommand
{
    protected static $queryVisitor;
    protected static $xmlVisitor;
    protected function init()
    {
        if (!self::$queryVisitor) {
            self::$queryVisitor = new AwsQueryVisitor();
        }
        if (!self::$xmlVisitor) {
            self::$xmlVisitor = new XmlResponseLocationVisitor();
        }
        $this->getRequestSerializer()->addVisitor('aws.query', self::$queryVisitor);
        $this->getResponseParser()->addVisitor('xml', self::$xmlVisitor);
    }
}
