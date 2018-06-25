<?php
namespace Aws\Api\Parser;
use Aws\Api\StructureShape;
use Aws\Api\Service;
use Psr\Http\Message\ResponseInterface;
class RestXmlParser extends AbstractRestParser
{
    use PayloadParserTrait;
    private $parser;
    public function __construct(Service $api, XmlParser $parser = null)
    {
        parent::__construct($api);
        $this->parser = $parser ?: new XmlParser();
    }
    protected function payload(
        ResponseInterface $response,
        StructureShape $member,
        array &$result
    ) {
        $xml = $this->parseXml($response->getBody());
        $result += $this->parser->parse($member, $xml);
    }
}
