<?php
namespace Aws\Common\Signature;
use Aws\Common\Credentials\CredentialsInterface;
use Guzzle\Http\Message\RequestInterface;
abstract class AbstractSignature implements SignatureInterface
{
    protected function getTimestamp()
    {
        return time();
    }
    public function createPresignedUrl(
        RequestInterface $request,
        CredentialsInterface $credentials,
        $expires
    ) {
        throw new \BadMethodCallException(__METHOD__ . ' not implemented');
    }
}
