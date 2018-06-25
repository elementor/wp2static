<?php
namespace Aws\Api\Serializer;
use Aws\Api\StructureShape;
use Aws\Api\Service;
class RestXmlSerializer extends RestSerializer
{
    private $xmlBody;
    public function __construct(
        Service $api,
        $endpoint,
        XmlBody $xmlBody = null
    ) {
        parent::__construct($api, $endpoint);
        $this->xmlBody = $xmlBody ?: new XmlBody($api);
    }
    protected function payload(StructureShape $member, array $value, array &$opts)
    {
        $opts['headers']['Content-Type'] = 'application/xml';
        $opts['body'] = (string) $this->xmlBody->build($member, $value);
    }
}
