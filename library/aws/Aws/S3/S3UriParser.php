<?php
namespace Aws\S3;
use GuzzleHttp\Psr7;
use Psr\Http\Message\UriInterface;
class S3UriParser
{
    private $pattern = '/^(.+\\.)?s3[.-]([A-Za-z0-9-]+)\\./';
    private $streamWrapperScheme = 's3';
    private static $defaultResult = [
        'path_style' => true,
        'bucket'     => null,
        'key'        => null,
        'region'     => null
    ];
    public function parse($uri)
    {
        $url = Psr7\uri_for($uri);
        if ($url->getScheme() == $this->streamWrapperScheme) {
            return $this->parseStreamWrapper($url);
        }
        if (!$url->getHost()) {
            throw new \InvalidArgumentException('No hostname found in URI: '
                . $uri);
        }
        if (!preg_match($this->pattern, $url->getHost(), $matches)) {
            return $this->parseCustomEndpoint($url);
        }
        $result = empty($matches[1])
            ? $this->parsePathStyle($url)
            : $this->parseVirtualHosted($url, $matches);
        $result['region'] = $matches[2] == 'amazonaws' ? null : $matches[2];
        return $result;
    }
    private function parseStreamWrapper(UriInterface $url)
    {
        $result = self::$defaultResult;
        $result['path_style'] = false;
        $result['bucket'] = $url->getHost();
        if ($url->getPath()) {
            $key = ltrim($url->getPath(), '/ ');
            if (!empty($key)) {
                $result['key'] = $key;
            }
        }
        return $result;
    }
    private function parseCustomEndpoint(UriInterface $url)
    {
        $result = self::$defaultResult;
        $path = ltrim($url->getPath(), '/ ');
        $segments = explode('/', $path, 2);
        if (isset($segments[0])) {
            $result['bucket'] = $segments[0];
            if (isset($segments[1])) {
                $result['key'] = $segments[1];
            }
        }
        return $result;
    }
    private function parsePathStyle(UriInterface $url)
    {
        $result = self::$defaultResult;
        if ($url->getPath() != '/') {
            $path = ltrim($url->getPath(), '/');
            if ($path) {
                $pathPos = strpos($path, '/');
                if ($pathPos === false) {
                    $result['bucket'] = $path;
                } elseif ($pathPos == strlen($path) - 1) {
                    $result['bucket'] = substr($path, 0, -1);
                } else {
                    $result['bucket'] = substr($path, 0, $pathPos);
                    $result['key'] = substr($path, $pathPos + 1) ?: null;
                }
            }
        }
        return $result;
    }
    private function parseVirtualHosted(UriInterface $url, array $matches)
    {
        $result = self::$defaultResult;
        $result['path_style'] = false;
        $result['bucket'] = substr($matches[1], 0, -1);
        $path = $url->getPath();
        $result['key'] = !$path || $path == '/' ? null : substr($path, 1);
        return $result;
    }
}
