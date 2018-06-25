<?php
namespace Aws\S3\Exception\Parser;
use Aws\Common\Exception\Parser\DefaultXmlExceptionParser;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
class S3ExceptionParser extends DefaultXmlExceptionParser
{
    public function parse(RequestInterface $request, Response $response)
    {
        $data = parent::parse($request, $response);
        if ($response->getStatusCode() === 301) {
            $data['type'] = 'client';
            if (isset($data['message'], $data['parsed'])) {
                $data['message'] = rtrim($data['message'], '.') . ': "' . $data['parsed']->Endpoint . '".';
            }
        }
        return $data;
    }
    protected function parseHeaders(RequestInterface $request, Response $response, array &$data)
    {
        parent::parseHeaders($request, $response, $data);
        $status  = $response->getStatusCode();
        $method  = $request->getMethod();
        if ($status === 403) {
            $data['code'] = 'AccessDenied';
        } elseif ($method === 'HEAD' && $status === 404) {
            $path   = explode('/', trim($request->getPath(), '/'));
            $host   = explode('.', $request->getHost());
            $bucket = (count($host) === 4) ? $host[0] : array_shift($path);
            $object = array_shift($path);
            if ($bucket && $object) {
                $data['code'] = 'NoSuchKey';
            } elseif ($bucket) {
                $data['code'] = 'NoSuchBucket';
            }
        }
    }
}
