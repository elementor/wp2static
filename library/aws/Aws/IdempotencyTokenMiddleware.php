<?php
namespace Aws;
use Aws\Api\Service;
use Psr\Http\Message\RequestInterface;
class IdempotencyTokenMiddleware
{
    private $service;
    private $bytesGenerator;
    private $nextHandler;
    public static function wrap(
        Service $service,
        callable $bytesGenerator = null
    ) {
        return function (callable $handler) use ($service, $bytesGenerator) {
            return new self($handler, $service, $bytesGenerator);
        };
    }
    public function __construct(
        callable $nextHandler,
        Service $service,
        callable $bytesGenerator = null
    ) {
        $this->bytesGenerator = $bytesGenerator
            ?: $this->findCompatibleRandomSource();
        $this->service = $service;
        $this->nextHandler = $nextHandler;
    }
    public function __invoke(
        CommandInterface $command,
        RequestInterface $request = null
    ) {
        $handler = $this->nextHandler;
        if ($this->bytesGenerator) {
            $operation = $this->service->getOperation($command->getName());
            $members = $operation->getInput()->getMembers();
            foreach ($members as $member => $value) {
                if ($value['idempotencyToken']) {
                    $bytes = call_user_func($this->bytesGenerator, 16);
                    $command[$member] = $command[$member]
                        ?: $this->getUuidV4($bytes);
                    break;
                }
            }
        }
        return $handler($command, $request);
    }
    private static function getUuidV4($bytes)
    {
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
    private function findCompatibleRandomSource()
    {
        if (function_exists('random_bytes')) {
            return 'random_bytes';
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return 'openssl_random_pseudo_bytes';
        }
        if (function_exists('mcrypt_create_iv')) {
            return 'mcrypt_create_iv';
        }
    }
}
