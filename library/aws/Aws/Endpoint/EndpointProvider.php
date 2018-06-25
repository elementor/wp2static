<?php
namespace Aws\Endpoint;
use Aws\Exception\UnresolvedEndpointException;
class EndpointProvider
{
    public static function resolve(callable $provider, array $args = [])
    {
        $result = $provider($args);
        if (is_array($result)) {
            return $result;
        }
        throw new UnresolvedEndpointException(
            'Unable to resolve an endpoint using the provider arguments: '
            . json_encode($args) . '. Note: you can provide an "endpoint" '
            . 'option to a client constructor to bypass invoking an endpoint '
            . 'provider.');
    }
    public static function defaultProvider()
    {
        return PartitionEndpointProvider::defaultProvider();
    }
    public static function patterns(array $patterns)
    {
        return new PatternEndpointProvider($patterns);
    }
}
