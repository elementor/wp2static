<?php
namespace Aws\Common\Exception\Parser;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
abstract class AbstractJsonExceptionParser implements ExceptionParserInterface
{
    public function parse(RequestInterface $request, Response $response)
    {
        $data = array(
            'code'       => null,
            'message'    => null,
            'type'       => $response->isClientError() ? 'client' : 'server',
            'request_id' => (string) $response->getHeader('x-amzn-RequestId'),
            'parsed'     => null
        );
        if (null !== $json = json_decode($response->getBody(true), true)) {
            $data['parsed'] = array_change_key_case($json);
        }
        $data = $this->doParse($data, $response);
        if (isset($data['code']) && strpos($data['code'], 'Fault')) {
            $data['code'] = preg_replace('/^([a-zA-Z]+)Fault$/', '$1', $data['code']);
        }
        return $data;
    }
    abstract protected function doParse(array $data, Response $response);
}
