<?php
namespace Aws\S3;
use Aws\CommandInterface;
use Psr\Http\Message\RequestInterface;
class SSECMiddleware
{
    private $endpointScheme;
    private $nextHandler;
    public static function wrap($endpointScheme)
    {
        return function (callable $handler) use ($endpointScheme) {
            return new self($endpointScheme, $handler);
        };
    }
    public function __construct($endpointScheme, callable $nextHandler)
    {
        $this->nextHandler = $nextHandler;
        $this->endpointScheme = $endpointScheme;
    }
    public function __invoke(
        CommandInterface $command,
        RequestInterface $request = null
    ) {
        if (($command['SSECustomerKey'] || $command['CopySourceSSECustomerKey'])
            && $this->endpointScheme !== 'https'
        ) {
            throw new \RuntimeException('You must configure your S3 client to '
                . 'use HTTPS in order to use the SSE-C features.');
        }
        if ($command['SSECustomerKey']) {
            $this->prepareSseParams($command);
        }
        if ($command['CopySourceSSECustomerKey']) {
            $this->prepareSseParams($command, 'CopySource');
        }
        $f = $this->nextHandler;
        return $f($command, $request);
    }
    private function prepareSseParams(CommandInterface $command, $prefix = '')
    {
        $key = $command[$prefix . 'SSECustomerKey'];
        $command[$prefix . 'SSECustomerKey'] = base64_encode($key);
        if ($md5 = $command[$prefix . 'SSECustomerKeyMD5']) {
            $command[$prefix . 'SSECustomerKeyMD5'] = base64_encode($md5);
        } else {
            $command[$prefix . 'SSECustomerKeyMD5'] = base64_encode(md5($key, true));
        }
    }
}
