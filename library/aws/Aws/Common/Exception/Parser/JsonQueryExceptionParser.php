<?php
namespace Aws\Common\Exception\Parser;
use Guzzle\Http\Message\Response;
class JsonQueryExceptionParser extends AbstractJsonExceptionParser
{
    protected function doParse(array $data, Response $response)
    {
        if ($json = $data['parsed']) {
            if (isset($json['__type'])) {
                $parts = explode('#', $json['__type']);
                $data['code'] = isset($parts[1]) ? $parts[1] : $parts[0];
            }
            $data['message'] = isset($json['message']) ? $json['message'] : null;
        }
        return $data;
    }
}
