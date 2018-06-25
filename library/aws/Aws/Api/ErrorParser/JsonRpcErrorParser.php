<?php
namespace Aws\Api\ErrorParser;
use Psr\Http\Message\ResponseInterface;
class JsonRpcErrorParser
{
    use JsonParserTrait;
    public function __invoke(ResponseInterface $response)
    {
        $data = $this->genericHandler($response);
        if ($data['parsed']) {
            $data['parsed'] = array_change_key_case($data['parsed']);
        }
        if (isset($data['parsed']['__type'])) {
            $parts = explode('#', $data['parsed']['__type']);
            $data['code'] = isset($parts[1]) ? $parts[1] : $parts[0];
            $data['message'] = isset($data['parsed']['message'])
                ? $data['parsed']['message']
                : null;
        }
        return $data;
    }
}
