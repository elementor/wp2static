<?php
namespace Aws\Api\Serializer;
use Aws\Api\Service;
use Aws\CommandInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
class QuerySerializer
{
    private $endpoint;
    private $api;
    private $paramBuilder;
    public function __construct(
        Service $api,
        $endpoint,
        callable $paramBuilder = null
    ) {
        $this->api = $api;
        $this->endpoint = $endpoint;
        $this->paramBuilder = $paramBuilder ?: new QueryParamBuilder();
    }
    public function __invoke(CommandInterface $command)
    {
        $operation = $this->api->getOperation($command->getName());
        $body = [
            'Action'  => $command->getName(),
            'Version' => $this->api->getMetadata('apiVersion')
        ];
        $params = $command->toArray();
        if ($params) {
            $body += call_user_func(
                $this->paramBuilder,
                $operation->getInput(),
                $params
            );
        }
        $body = http_build_query($body, null, '&', PHP_QUERY_RFC3986);
        return new Request(
            'POST',
            $this->endpoint,
            [
                'Content-Length' => strlen($body),
                'Content-Type'   => 'application/x-www-form-urlencoded'
            ],
            $body
        );
    }
}
