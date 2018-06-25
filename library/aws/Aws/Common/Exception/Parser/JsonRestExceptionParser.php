<?php
namespace Aws\Common\Exception\Parser;
use Guzzle\Http\Message\Response;
class JsonRestExceptionParser extends AbstractJsonExceptionParser
{
    protected function doParse(array $data, Response $response)
    {
        if ($json = $data['parsed']) {
            $data = array_replace($data, $json);
        }
        if (!empty($data['type'])) {
            $data['type'] = strtolower($data['type']);
        }
        if ($code = (string) $response->getHeader('x-amzn-ErrorType')) {
            $data['code'] = substr($code, 0, strpos($code, ':'));
        }
        return $data;
    }
}
