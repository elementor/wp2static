<?php
namespace Aws\Api\Serializer;
use Aws\Api\Service;
use Aws\CommandInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
class JsonRpcSerializer
{
    private $jsonFormatter;
    private $endpoint;
    private $api;
    private $contentType;
    public function __construct(
        Service $api,
        $endpoint,
        JsonBody $jsonFormatter = null
    ) {
        $this->endpoint = $endpoint;
        $this->api = $api;
        $this->jsonFormatter = $jsonFormatter ?: new JsonBody($this->api);
        $this->contentType = JsonBody::getContentType($api);
    }
    public function __invoke(CommandInterface $command)
    {
        $name = $command->getName();
        $operation = $this->api->getOperation($name);
        return new Request(
            $operation['http']['method'],
            $this->endpoint,
            [
                'X-Amz-Target' => $this->api->getMetadata('targetPrefix') . '.' . $name,
                'Content-Type' => $this->contentType
            ],
            $this->jsonFormatter->build(
                $operation->getInput(),
                $command->toArray()
            )
        );
    }
}
