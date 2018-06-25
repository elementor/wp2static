<?php
namespace Aws\Signature;
use Aws\Exception\UnresolvedSignatureException;
class SignatureProvider
{
    public static function resolve(callable $provider, $version, $service, $region)
    {
        $result = $provider($version, $service, $region);
        if ($result instanceof SignatureInterface) {
            return $result;
        }
        throw new UnresolvedSignatureException(
            "Unable to resolve a signature for $version/$service/$region.\n"
            . "Valid signature versions include v4 and anonymous."
        );
    }
    public static function defaultProvider()
    {
        return self::memoize(self::version());
    }
    public static function memoize(callable $provider)
    {
        $cache = [];
        return function ($version, $service, $region) use (&$cache, $provider) {
            $key = "($version)($service)($region)";
            if (!isset($cache[$key])) {
                $cache[$key] = $provider($version, $service, $region);
            }
            return $cache[$key];
        };
    }
    public static function version()
    {
        return function ($version, $service, $region) {
            switch ($version) {
                case 's3v4':
                case 'v4':
                    return $service === 's3'
                        ? new S3SignatureV4($service, $region)
                        : new SignatureV4($service, $region);
                case 'v4-unsigned-body':
                    return new SignatureV4($service, $region, ['unsigned-body' => 'true']);
                case 'anonymous':
                    return new AnonymousSignature();
                default:
                    return null;
            }
        };
    }
}
