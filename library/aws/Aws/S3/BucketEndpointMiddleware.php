<?php
namespace Aws\S3;
use Aws\CommandInterface;
use Psr\Http\Message\RequestInterface;
class BucketEndpointMiddleware
{
    private static $exclusions = ['GetBucketLocation' => true];
    private $nextHandler;
    public static function wrap()
    {
        return function (callable $handler) {
            return new self($handler);
        };
    }
    public function __construct(callable $nextHandler)
    {
        $this->nextHandler = $nextHandler;
    }
    public function __invoke(CommandInterface $command, RequestInterface $request)
    {
        $nextHandler = $this->nextHandler;
        $bucket = $command['Bucket'];
        if ($bucket && !isset(self::$exclusions[$command->getName()])) {
            $request = $this->modifyRequest($request, $command);
        }
        return $nextHandler($command, $request);
    }
    private function removeBucketFromPath($path, $bucket)
    {
        $len = strlen($bucket) + 1;
        if (substr($path, 0, $len) === "/{$bucket}") {
            $path = substr($path, $len);
        }
        return $path ?: '/';
    }
    private function modifyRequest(
        RequestInterface $request,
        CommandInterface $command
    ) {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $bucket = $command['Bucket'];
        $path = $this->removeBucketFromPath($path, $bucket);
        if ($command['Key']) {
            $path = S3Client::encodeKey(rawurldecode($path));
        }
        return $request->withUri($uri->withPath($path));
    }
}
