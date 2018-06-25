<?php
namespace Aws\Common;
use Guzzle\Http\Url;
class HostNameUtils
{
    const DEFAULT_REGION = 'us-east-1';
    const DEFAULT_GOV_REGION = 'us-gov-west-1';
    public static function parseRegionName(Url $url)
    {
        if (substr($url->getHost(), -14) != '.amazonaws.com') {
            return self::DEFAULT_REGION;
        }
        $serviceAndRegion = substr($url->getHost(), 0, -14);
        $separator = strpos($serviceAndRegion, 's3') === 0 ? '-' : '.';
        $separatorPos = strpos($serviceAndRegion, $separator);
        if ($separatorPos === false) {
            return self::DEFAULT_REGION;
        }
        $region = substr($serviceAndRegion, $separatorPos + 1);
        if ($region == 'us-gov') {
            return self::DEFAULT_GOV_REGION;
        }
        return $region;
    }
    public static function parseServiceName(Url $url)
    {
        $parts = explode('.', $url->getHost(), 2);
        if (stripos($parts[0], 's3') === 0) {
            return 's3';
        }
        return $parts[0];
    }
}
