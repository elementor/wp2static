<?php
namespace Aws\Api\Parser;
use Aws\Api\Service;
use Aws\Api\StructureShape;
use Psr\Http\Message\ResponseInterface;
class RestJsonParser extends AbstractRestParser
{
    use PayloadParserTrait;
    private $parser;
    public function __construct(Service $api, JsonParser $parser = null)
    {
        parent::__construct($api);
        $this->parser = $parser ?: new JsonParser();
    }
    protected function payload(
        ResponseInterface $response,
        StructureShape $member,
        array &$result
    ) {
        $jsonBody = $this->parseJson($response->getBody());
        if ($jsonBody) {
            $result += $this->parser->parse($member, $jsonBody);
        }
    }
}
