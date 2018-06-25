<?php
namespace Aws\Api\Parser;
use Aws\Api\DateTimeResult;
use Aws\Api\Shape;
use Aws\Api\StructureShape;
use Aws\Result;
use Aws\CommandInterface;
use Psr\Http\Message\ResponseInterface;
abstract class AbstractRestParser extends AbstractParser
{
    use PayloadParserTrait;
    abstract protected function payload(
        ResponseInterface $response,
        StructureShape $member,
        array &$result
    );
    public function __invoke(
        CommandInterface $command,
        ResponseInterface $response
    ) {
        $output = $this->api->getOperation($command->getName())->getOutput();
        $result = [];
        if ($payload = $output['payload']) {
            $this->extractPayload($payload, $output, $response, $result);
        }
        foreach ($output->getMembers() as $name => $member) {
            switch ($member['location']) {
                case 'header':
                    $this->extractHeader($name, $member, $response, $result);
                    break;
                case 'headers':
                    $this->extractHeaders($name, $member, $response, $result);
                    break;
                case 'statusCode':
                    $this->extractStatus($name, $response, $result);
                    break;
            }
        }
        if (!$payload
            && $response->getBody()->getSize() > 0
            && count($output->getMembers()) > 0
        ) {
            $this->payload($response, $output, $result);
        }
        return new Result($result);
    }
    private function extractPayload(
        $payload,
        StructureShape $output,
        ResponseInterface $response,
        array &$result
    ) {
        $member = $output->getMember($payload);
        if ($member instanceof StructureShape) {
            $result[$payload] = [];
            $this->payload($response, $member, $result[$payload]);
        } else {
            $result[$payload] = $response->getBody();
        }
    }
    private function extractHeader(
        $name,
        Shape $shape,
        ResponseInterface $response,
        &$result
    ) {
        $value = $response->getHeaderLine($shape['locationName'] ?: $name);
        switch ($shape->getType()) {
            case 'float':
            case 'double':
                $value = (float) $value;
                break;
            case 'long':
                $value = (int) $value;
                break;
            case 'boolean':
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'blob':
                $value = base64_decode($value);
                break;
            case 'timestamp':
                try {
                    $value = new DateTimeResult($value);
                    break;
                } catch (\Exception $e) {
                    return;
                }
            case 'string':
                if ($shape['jsonvalue']) {
                    $value = $this->parseJson(base64_decode($value));
                }
                break;
        }
        $result[$name] = $value;
    }
    private function extractHeaders(
        $name,
        Shape $shape,
        ResponseInterface $response,
        &$result
    ) {
        $result[$name] = [];
        $prefix = $shape['locationName'];
        $prefixLen = strlen($prefix);
        foreach ($response->getHeaders() as $k => $values) {
            if (!$prefixLen) {
                $result[$name][$k] = implode(', ', $values);
            } elseif (stripos($k, $prefix) === 0) {
                $result[$name][substr($k, $prefixLen)] = implode(', ', $values);
            }
        }
    }
    private function extractStatus(
        $name,
        ResponseInterface $response,
        array &$result
    ) {
        $result[$name] = (int) $response->getStatusCode();
    }
}
