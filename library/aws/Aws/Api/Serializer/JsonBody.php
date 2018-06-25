<?php
namespace Aws\Api\Serializer;
use Aws\Api\Service;
use Aws\Api\Shape;
use Aws\Api\TimestampShape;
class JsonBody
{
    private $api;
    public function __construct(Service $api)
    {
        $this->api = $api;
    }
    public static function getContentType(Service $service)
    {
        return 'application/x-amz-json-'
            . number_format($service->getMetadata('jsonVersion'), 1);
    }
    public function build(Shape $shape, array $args)
    {
        $result = json_encode($this->format($shape, $args));
        return $result == '[]' ? '{}' : $result;
    }
    private function format(Shape $shape, $value)
    {
        switch ($shape['type']) {
            case 'structure':
                $data = [];
                foreach ($value as $k => $v) {
                    if ($v !== null && $shape->hasMember($k)) {
                        $valueShape = $shape->getMember($k);
                        $data[$valueShape['locationName'] ?: $k]
                            = $this->format($valueShape, $v);
                    }
                }
                return $data;
            case 'list':
                $items = $shape->getMember();
                foreach ($value as $k => $v) {
                    $value[$k] = $this->format($items, $v);
                }
                return $value;
            case 'map':
                if (empty($value)) {
                    return new \stdClass;
                }
                $values = $shape->getValue();
                foreach ($value as $k => $v) {
                    $value[$k] = $this->format($values, $v);
                }
                return $value;
            case 'blob':
                return base64_encode($value);
            case 'timestamp':
                return TimestampShape::format($value, 'unixTimestamp');
            default:
                return $value;
        }
    }
}
