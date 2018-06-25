<?php
namespace Aws\Api\Parser;
use Aws\Api\Service;
use Aws\Result;
use Aws\CommandInterface;
use Psr\Http\Message\ResponseInterface;
class QueryParser extends AbstractParser
{
    use PayloadParserTrait;
    private $xmlParser;
    private $honorResultWrapper;
    public function __construct(
        Service $api,
        XmlParser $xmlParser = null,
        $honorResultWrapper = true
    ) {
        parent::__construct($api);
        $this->xmlParser = $xmlParser ?: new XmlParser();
        $this->honorResultWrapper = $honorResultWrapper;
    }
    public function __invoke(
        CommandInterface $command,
        ResponseInterface $response
    ) {
        $output = $this->api->getOperation($command->getName())->getOutput();
        $xml = $this->parseXml($response->getBody());
        if ($this->honorResultWrapper && $output['resultWrapper']) {
            $xml = $xml->{$output['resultWrapper']};
        }
        return new Result($this->xmlParser->parse($output, $xml));
    }
}
