<?php
namespace Aws\S3;
use Aws\CommandInterface;
use Aws\ResultInterface;
use Psr\Http\Message\RequestInterface;
class PutObjectUrlMiddleware
{
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
    public function __invoke(CommandInterface $command, RequestInterface $request = null)
    {
        $next = $this->nextHandler;
        return $next($command, $request)->then(
            function (ResultInterface $result) use ($command) {
                $name = $command->getName();
                switch ($name) {
                    case 'PutObject':
                    case 'CopyObject':
                        $result['ObjectURL'] = $result['@metadata']['effectiveUri'];
                        break;
                    case 'CompleteMultipartUpload':
                        $result['ObjectURL'] = $result['Location'];
                        break;
                }
                return $result;
            }
        );
    }
}
