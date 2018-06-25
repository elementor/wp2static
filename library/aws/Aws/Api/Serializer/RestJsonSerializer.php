<?php
namespace Aws\Api\Serializer;
use Aws\Api\Service;
use Aws\Api\StructureShape;
class RestJsonSerializer extends RestSerializer
{
    private $jsonFormatter;
    private $contentType;
    public function __construct(
        Service $api,
        $endpoint,
        JsonBody $jsonFormatter = null
    ) {
        parent::__construct($api, $endpoint);
        $this->contentType = JsonBody::getContentType($api);
        $this->jsonFormatter = $jsonFormatter ?: new JsonBody($api);
    }
    protected function payload(StructureShape $member, array $value, array &$opts)
    {
        $opts['headers']['Content-Type'] = $this->contentType;
        $opts['body'] = (string) $this->jsonFormatter->build($member, $value);
    }
}
