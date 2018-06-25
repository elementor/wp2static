<?php
namespace Aws\Api\ErrorParser;
use Psr\Http\Message\ResponseInterface;
class RestJsonErrorParser
{
    use JsonParserTrait;
    public function __invoke(ResponseInterface $response)
    {
        $data = $this->genericHandler($response);
        if ($json = $data['parsed']) {
            $data = array_replace($data, $json);
        }
        if (!empty($data['type'])) {
            $data['type'] = strtolower($data['type']);
        }
        if ($code = $response->getHeaderLine('x-amzn-errortype')) {
            $colon = strpos($code, ':');
            $data['code'] = $colon ? substr($code, 0, $colon) : $code;
        }
        return $data;
    }
}
