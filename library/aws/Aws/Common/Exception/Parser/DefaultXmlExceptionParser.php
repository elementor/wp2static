<?php
namespace Aws\Common\Exception\Parser;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
class DefaultXmlExceptionParser implements ExceptionParserInterface
{
    public function parse(RequestInterface $request, Response $response)
    {
        $data = array(
            'code'       => null,
            'message'    => null,
            'type'       => $response->isClientError() ? 'client' : 'server',
            'request_id' => null,
            'parsed'     => null
        );
        $body = $response->getBody(true);
        if (!$body) {
            $this->parseHeaders($request, $response, $data);
            return $data;
        }
        try {
            $xml = new \SimpleXMLElement($body);
            $this->parseBody($xml, $data);
            return $data;
        } catch (\Exception $e) {
            $data['code'] = 'PhpInternalXmlParseError';
            $data['message'] = 'A non-XML response was received';
            return $data;
        }
    }
    protected function parseHeaders(RequestInterface $request, Response $response, array &$data)
    {
        $data['message'] = $response->getStatusCode() . ' ' . $response->getReasonPhrase();
        if ($requestId = $response->getHeader('x-amz-request-id')) {
            $data['request_id'] = $requestId;
            $data['message'] .= " (Request-ID: $requestId)";
        }
    }
    protected function parseBody(\SimpleXMLElement $body, array &$data)
    {
        $data['parsed'] = $body;
        $namespaces = $body->getDocNamespaces();
        if (isset($namespaces[''])) {
            $body->registerXPathNamespace('ns', $namespaces['']);
            $prefix = 'ns:';
        } else {
            $prefix = '';
        }
        if ($tempXml = $body->xpath("
            $data['code'] = (string) $tempXml[0];
        }
        if ($tempXml = $body->xpath("
            $data['message'] = (string) $tempXml[0];
        }
        $tempXml = $body->xpath("
        if (empty($tempXml)) {
            $tempXml = $body->xpath("
        }
        if (isset($tempXml[0])) {
            $data['request_id'] = (string) $tempXml[0];
        }
    }
}
